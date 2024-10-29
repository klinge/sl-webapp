<?php

namespace Tests\Unit\Traits;

use PHPUnit\Framework\TestCase;
use App\Traits\ResponseFormatter;
use App\Utils\Session;
use App\Application;
use AltoRouter;
use Laminas\Diactoros\Response\RedirectResponse;
use Psr\Http\Message\ResponseInterface;

class ResponseFormatterTest extends TestCase
{
    private $testClass;
    private $app;
    private $router;

    protected function setUp(): void
    {
        // Create mock objects
        $this->app = $this->createMock(Application::class);
        $this->router = $this->createMock(AltoRouter::class);

        // Create an anonymous test class that uses the ResponseFormatter trait
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
        };
        $this->testClass->setApp($this->app);
    }

    public function testRedirectWithSuccessSetsFlashMessageAndRedirects()
    {
        $this->router->method('generate')
            ->willReturn('/success-route');

        $this->app->method('getRouter')
            ->willReturn($this->router);

        $data = ['success-route', 'Success message'];


        $this->callProtectedMethod($this->testClass, 'redirectWithSuccess', $data);
        $this->assertEquals('Success message', Session::get('flash_message')['message']);

        $response =  $this->callProtectedMethod($this->testClass, 'emitRedirect', [$data[0]]);
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('/success-route', $response->getHeader('Location')[0]);
    }

    public function testRedirectWithErrorSetsFlashMessageAndRedirects()
    {
        $this->router->method('generate')
            ->willReturn('/error-route');

        $this->app->method('getRouter')
            ->willReturn($this->router);

        $data = ['error-route', 'Error message'];


        $this->callProtectedMethod($this->testClass, 'redirectWithSuccess', $data);
        $this->assertEquals('Error message', Session::get('flash_message')['message']);

        $response =  $this->callProtectedMethod($this->testClass, 'emitRedirect', [$data[0]]);
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('/error-route', $response->getHeader('Location')[0]);
    }

    public function testRenderWithErrorSetsFlashMessageAndRendersView()
    {
        $view = $this->createMock(\App\Utils\View::class);

        $this->testClass->setview($view);

        $view->expects($this->once())
            ->method('render')
            ->with('test-view', ['data' => 'value']);

        $this->callProtectedMethod($this->testClass, 'renderWithError', ['test-view', 'Error message', ['data' => 'value']]);

        $this->assertEquals('Error message', Session::get('flash_message')['message']);
    }

    private function callProtectedMethod($object, $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        return $method->invokeArgs($object, $parameters);
    }
}
