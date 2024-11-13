<?php

declare(strict_types=1);

namespace Tests\Unit\Services\MailAlias;

use PHPUnit\Framework\TestCase;
use App\Services\MailAliasService;
use App\Application;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Exception\RequestException;
use Monolog\Logger;

class MailAliasServiceTest extends TestCase
{
    private $app;
    private $logger;
    private $service;
    private $mockQueue;

    protected function setUp(): void
    {
        $this->app = $this->createMock(Application::class);
        $this->logger = $this->createMock(Logger::class);

        //Setup mocking of the guzzle http client
        $this->mockQueue = new MockHandler();
        $handler = HandlerStack::create($this->mockQueue);
        $mockedClient = new Client(['handler' => $handler]);

        $this->app->method('getLogger')->willReturn($this->logger);
        $this->app->method('getConfig')->willReturn('test_value');

        $this->service = new MailAliasService($this->app);

        // Inject mocked client using reflection
        $reflection = new \ReflectionClass($this->service);
        $clientProperty = $reflection->getProperty('client');
        $clientProperty->setAccessible(true);
        $clientProperty->setValue($this->service, $mockedClient);
    }

    public function testUpdateAliasSuccess(): void
    {
        // Mock responses with complete response structure
        $this->mockQueue->append(
            new Response(200, [], json_encode([
                'accessToken' => 'test_token',  // Match the actual API response structure
                'data' => ['status' => 'success']
            ])),
            new Response(200, [], json_encode(['success' => 'true']))
        );

        // Set up logger expectation BEFORE calling the method
        $this->logger->expects($this->atLeastOnce())
            ->method('info')
            ->with($this->stringContains('Mail alias updated successfully'));

        $targetEmails = ['test1@example.com', 'test2@example.com'];

        $this->service->updateAlias('alias@example.com', $targetEmails);
    }

    public function testUpdateAliasFail(): void
    {
        // Mock responses with complete response structure
        $this->mockQueue->append(
            new Response(400, [], json_encode([ // Match the actual API response structure
                'data' => ['status' => 'false']
            ])),
            new Response(200, [], json_encode(['status' => 'success']))
        );

        // Set up logger expectation BEFORE calling the method
        $this->logger->expects($this->atLeastOnce())
            ->method('error')
            ->with($this->stringContains('Failed to authenticate with SmarterMail API'));

        $targetEmails = ['test1@example.com', 'test2@example.com'];

        $this->service->updateAlias('alias@example.com', $targetEmails);
    }
}
