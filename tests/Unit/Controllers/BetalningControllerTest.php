<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers;

use PHPUnit\Framework\TestCase;
use App\Controllers\BetalningController;
use App\Services\BetalningService;
use App\Services\BetalningServiceResult;
use App\Application;
use App\Utils\View;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Exception;

class BetalningControllerTest extends TestCase
{
    private BetalningController $controller;
    private $betalningService;
    private $view;
    private $request;
    private $app;
    private $urlGenerator;

    protected function setUp(): void
    {
        $this->betalningService = $this->createMock(BetalningService::class);
        $this->view = $this->createMock(View::class);
        $this->request = $this->createMock(ServerRequestInterface::class);
        $this->app = $this->createMock(Application::class);

        $this->urlGenerator = $this->createMock(\App\Services\UrlGeneratorService::class);

        $this->controller = new BetalningController(
            $this->betalningService,
            $this->view,
            $this->urlGenerator
        );

        $this->setProtectedProperty($this->controller, 'request', $this->request);
    }

    protected function tearDown(): void
    {
        // Reset Database singleton
        $reflection = new \ReflectionClass(\App\Utils\Database::class);
        $instance = $reflection->getProperty('instance');
        $instance->setAccessible(true);
        $instance->setValue(null, null);
    }

    public function testListDelegatesServiceAndRendersView(): void
    {
        $expectedPayments = [
            ['id' => 1, 'belopp' => 100, 'namn' => 'Test Person'],
            ['id' => 2, 'belopp' => 200, 'namn' => 'Another Person']
        ];

        $this->betalningService->expects($this->once())
            ->method('getAllPayments')
            ->willReturn($expectedPayments);

        $mockResponse = $this->createMock(ResponseInterface::class);
        $this->view->expects($this->once())
            ->method('render')
            ->with('viewBetalning', [
                'title' => 'Betalningslista',
                'items' => $expectedPayments
            ])
            ->willReturn($mockResponse);

        $response = $this->controller->list();

        $this->assertSame($mockResponse, $response);
    }

    public function testGetMedlemBetalningWithPayments(): void
    {
        $params = ['id' => 1];
        $mockMedlem = $this->createMock(\App\Models\Medlem::class);
        $mockMedlem->method('getNamn')->willReturn('Test Person');

        $testPayments = [['id' => 1, 'belopp' => 100]];
        $memberData = ['medlem' => $mockMedlem, 'payments' => $testPayments];

        $this->betalningService->expects($this->once())
            ->method('getPaymentsForMember')
            ->with(1)
            ->willReturn($memberData);

        $mockResponse = $this->createMock(ResponseInterface::class);
        $this->view->expects($this->once())
            ->method('render')
            ->with('viewBetalning', [
                'success' => true,
                'title' => 'Betalningar för: Test Person',
                'items' => $testPayments
            ])
            ->willReturn($mockResponse);

        $response = $this->controller->getMedlemBetalning($this->request, $params);

        $this->assertSame($mockResponse, $response);
    }

    public function testGetMedlemBetalningNoPayments(): void
    {
        $params = ['id' => 1];
        $mockMedlem = $this->createMock(\App\Models\Medlem::class);
        $memberData = ['medlem' => $mockMedlem, 'payments' => []];

        $this->betalningService->expects($this->once())
            ->method('getPaymentsForMember')
            ->with(1)
            ->willReturn($memberData);

        $mockResponse = $this->createMock(ResponseInterface::class);
        $this->view->expects($this->once())
            ->method('render')
            ->with('viewBetalning', [
                'success' => false,
                'title' => 'Inga betalningar hittades'
            ])
            ->willReturn($mockResponse);

        $response = $this->controller->getMedlemBetalning($this->request, $params);

        $this->assertSame($mockResponse, $response);
    }

    public function testGetMedlemBetalningHandlesServiceException(): void
    {
        $params = ['id' => 999];

        $this->betalningService->expects($this->once())
            ->method('getPaymentsForMember')
            ->with(999)
            ->willThrowException(new Exception('Medlem not found'));

        $mockResponse = $this->createMock(ResponseInterface::class);
        $this->view->expects($this->once())
            ->method('render')
            ->with('viewBetalning', [
                'success' => false,
                'title' => 'Medlem hittades inte'
            ])
            ->willReturn($mockResponse);

        $response = $this->controller->getMedlemBetalning($this->request, $params);

        $this->assertSame($mockResponse, $response);
    }

    public function testCreateBetalningDelegatesServiceAndReturnsJson(): void
    {
        $postData = ['belopp' => 100, 'datum' => '2024-01-01', 'avser_ar' => 2024];
        $successResult = new BetalningServiceResult(true, 'Payment created successfully', 123);

        $this->request->expects($this->once())
            ->method('getParsedBody')
            ->willReturn($postData);

        $this->betalningService->expects($this->once())
            ->method('createPayment')
            ->with($postData)
            ->willReturn($successResult);

        $response = $this->controller->createBetalning($this->request);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals('application/json', $response->getHeader('Content-Type')[0]);

        $responseData = json_decode((string) $response->getBody(), true);
        $this->assertTrue($responseData['success']);
        $this->assertEquals('Payment created successfully', $responseData['message']);
    }

    public function testCreateBetalningHandlesServiceFailure(): void
    {
        $postData = ['invalid' => 'data'];
        $failureResult = new BetalningServiceResult(false, 'Validation failed');

        $this->request->expects($this->once())
            ->method('getParsedBody')
            ->willReturn($postData);

        $this->betalningService->expects($this->once())
            ->method('createPayment')
            ->with($postData)
            ->willReturn($failureResult);

        $response = $this->controller->createBetalning($this->request);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $responseData = json_decode((string) $response->getBody(), true);
        $this->assertFalse($responseData['success']);
        $this->assertEquals('Validation failed', $responseData['message']);
    }

    public function testDeleteBetalningDelegatesServiceAndReturnsJson(): void
    {
        $params = ['id' => '123'];
        $successResult = new BetalningServiceResult(true, 'Payment deleted successfully');

        $this->betalningService->expects($this->once())
            ->method('deletePayment')
            ->with(123)
            ->willReturn($successResult);

        $response = $this->controller->deleteBetalning($this->request, $params);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals('application/json', $response->getHeader('Content-Type')[0]);

        $responseData = json_decode((string) $response->getBody(), true);
        $this->assertTrue($responseData['success']);
        $this->assertEquals('Payment deleted successfully', $responseData['message']);
    }

    public function testGetBetalningReturnsNotImplemented(): void
    {
        $params = ['id' => '123'];

        $response = $this->controller->getBetalning($this->request, $params);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $responseData = json_decode((string) $response->getBody(), true);
        $this->assertEquals('Payment editing not yet implemented', $responseData['message']);
    }

    private function setProtectedProperty(object $protectedClass, string $property, object $objectToInject): void
    {
        $reflection = new \ReflectionClass($protectedClass);
        $property = $reflection->getProperty($property);
        $property->setAccessible(true);
        $property->setValue($protectedClass, $objectToInject);
    }
}
