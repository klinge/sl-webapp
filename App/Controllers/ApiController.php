<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Application;

class ApiController extends BaseController
{
    private string $githubSecret = '';

    public function __construct(Application $app, array $request)
    {
        parent::__construct($app, $request);
        $this->githubSecret = $this->app->getConfig('GITHUB_WEBHOOK_SECRET');
    }

    public function handleGithubWebhook()
    {
        $jsonPayload = file_get_contents('php://input');
        $data = json_decode($jsonPayload, true);
        var_dump($data);
        exit;

        // Now $data contains the webhook payload as an associative array
        // You can process it further, for example:
        $branch = str_replace('refs/heads/', '', $data['ref']);

        // Your logic for handling the webhook data goes here

        // You can use the jsonResponse method from BaseController to send a response
        return $this->jsonResponse(['status' => 'success', 'message' => 'Webhook received']);
    }
}
