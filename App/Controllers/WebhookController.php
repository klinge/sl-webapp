<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Application;
use Psr\Http\Message\ServerRequestInterface;

class WebhookController extends BaseController
{
    private string $githubSecret = '';
    private string $remoteIp;

    private const REPOSITORY_ID = 781366756;

    public function __construct(Application $app, ServerRequestInterface $request)
    {
        parent::__construct($app, $request);
        $this->githubSecret = $this->app->getConfig('GITHUB_WEBHOOK_SECRET');
        $this->remoteIp = $this->request->getServerParams()['REMOTE_ADDR'];
    }

    public function handle(): void
    {
        $this->app->getLogger()->info('Starting to process webhook call from: ' . $this->remoteIp, ['class' => __CLASS__, 'function' => __FUNCTION__]);
        $this->app->getLogger()->debug('Headers: ' . json_encode($this->request->getHeaders()), ['class' => __CLASS__, 'function' => __FUNCTION__]);
        $this->app->getLogger()->debug('Payload: ' . json_encode($this->request->getParsedBody()), ['class' => __CLASS__, 'function' => __FUNCTION__]);

        $payload = $this->verifyRequest();

        //If payload is empty, it was a ping request or it didn't pass the verification
        if (!$payload) {
            exit;
        }
        $branch = str_replace('refs/heads/', '', $payload['ref']);
        // Check if it's a release branch
        if (!preg_match('/^release\/v\d+(\.\d+)?$/', $branch)) {
            $this->jsonResponse(['status' => 'ignored', 'message' => 'Not a push to the release branch']);
            $this->app->getLogger()->debug('Not a push to the release branch', ['class' => __CLASS__, 'function' => __FUNCTION__]);
            exit;
        } else {
            $this->jsonResponse(['status' => 'success', 'message' => 'Successfully received a push on the release branch']);
            $this->app->getLogger()->info('Received a push on the release branch. Continuing to deploy.. ');
        }
        $repoUrl = $payload['repository']['clone_url'];
        $result = $this->handleRepositoryOperations($branch, $repoUrl);
        if ($result['status'] !== 'success') {
            $this->app->getLogger()->error(
                "Error occurred while handling repository operations. Message: {$result['message']}",
                ['class' => __CLASS__, 'function' => __FUNCTION__]
            );
            exit;
        }
        //Finally schedule the deploy of the repository to the web server
        $this->scheduleDeployment();
    }

    public function verifyRequest(): array
    {
        $payload = [];
        $event = '';

        // Verify that it's a github webhook request
        if (!$this->request->hasHeader('X-GitHub-Event')) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Missing event header'], 400);
            $this->app->getLogger()->warning("Missing X-GitHub-Event header. Headers was: " . json_encode($this->request->getHeaders()));
            return $payload;
        } else {
            $event = $this->request->getHeader('X-GitHub-Event')[0];
        }
        // Verify that it's ping or a push
        if (!empty($event) && $event !== 'push' && $event !== 'ping') {
            $this->jsonResponse(['status' => 'error', 'message' => 'Event not supported'], 400);
            $this->app->getLogger()->warning("Github event was not push or ping");
            return $payload;
        }
        // Verify that the signature header is there
        if (!$this->request->hasHeader('X-Hub-Signature-256')) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Missing signature header'], 400);
            $this->app->getLogger()->warning("Github signature header missing");
            return $payload;
        }
        // Verify signature header format
        $signature = $this->request->getHeader('X-Hub-Signature-256')[0];
        $signature_parts = explode('=', $signature);

        if (count($signature_parts) != 2 || $signature_parts[0] != 'sha256') {
            $this->jsonResponse(['status' => 'error', 'message' => 'Bad header format'], 400);
            $this->app->getLogger()->warning("Bad format for the github signature header");
            return $payload;
        }
        //All is well so far - get the request body and validate the signature
        $rawBody = (string) $this->request->getBody();

        $signatureResult = $this->validateSignature($rawBody, $signature_parts[1], $this->githubSecret);

        if ($signatureResult !== true) {
            $this->jsonResponse(['status' => 'error', 'message' => $signatureResult], 401);
            $this->app->getLogger()->warning("Github signature did not verify");
            return [];
        }

        $payload = json_decode($rawBody, true);

        //Lastly check that the request was for the correct repository
        if ($payload['repository']['id'] !== self::REPOSITORY_ID) {
            $this->jsonResponse(['status' => 'ignored', 'message' => "Not handling requests for this repo, {$payload['repository']['full_name']}"], 200);
            $this->app->getLogger()->warning("Repository Id in the request was not correct");
            return [];
        }

        //Handle the ping event here, just send a pong back
        if ($event === 'ping') {
            $this->jsonResponse(['status' => 'ok', 'message' => 'Pong'], 200);
            $this->app->getLogger()->debug("Got a ping event, sent a pong back");
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
        $cloneDir = $this->app->getConfig('REPO_BASE_DIRECTORY') . '/' . basename($repoUrl, '.git');
        $this->app->getLogger()->debug("Fetching github repo to directory: " . $cloneDir);

        if (!is_dir($cloneDir)) {
            // Clone the repository if it doesn't exist
            exec("git clone $repoUrl $cloneDir 2>&1", $output, $returnVar);
            if ($returnVar !== 0) {
                $this->app->getLogger()->error("Failed to clone repository: " . implode(",", $output));
                return ['status' => 'error', 'message' => 'Failed to clone repository'];
            }
        } else {
            // If the repository already exists, fetch the latest changes
            chdir($cloneDir);
            exec("git fetch --all 2>&1", $output, $returnVar);
            if ($returnVar !== 0) {
                $this->app->getLogger()->error("Failed to fetch latest changes: " . implode(",", $output));
                return ['status' => 'error', 'message' => 'Failed to fetch latest changes'];
            }
        }
        // Change to the cloned directory (if not already there)
        chdir($cloneDir);

        // Checkout the branch that was pushed
        exec("git checkout $branch 2>&1", $output, $returnVar);
        if ($returnVar !== 0) {
            $this->app->getLogger()->error("Failed to checkout branch $branch: " . implode(",", $output));
            return ['status' => 'error', 'message' => 'Failed to checkout branch'];
        }
        // Pull the latest changes
        $this->app->getLogger()->debug("Checking out and pulling changes for branch: " . $branch);
        exec("git pull origin $branch 2>&1", $output, $returnVar);
        if ($returnVar !== 0) {
            $this->app->getLogger()->error("Failed to pull latest changes for branch $branch: " . implode("\n", $output));
            return ['status' => 'error', 'message' => 'Failed to pull latest changes'];
        }

        $this->app->getLogger()->info("Successfully updated and checked out branch $branch");
        return ['status' => 'success', 'message' => 'Repository operations completed successfully'];
    }


    private function scheduleDeployment(): bool
    {
        $triggerFile = $this->app->getConfig('TRIGGER_FILE_DIRECTORY') . '/deploy_' . time() . '.trigger';
        $result = file_put_contents($triggerFile, '');

        if ($result === false) {
            $this->app->getLogger()->error('Failed to create deployment trigger file');
            return false;
        }

        $this->app->getLogger()->info('Deployment trigger file created successfully, deployment job will be run by cron');
        return true;
    }
}
