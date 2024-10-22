<?php

namespace Tests\Unit\Traits;

use PHPUnit\Framework\TestCase;
use Laminas\Diactoros\Response\JsonResponse;
use App\Utils\ResponseEmitter;
use App\Traits\JsonResponder;

class JsonResponderTest extends TestCase
{
    private $testClass;
    private $mockEmitter;

    protected function setUp(): void
    {
        //create an anonymous test class that uses the JsonResponder trait
        $this->testClass = new class {
            use JsonResponder;
        };
        $this->mockEmitter = $this->createMock(ResponseEmitter::class);
    }

    public function testJsonResponseReturnsCorrectStatusCode()
    {
        $data = ['key' => 'value'];
        $statusCode = 201;

        ob_start();
        $response = $this->callProtectedMethod($this->testClass, 'jsonResponse', [$data, $statusCode]);
        ob_get_clean();

        $this->assertEquals($statusCode, $response->getStatusCode());
    }

    public function testJsonResponseUsesDefaultStatusCode()
    {
        $data = ['key' => 'value'];

        ob_start();
        $response = $this->callProtectedMethod($this->testClass, 'jsonResponse', [$data]);
        ob_get_clean();

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testJsonResponseSetsCorrectContentTypeHeader()
    {
        $data = ['key' => 'value'];
        $expectedContentType = 'application/json';

        ob_start();
        $response = $this->callProtectedMethod($this->testClass, 'jsonResponse', [$data]);
        ob_get_clean();

        $this->assertEquals($expectedContentType, $response->getHeader('Content-Type')[0]);
    }

    public function testJsonResponseSetsCorrectBody()
    {
        $data = ['anotherkey' => 'anothervalue'];
        $expectedBody = json_encode($data);

        ob_start();
        $response = $this->callProtectedMethod($this->testClass, 'jsonResponse', [$data]);
        ob_get_clean();

        $this->assertEquals($expectedBody, $response->getBody()->__toString());
        $this->assertEquals(JsonResponse::class, get_class($response));
    }

    public function testJsonResponseSetsCustomHeaders()
    {
        $data = ['key' => 'value'];
        $headers = ['X-Custom-Header' => 'Test'];

        ob_start();
        $response = $this->callProtectedMethod($this->testClass, 'jsonResponse', [$data, 201, $headers]);
        ob_get_clean();

        $this->assertEquals($headers['X-Custom-Header'], $response->getHeader('X-Custom-Header')[0]);
    }

    private function callProtectedMethod($object, $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        return $method->invokeArgs($object, $parameters);
    }
}
