<?php

namespace Tests\Unit\Traits;

use PHPUnit\Framework\TestCase;
use App\Traits\ResponseFormatter;
use App\Utils\Session;
use App\Application;
use League\Route\Router;
use Laminas\Diactoros\Response\RedirectResponse;
use Psr\Http\Message\ResponseInterface;

class ResponseFormatterTest extends TestCase
{
    private $testClass;
    private $app;
    private $router;

    protected function setUp(): void
    {
        $this->app = $this->createMock(Application::class);
        $this->router = $this->createMock(Router::class);
        $this->app->method('getRouter')->willReturn($this->router);

        $this->testClass = new class {
            use ResponseFormatter;

            public $app;
            public $view;

            public function setApp($app)
            {
                $this->app = $app;
            }
            public function setView($view)
            {
                $this->view = $view;
            }

            protected function createUrl(string $routeName, array $params = []): string
            {
                $router = $this->app->getRouter();
                $route = $router->getNamedRoute($routeName);
                return $route->getPath($params);
            }
        };
        $this->testClass->setApp($this->app);
    }

    /**
     * @dataProvider redirectTestProvider
     */
    public function testRedirectMethods($method, $routeName, $message, $expectedPath)
    {
        $this->setupMockRoute($expectedPath);

        $response = $this->callProtectedMethod($this->testClass, $method, [$routeName, $message]);

        $this->assertEquals($message, Session::get('flash_message')['message']);
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals($expectedPath, $response->getHeader('Location')[0]);
    }

    public static function redirectTestProvider()
    {
        return [
            ['redirectWithSuccess', 'success-route', 'Success message', '/success-route'],
            ['redirectWithError', 'error-route', 'Error message', '/error-route'],
        ];
    }

    public function testRenderWithErrorReturnsHtmlResponse()
    {
        $view = $this->createMock(\App\Utils\View::class);
        $mockResponse = $this->createMock(ResponseInterface::class);
        $this->testClass->setView($view);

        $view->expects($this->once())
            ->method('render')
            ->with('test-view', ['data' => 'value'])
            ->willReturn($mockResponse);

        $response = $this->callProtectedMethod($this->testClass, 'renderWithError', ['test-view', 'Error message', ['data' => 'value']]);

        $this->assertEquals('Error message', Session::get('flash_message')['message']);
        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    private function setupMockRoute($path)
    {
        $mockRoute = $this->createMock(\League\Route\Route::class);
        $mockRoute->method('getPath')->willReturn($path);
        $this->router->method('getNamedRoute')->willReturn($mockRoute);
    }

    private function callProtectedMethod($object, $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        return $method->invokeArgs($object, $parameters);
    }
}
