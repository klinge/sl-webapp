<?php

namespace Tests\Unit\Utils;

use PHPUnit\Framework\TestCase;
use App\Utils\ResponseEmitter;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class ResponseEmitterTest extends TestCase
{
    private ResponseEmitter $emitter;

    protected function setUp(): void
    {
        $this->emitter = new ResponseEmitter();
    }

    public function testIsResponseEmptyWithStatus204()
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(204);

        $this->assertTrue($this->emitter->isResponseEmpty($response));
    }

    public function testIsResponseEmptyWithStatus205()
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(205);

        $this->assertTrue($this->emitter->isResponseEmpty($response));
    }

    public function testIsResponseEmptyWithStatus304()
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(304);

        $this->assertTrue($this->emitter->isResponseEmpty($response));
    }

    public function testIsResponseEmptyWithEmptySeekableBody()
    {
        $stream = $this->createMock(StreamInterface::class);
        $stream->method('isSeekable')->willReturn(true);
        $stream->method('rewind');
        $stream->method('read')->with(1)->willReturn('');

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);
        $response->method('getBody')->willReturn($stream);

        $this->assertTrue($this->emitter->isResponseEmpty($response));
    }

    public function testIsResponseEmptyWithNonEmptyBody()
    {
        $stream = $this->createMock(StreamInterface::class);
        $stream->method('isSeekable')->willReturn(true);
        $stream->method('rewind');
        $stream->method('read')->with(1)->willReturn('a');

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);
        $response->method('getBody')->willReturn($stream);

        $this->assertFalse($this->emitter->isResponseEmpty($response));
    }

    public function testIsResponseEmptyWithNonSeekableEofBody()
    {
        $stream = $this->createMock(StreamInterface::class);
        $stream->method('isSeekable')->willReturn(false);
        $stream->method('eof')->willReturn(true);

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);
        $response->method('getBody')->willReturn($stream);

        $this->assertTrue($this->emitter->isResponseEmpty($response));
    }

    public function testIsResponseEmptyWithNonSeekableNonEofBody()
    {
        $stream = $this->createMock(StreamInterface::class);
        $stream->method('isSeekable')->willReturn(false);
        $stream->method('eof')->willReturn(false);

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);
        $response->method('getBody')->willReturn($stream);

        $this->assertFalse($this->emitter->isResponseEmpty($response));
    }

    public function testConstructorWithCustomChunkSize()
    {
        $emitter = new ResponseEmitter(8192);
        $this->assertInstanceOf(ResponseEmitter::class, $emitter);
    }
}
