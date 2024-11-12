<?php

declare(strict_types=1);

namespace App\Services;

use App\Application;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;
use Psr\Http\Message\ResponseInterface;

class MailAliasService
{
    private Client $client;
    private LoggerInterface $logger;
    private Application $app;
    private string $baseUrl;
    private string $username;
    private string $password;
    private ?string $accessToken = null;

    public function __construct(Application $app)
    {
        $this->client = new Client();
        $this->app = $app;

        $this->logger = $this->app->getLogger();

        $this->baseUrl = $this->app->getConfig('SMARTEREMAIL_BASE_URL');
        $this->username = $this->app->getConfig('SMARTEREMAIL_USERNAME');
        $this->password = $this->app->getConfig('SMARTEREMAIL_PASSWORD');
    }

    private function authenticate(): void
    {
        $response = $this->client->post($this->baseUrl . '/api/v1/auth/authenticate-user', [
            'json' => [
                'username' => $this->username,
                'password' => $this->password,
                'teamWorkspace' => false,
                'retrieveAutoLoginToken' => false
            ]
        ]);

        $data = json_decode($response->getBody()->getContents(), true);
        $this->accessToken = $data['accessToken'];
    }

    public function updateAlias(string $aliasName, array $targetEmails): void
    {
        if (!$this->accessToken) {
            $this->authenticate();
        }

        $requestBody = [
            'alias' => [
                'name' => $aliasName,
                'aliasTargetList' => array_values($targetEmails)
            ]
        ];

        $promise = $this->client->postAsync($this->baseUrl . '/api/v1/settings/domain/alias', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Content-Type' => 'application/json'
            ],
            'json' => $requestBody
        ]);

        $promise->then(
            function (ResponseInterface $response) use ($aliasName) {
                $this->logger->info('Mail alias updated successfully for: ' . $aliasName);
            },
            function (RequestException $e) use ($aliasName) {
                $this->logger->error('Mail alias update failed for' . $aliasName . ': ' . $e->getMessage());
            }
        );
        //Ugly but we have to wait otherwise script will exit before the promise is resolved
        //At some stage rewrite this class to use message queues
        $promise->wait();
    }
}
