<?php

declare(strict_types=1);

namespace App\Services;

use App\Application;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;
use Psr\Http\Message\ResponseInterface;
use Monolog\Logger;

class MailAliasService
{
    private Client $client;
    private LoggerInterface $logger;
    /** @var array<string, string> */
    private array $config;
    private string $baseUrl;
    private string $username;
    private string $password;
    private ?string $accessToken = null;

    /**
     * Initialize MailAliasService with logger and configuration.
     *
     * @param Logger $logger Logger instance for service operations
     * @param array<string, string> $config Configuration array with SmarterMail settings
     */
    public function __construct(Logger $logger, array $config)
    {
        $this->client = new Client();
        $this->config = $config;
        $this->logger = $logger;

        $this->baseUrl = $this->config['SMARTEREMAIL_BASE_URL'];
        $this->username = $this->config['SMARTEREMAIL_USERNAME'];
        $this->password = $this->config['SMARTEREMAIL_PASSWORD'];
    }

    /**
     * Authenticate with SmarterMail API and store access token.
     *
     * @throws RequestException If authentication fails
     */
    private function authenticate(): void
    {
        try {
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
        } catch (RequestException $e) {
            $this->logger->error('Failed to authenticate with SmarterMail API: ' . $e->getMessage());
        }
    }

    /**
     * Update mail alias with new target email addresses.
     *
     * @param string $aliasName The name of the alias to update
     * @param array<int, string> $targetEmails Array of target email addresses
     */
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
