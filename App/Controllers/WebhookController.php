<?php

declare(strict_types=1);

namespace App\Controllers;

error_reporting(E_ALL);
ini_set('display_errors', 1);

use App\Application;

class WebhookController extends BaseController
{
    private string $githubSecret = '';
    private const REPOSITORY_ID = 781366756;

    public function __construct(Application $app, array $request)
    {
        parent::__construct($app, $request);
        $this->githubSecret = $this->app->getConfig('GITHUB_WEBHOOK_SECRET');
    }

    public function handle(): void
    {
        $this->app->getLogger()->info('Webhook called from: ' . $this->request['REMOTE_ADDR']);

        $payload = $this->verifyRequest();

        //If payload is empty, it was a ping request or it didn't pass the verification
        if (!$payload) {
            exit;
        }
        $branch = str_replace('refs/heads/', '', $payload['ref']);
        // Check if it's a release branch
        if (!preg_match('/^release\/v\d+(\.\d+)?$/', $branch)) {
            $this->jsonResponse(['status' => 'ignored', 'message' => 'Not a push to the release branch']);
            $this->app->getLogger()->info('Not a push to the release branch', ['class' => __CLASS__, 'function' => __FUNCTION__]);
            exit;
        }
        $repoUrl = $payload['repository']['clone_url'];
        $result = $this->handleRepositoryOperations($branch, $repoUrl);
        if ($result['status'] !== 'success') {
            $this->app->getLogger()->error(
                "Error occurred while handling repository operations. Message: {$result['message']}",
                ['class' => __CLASS__, 'function' => __FUNCTION__]
            );
        }
        //Finally deploy the repository to the web server
        $result = $this->scheduleDeployment();
        //TODO add error handling
    }

    public function verifyRequest(): array
    {
        $payload = [];
        $event = '';

        // Verify that it's a github webhook request
        if (!isset($this->request['HTTP_X_GITHUB_EVENT'])) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Missing header'], 400);
            return $payload;
        } else {
            $event = $this->request['HTTP_X_GITHUB_EVENT'];
        }
        // Validate that it's ping or a push
        if (!empty($event) && $event !== 'push' && $event !== 'ping') {
            $this->jsonResponse(['status' => 'error', 'message' => 'Event not supported'], 400);
            return $payload;
        }
        // Validate the signature using the github secret
        if (!isset($this->request['HTTP_X_HUB_SIGNATURE_256'])) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Missing header'], 400);
            return $payload;
        }
        // Validate signature header format
        $signature = $this->request['HTTP_X_HUB_SIGNATURE_256'];
        $signature_parts = explode('=', $signature);

        if (count($signature_parts) != 2 || $signature_parts[0] != 'sha256') {
            $this->jsonResponse(['status' => 'error', 'message' => 'Bad header format'], 400);
            return $payload;
        }
        //All is well so far - get the request body and validate the signature
        $rawBody = file_get_contents('php://input');

        $signatureResult = $this->validateSignature($rawBody, $signature_parts[1], $this->githubSecret);

        if ($signatureResult !== true) {
            $this->jsonResponse(['status' => 'error', 'message' => $signatureResult], 401);
            return [];
        }

        $payload = json_decode($rawBody, true);

        //Lastly check that the request was for the correct repository
        if ($payload['repository']['id'] !== self::REPOSITORY_ID) {
            $this->jsonResponse(['status' => 'ignored', 'message' => "Not handling requests for this repo, {$payload['repository']['full_name']}"], 200);
            $payload = [];
            return $payload;
        }

        //Handle the ping event here, just send a pong back
        if ($event === 'ping') {
            $this->jsonResponse(['status' => 'ok', 'message' => 'Pong'], 200);
            return [];
        }

        //Return paylod, empty array if there were any errors
        return $payload;
    }

    private function validateSignature(string $rawRequestBody, string $signature, string $secret): bool|string
    {
        //Calculate the expected signature
        //$utf8Body = mb_convert_encoding($rawRequestBody, 'UTF-8', 'ASCII');
        $calculatedSignature = hash_hmac('sha256', $rawRequestBody, $secret, false);

        //Compare it to the actual signature
        $hashOk = hash_equals($calculatedSignature, $signature);
        if ($hashOk) {
            return true;
        } else {
            return "Request signature: {$signature}, calulated signature: {$calculatedSignature}";
        }
    }

    private function handleRepositoryOperations(string $branch, string $repoUrl): array
    {
        $cloneDir = '/var/www/html/repos/' . basename($repoUrl, '.git');

        if (!is_dir($cloneDir)) {
            // Clone the repository if it doesn't exist
            exec("git clone $repoUrl $cloneDir 2>&1", $output, $returnVar);
            if ($returnVar !== 0) {
                error_log("Failed to clone repository: " . implode("\n", $output));
                return ['status' => 'error', 'message' => 'Failed to clone repository'];
            }
        } else {
            // If the repository already exists, fetch the latest changes
            chdir($cloneDir);
            exec("git fetch --all 2>&1", $output, $returnVar);
            if ($returnVar !== 0) {
                error_log("Failed to fetch latest changes: " . implode("\n", $output));
                return ['status' => 'error', 'message' => 'Failed to fetch latest changes'];
            }
        }
        // Change to the cloned directory (if not already there)
        chdir($cloneDir);

        // Checkout the branch that was pushed
        exec("git checkout $branch 2>&1", $output, $returnVar);
        if ($returnVar !== 0) {
            error_log("Failed to checkout branch $branch: " . implode("\n", $output));
            return ['status' => 'error', 'message' => 'Failed to checkout branch'];
        }

        // Pull the latest changes
        exec("git pull origin $branch 2>&1", $output, $returnVar);
        if ($returnVar !== 0) {
            error_log("Failed to pull latest changes for branch $branch: " . implode("\n", $output));
            return ['status' => 'error', 'message' => 'Failed to pull latest changes'];
        }

        error_log("Successfully updated and checked out branch $branch");
        return ['status' => 'success', 'message' => 'Repository operations completed successfully'];
    }


    private function scheduleDeployment(): bool
    {
        $deployScriptPath = '/var/www/html/scrips/deployScript.sh';
        $command = "echo '/var/www/html/sl-webapp/scripts/deployScript.sh > /var/www/html/deploy.log 2>&1' | at now + 2 minutes";

        exec($command, $output, $returnVar);

        if ($returnVar !== 0) {
            $this->app->getLogger()->error('Failed to schedule deployment', ['output' => implode("\n", $output)]);
            return false;
        }

        $this->app->getLogger()->info('Deployment scheduled successfully, check job queue with atq');
        return true;
    }
}
