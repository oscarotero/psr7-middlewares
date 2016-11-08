<?php

use org\bovigo\vfs\vfsStream;
use Psr\Http\Message\ResponseInterface;
use Psr7Middlewares\Middleware\JsonValidator;
use Zend\Diactoros\Response;

/**
 * @covers \Psr7Middlewares\Middleware\JsonValidator
 */
class JsonValidatorTest extends Base
{
    /** @var JsonValidator */
    private $validator;

    /** @var [] */
    private static $schema = [
        '$schema' => 'http://json-schema.org/draft-04/schema#',
        'type' => 'object',
        'properties' => [
            'id' => [
                'type' => 'string'
            ],
            'name' => [
                'type' => 'object',
                'properties' => [
                    'given' => [
                        'type' => 'string'
                    ],
                    'family' => [
                        'type' => 'string'
                    ]
                ],
                'required' => [
                    'given',
                    'family'
                ]
            ],
            'email' => [
                'type' => 'string',
                'format' => 'email'
            ]
        ],
        'required' => [
            'id',
            'name',
            'email'
        ]
    ];

    protected function setUp()
    {
        parent::setUp();

        $this->validator = new JsonValidator(json_decode(json_encode(self::$schema)));
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
                    'family' => 'Bar'
                ],
                'email' => 'foo.bar@example.com',
            ])));

        $response = $this->dispatch([$this->validator], $request, new Response());

        self::assertInstanceOf(ResponseInterface::class, $response);
        self::assertGreaterThanOrEqual(200, $response->getStatusCode(), $response->getReasonPhrase());
        self::assertLessThan(300, $response->getStatusCode(), $response->getReasonPhrase());
    }

    public function testValidJsonFromFileReference()
    {
        $root = vfsStream::setup('test');
        $file = vfsStream::newFile('schema.json');
        $root->addChild($file);

        file_put_contents($file->url(), json_encode(self::$schema));

        $this->validator = new JsonValidator((object) [
            '$ref' => $file->url(),
        ]);

        $request = $this->request('/en/v1/users')
            ->withParsedBody(json_decode(json_encode([
                'id' => '1234',
                'name' => [
                    'given' => 'Foo',
                    'family' => 'Bar'
                ],
                'email' => 'foo.bar@example.com',
            ])));

        $response = $this->dispatch([$this->validator], $request, new Response());

        self::assertInstanceOf(ResponseInterface::class, $response);
        self::assertGreaterThanOrEqual(200, $response->getStatusCode(), $response->getReasonPhrase());
        self::assertLessThan(300, $response->getStatusCode(), $response->getReasonPhrase());
    }

    public function testPayloadCollaborationWithValidJson()
    {
        $request = $this->request('/en/v1/users', ['Content-Type' => 'application/json'])
            ->withMethod('POST')
            ->withBody($this->stream(json_encode([
                'id' => '1234',
                'name' => [
                    'given' => 'Foo',
                    'family' => 'Bar'
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
        self::assertLessThan(300, $response->getStatusCode(), $response->getReasonPhrase());
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
}
