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

    public function testRenderCreatesHtmlResponse()
    {
        ob_start();
        $this->view->render('test');
        ob_end_clean();

        $property = $this->makeResponseAccessible('response');
        $response = $property->getValue($this->view);

        $this->assertInstanceOf(HtmlResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testRenderThrowsExceptionForNonExistentTemplate()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('not found');

        $this->view->render('nonexistent');
    }

    public function testAssignAddsDataToViewArray()
    {
        $this->view->assign('testKey', 'testValue');
        $this->view->assign('numberKey', 42);

        $property = $this->makeResponseAccessible('data');
        $data = $property->getValue($this->view);

        $this->assertEquals('testValue', $data['testKey']);
        $this->assertEquals(42, $data['numberKey']);
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
        unlink($this->tempDir . '/public/views/test.php');
        rmdir($this->tempDir . '/public/views');
        rmdir($this->tempDir . '/public');
        rmdir($this->tempDir);
    }
}
