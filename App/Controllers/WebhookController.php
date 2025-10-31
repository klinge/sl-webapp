<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\UrlGeneratorService;
use App\Services\Github\GitHubService;
use App\Services\Github\GitRepositoryService;
use App\Services\Github\DeploymentService;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Monolog\Logger;
use League\Container\Container;

class WebhookController extends BaseController
{
    private string $remoteIp;

    public function __construct(
        UrlGeneratorService $urlGenerator,
        ServerRequestInterface $request,
        Logger $logger,
        Container $container,
        private GitHubService $githubService,
        private GitRepositoryService $gitRepositoryService,
        private DeploymentService $deploymentService
    ) {
        parent::__construct($urlGenerator, $request, $logger, $container);
        $this->remoteIp = $this->request->getServerParams()['REMOTE_ADDR'];
    }

    public function handle(): ResponseInterface
    {
        $this->logger->info(
            'Starting to process webhook call from: ' . $this->remoteIp,
            ['class' => __CLASS__, 'function' => __FUNCTION__]
        );
        $this->logger->debug(
            'Headers: ' . json_encode($this->request->getHeaders()),
            ['class' => __CLASS__, 'function' => __FUNCTION__]
        );
        $this->logger->debug(
            'Payload: ' . json_encode($this->request->getParsedBody()),
            ['class' => __CLASS__, 'function' => __FUNCTION__]
        );

        $verificationResult = $this->verifyRequest();

        // Handle verification failures with appropriate responses
        if (!$verificationResult) {
            if (!$this->request->hasHeader('X-GitHub-Event')) {
                return $this->jsonResponse(['status' => 'error', 'message' => 'Missing event header'], 400);
            }

            $event = $this->request->getHeader('X-GitHub-Event')[0] ?? '';

            if ($event !== 'push' && $event !== 'ping') {
                return $this->jsonResponse(['status' => 'error', 'message' => 'Event not supported'], 400);
            }

            if (!$this->request->hasHeader('X-Hub-Signature-256')) {
                return $this->jsonResponse(['status' => 'error', 'message' => 'Missing signature header'], 400);
            }

            $signature = $this->request->getHeader('X-Hub-Signature-256')[0] ?? '';
            $signature_parts = explode('=', $signature);

            if (count($signature_parts) != 2 || $signature_parts[0] != 'sha256') {
                return $this->jsonResponse(['status' => 'error', 'message' => 'Bad header format'], 400);
            }

            $rawBody = (string) $this->request->getBody();

            if (!$this->githubService->validateSignature($rawBody, $signature_parts[1])) {
                return $this->jsonResponse(['status' => 'error', 'message' => 'Invalid signature'], 401);
            }

            $payload = json_decode($rawBody, true);

            if (!$this->githubService->isValidRepository($payload)) {
                return $this->jsonResponse(
                    ['status' => 'ignored', 'message' => "Not handling requests for this repo, {$payload['repository']['full_name']}"],
                    200
                );
            }

            if ($event === 'ping') {
                return $this->jsonResponse(['status' => 'ok', 'message' => 'Pong'], 200);
            }

            return $this->jsonResponse(['status' => 'ok', 'message' => 'Request processed']);
        }

        $payload = $verificationResult;
        $branch = $this->githubService->extractBranchName($payload['ref']);

        // Check if it's a release branch
        if (!$this->githubService->isReleaseBranch($branch)) {
            $this->logger->debug('Not a push to the release branch', ['class' => __CLASS__, 'function' => __FUNCTION__]);
            return $this->jsonResponse(['status' => 'ignored', 'message' => 'Not a push to the release branch']);
        }

        $this->logger->info('Received a push on the release branch. Continuing to deploy.. ');

        $repoUrl = $payload['repository']['clone_url'];
        $result = $this->gitRepositoryService->updateRepository($branch, $repoUrl);
        if ($result['status'] !== 'success') {
            $this->logger->error(
                "Error occurred while handling repository operations. Message: {$result['message']}",
                ['class' => __CLASS__, 'function' => __FUNCTION__]
            );
            return $this->jsonResponse(['status' => 'error', 'message' => $result['message']], 500);
        }

        //Finally schedule the deploy of the repository to the web server
        $deploymentScheduled = $this->deploymentService->scheduleDeployment();

        if (!$deploymentScheduled) {
            return $this->jsonResponse(['status' => 'error', 'message' => 'Failed to schedule deployment'], 500);
        }

        return $this->jsonResponse(['status' => 'success', 'message' => 'Successfully received push and scheduled deployment']);
    }

    /**
     * Verifies and validates the incoming GitHub webhook request.
     *
     * This method performs several checks on the incoming request to ensure its authenticity and integrity.
     *
     * If it's a 'ping' event, it responds with a 'pong'.
     * For 'push' events, it processes the payload for further action.
     *
     * @return array The validated payload for 'push' events, or an empty array for other cases
     */
    public function verifyRequest(): array
    {
        $payload = [];
        $event = '';

        // Verify that it's a github webhook request
        if (!$this->request->hasHeader('X-GitHub-Event')) {
            $this->logger->warning("Missing X-GitHub-Event header. Headers was: " . json_encode($this->request->getHeaders()));
            return $payload;
        } else {
            $event = $this->request->getHeader('X-GitHub-Event')[0];
        }
        // Verify that it's ping or a push
        if (!empty($event) && $event !== 'push' && $event !== 'ping') {
            $this->logger->warning("Github event was not push or ping");
            return $payload;
        }
        // Verify that the signature header is there
        if (!$this->request->hasHeader('X-Hub-Signature-256')) {
            $this->logger->warning("Github signature header missing");
            return $payload;
        }
        // Verify signature header format
        $signature = $this->request->getHeader('X-Hub-Signature-256')[0];
        $signature_parts = explode('=', $signature);

        if (count($signature_parts) != 2 || $signature_parts[0] != 'sha256') {
            $this->logger->warning("Bad format for the github signature header");
            return $payload;
        }
        //All is well so far - get the request body and validate the signature
        $rawBody = (string) $this->request->getBody();

        if (!$this->githubService->validateSignature($rawBody, $signature_parts[1])) {
            $this->logger->warning("Github signature did not verify");
            return [];
        }

        $payload = json_decode($rawBody, true);

        //Lastly check that the request was for the correct repository
        if (!$this->githubService->isValidRepository($payload)) {
            $this->logger->warning("Repository Id in the request was not correct");
            return [];
        }

        //Handle the ping event here, just send a pong back
        if ($event === 'ping') {
            $this->logger->debug("Got a ping event, sent a pong back");
            return [];
        }

        //Return paylod, empty array if there were any errors
        return $payload;
    }
}
