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

/**
 * Handles GitHub webhook requests for automated deployment.
 *
 * This controller implements a two-stage deployment workflow:
 * 1. Receives and validates GitHub webhook push events
 * 2. Updates staging repository and schedules deployment via trigger file
 *
 * Workflow:
 * - GitHub sends webhook on push to release branch (release/vX.Y pattern)
 * - Validates event type, HMAC-SHA256 signature, repository ID (781366756)
 * - Clones/updates repository in staging directory
 * - Creates trigger file for cron job to copy staging to production
 *
 * Security:
 * - Validates webhook signature using shared secret
 * - Only processes events from configured repository
 * - Only deploys release branches matching pattern
 */
class WebhookController extends BaseController
{
    private string $remoteIp;

    /**
     * Initialize webhook controller with required services.
     *
     * @param UrlGeneratorService $urlGenerator URL generation service
     * @param ServerRequestInterface $request PSR-7 HTTP request
     * @param Logger $logger Monolog logger instance
     * @param Container $container DI container
     * @param GitHubService $githubService GitHub webhook validation service
     * @param GitRepositoryService $gitRepositoryService Git operations service
     * @param DeploymentService $deploymentService Deployment scheduling service
     */
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

    /**
     * Process incoming GitHub webhook request.
     *
     * Validates the webhook, checks if it's a release branch push,
     * updates the staging repository, and schedules deployment.
     *
     * Response codes:
     * - 200: Ping event or non-release branch (ignored)
     * - 200: Successful deployment scheduled
     * - 400: Invalid request (missing headers, bad format, unsupported event)
     * - 401: Invalid signature
     * - 500: Repository or deployment operation failed
     *
     * @return ResponseInterface JSON response with status and message
     */
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
     * Verify and validate incoming GitHub webhook request.
     *
     * Performs security checks in order:
     * 1. Verifies X-GitHub-Event header exists
     * 2. Checks event is 'push' or 'ping'
     * 3. Verifies X-Hub-Signature-256 header exists
     * 4. Validates signature header format (sha256=...)
     * 5. Validates HMAC-SHA256 signature against webhook secret
     * 6. Confirms repository ID matches expected value
     *
     * @return array<string, mixed> Validated webhook payload for push events, empty array for ping or validation failures
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
