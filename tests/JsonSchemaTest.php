<?php

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamFile;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr7Middlewares\Middleware\JsonSchema;
use Psr7Middlewares\Middleware\JsonValidator;
use Zend\Diactoros\Response;

/**
 * @covers \Psr7Middlewares\Middleware\JsonSchema
 */
class JsonSchemaTest extends Base
{
    /** @var JsonSchema */
    private $validator;

    /** @var vfsStreamFile */
    private $schema;

    protected function setUp()
    {
        parent::setUp();

        $root = vfsStream::setup('test');
        $this->schema = vfsStream::newFile('schema.json');
        $root->addChild($this->schema);

        $this->validator = new JsonSchema([
            '/en/v1/users' => $this->schema->url(),
        ]);

        file_put_contents($this->schema->url(), <<<'JSON'
{
  "$schema": "http://json-schema.org/draft-04/schema#",
  "type": "object",
  "properties": {
    "id": {
      "type": "string"
    },
    "name": {
      "type": "object",
      "properties": {
        "given": {
          "type": "string"
        },
        "family": {
          "type": "string"
        }
      },
      "required": [
        "given",
        "family"
      ]
    },
    "email": {
        "type": "string",
        "format": "email"
    }
  },
  "required": [
    "id",
    "name",
    "email"
  ]
}
JSON
        );
    }

    public function testInvalidJson()
    {
        $request = $this->request('/en/v1/users')
            ->withParsedBody(json_decode(json_encode([
                'foo' => 'bar',
            ])));

        $response = $this->dispatch([$this->validator], $request, new Response());

        self::assertInstanceOf(ResponseInterface::class, $response);
        self::assertGreaterThanOrEqual(400, $response->getStatusCode(), $response->getBody());
    }

    public function dataInvalidParsedBody()
    {
        return [
            ['InvalidBody'],
            [1234],
            [1234.56],
            [true],
            [new \stdClass()],
            [['foo' => 'bar']],
            [STDERR],
        ];
    }

    /**
     * @dataProvider dataInvalidParsedBody
     *
     * @param mixed $parsedBody
     */
    public function testInvalidParsedBody($parsedBody)
    {
        $request = $this->request('/en/v1/users')
            ->withParsedBody($parsedBody);

        $response = $this->dispatch([$this->validator], $request, new Response());

        self::assertInstanceOf(ResponseInterface::class, $response);
        self::assertGreaterThanOrEqual(400, $response->getStatusCode(), $response->getBody());
    }

    public function testValidJson()
    {
        $request = $this->request('/en/v1/users')
            ->withParsedBody(json_decode(json_encode([
                'id' => '1234',
                'name' => [
                    'given' => 'Foo',
                    'family' => 'Bar',
                ],
                'email' => 'foo.bar@example.com',
            ])));

        $response = $this->dispatch([$this->validator], $request, new Response());

        self::assertInstanceOf(ResponseInterface::class, $response);
        self::assertGreaterThanOrEqual(200, $response->getStatusCode(), $response->getBody());
        self::assertLessThan(300, $response->getStatusCode(), $response->getBody());
    }

    public function testUnmatchedRouteBypassesValidation()
    {
        $request = $this->request('/en/v1/posts')
            ->withParsedBody(json_decode(json_encode([
                'foo' => 'bar',
            ])));

        $response = $this->dispatch([$this->validator], $request, new Response());

        self::assertInstanceOf(ResponseInterface::class, $response);
        self::assertGreaterThanOrEqual(200, $response->getStatusCode(), $response->getBody());
        self::assertLessThan(300, $response->getStatusCode(), $response->getBody());
    }

    public function testSubRouteMatchesValidator()
    {
        $request = $this->request('/en/v1/users')
            ->withParsedBody(json_decode(json_encode([
                'foo' => 'bar',
            ])));

        $response = $this->dispatch([$this->validator], $request, new Response());

        self::assertInstanceOf(ResponseInterface::class, $response);
        self::assertGreaterThanOrEqual(400, $response->getStatusCode(), $response->getBody());
    }

    public function testPayloadCollaborationWithValidJson()
    {
        $request = $this->request('/en/v1/users', ['Content-Type' => 'application/json'])
            ->withMethod('POST')
            ->withBody($this->stream(json_encode([
                'id' => '1234',
                'name' => [
                    'given' => 'Foo',
                    'family' => 'Bar',
                ],
                'email' => 'foo.bar@example.com',
            ])));

        $response = $this->dispatch(
            [
                new \Psr7Middlewares\Middleware\Payload([
                    'forceArray' => false,
                ]),
                $this->validator,
            ],
            $request,
            new Response()
        );

        self::assertInstanceOf(ResponseInterface::class, $response);
        self::assertGreaterThanOrEqual(200, $response->getStatusCode(), $response->getReasonPhrase());
        self::assertLessThan(300, $response->getStatusCode(), $response->getBody());
    }

    public function testPayloadCollaborationWithInvalidJson()
    {
        $request = $this->request('/en/v1/users', ['Content-Type' => 'application/json'])
            ->withMethod('POST')
            ->withBody($this->stream('InvalidJson'));

        $response = $this->dispatch(
            [
                new \Psr7Middlewares\Middleware\Payload([
                    'forceArray' => false,
                ]),
                $this->validator,
            ],
            $request,
            new Response()
        );

        self::assertInstanceOf(ResponseInterface::class, $response);
        self::assertGreaterThanOrEqual(400, $response->getStatusCode(), $response->getReasonPhrase());
    }

    public function testCustomErrorHandler()
    {
        $request = $this->request('/en/v1/users')
            ->withParsedBody(json_decode(json_encode([
                'foo' => 'bar',
            ])));

        $wasCalled = false;
        $this->validator->errorHandler(
            function (ServerRequestInterface $request, ResponseInterface $response) use (&$wasCalled) {
                self::assertNotCount(0, JsonValidator::getErrors($request));

                $wasCalled = true;
            }
        );

        $this->dispatch([$this->validator], $request, new Response());
        self::assertTrue($wasCalled);
    }
}
