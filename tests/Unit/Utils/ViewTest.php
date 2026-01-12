<?php

namespace Tests\Unit\Utils;

use PHPUnit\Framework\TestCase;
use App\Utils\View;
use App\Application;
use App\Utils\ResponseEmitter;
use Laminas\Diactoros\Response\HtmlResponse;

class ViewTest extends TestCase
{
    private View $view;
    private $app;
    private $tempDir;
    private ResponseEmitter $emitter;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/viewtest_' . uniqid();
        mkdir($this->tempDir . '/public/views', 0777, true);
        file_put_contents($this->tempDir . '/public/views/test.php', '<html><body>Test Template</body></html>');

        $this->app = $this->createMock(Application::class);
        $this->app->method('getAppDir')->willReturn('/app');
        $this->app->method('getRootDir')->willReturn($this->tempDir);

        $this->emitter = $this->createMock(ResponseEmitter::class);
        $this->view = new View($this->app);
    }

    public function testRenderReturnsHtmlResponse()
    {
        $response = $this->view->render('test');

        $this->assertInstanceOf(HtmlResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('Test Template', (string) $response->getBody());
    }

    public function testRenderWithCustomStatusCode()
    {
        $response = $this->view->render('test', [], 404);

        $this->assertInstanceOf(HtmlResponse::class, $response);
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertStringContainsString('Test Template', (string) $response->getBody());
    }

    public function testRenderWithDifferentStatusCodes()
    {
        // Test 500 status code
        $response500 = $this->view->render('test', [], 500);
        $this->assertEquals(500, $response500->getStatusCode());

        // Test 301 status code
        $response404 = $this->view->render('test', [], 404);
        $this->assertEquals(404, $response404->getStatusCode());

        // Test default 200 status code
        $response200 = $this->view->render('test');
        $this->assertEquals(200, $response200->getStatusCode());
    }

    public function testRenderThrowsExceptionForNonExistentTemplate()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('not found');

        $this->view->render('nonexistent');
    }

    public function testAssignDataIsAvailableInTemplate()
    {
        // Create a template that uses assigned data
        file_put_contents(
            $this->tempDir . '/public/views/data_test.php',
            '<html><body><?= $testKey ?? "" ?> - <?= $numberKey ?? "" ?></body></html>'
        );

        $this->view->assign('testKey', 'testValue');
        $this->view->assign('numberKey', 42);

        $response = $this->view->render('data_test');
        $body = (string) $response->getBody();

        $this->assertStringContainsString('testValue', $body);
        $this->assertStringContainsString('42', $body);
    }

    protected function makeResponseAccessible(string $propertyName): \ReflectionProperty
    {
        $reflection = new \ReflectionClass($this->view);
        $property = $reflection->getProperty($propertyName);
        $property->setAccessible(true);
        return $property;
    }

    protected function tearDown(): void
    {
        // Clean up temp files
        $files = glob($this->tempDir . '/public/views/*.php');
        foreach ($files as $file) {
            unlink($file);
        }
        rmdir($this->tempDir . '/public/views');
        rmdir($this->tempDir . '/public');
        rmdir($this->tempDir);
    }
}
