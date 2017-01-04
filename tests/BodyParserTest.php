<?php

use Psr7Middlewares\Transformers\BodyParser;

/**
 * @covers \Psr7Middlewares\Transformers\BodyParser
 */
class BodyParserTest extends Base
{
    public function testDefaultJsonParser()
    {
        $expected = [
            'foo' => 'bar',
            'fiz' => [
                'buz' => true,
            ],
        ];

        $actual = (new BodyParser())->json(
            $this->stream(json_encode($expected))
        );

        self::assertArraySubset($expected, $actual);
    }

    public function testJsonObjectParser()
    {
        $expected = [
            'foo' => 'bar',
            'fiz' => [
                'buz' => true,
            ],
        ];

        $actual = (new BodyParser(['forceArray' => false]))->json(
            $this->stream(json_encode($expected))
        );

        self::assertInstanceOf(\stdClass::class, $actual);
        self::assertObjectHasAttribute('foo', $actual);
        self::assertEquals('bar', $actual->foo);
        self::assertObjectHasAttribute('fiz', $actual);
        self::assertInstanceOf(\stdClass::class, $actual->fiz);
        self::assertObjectHasAttribute('buz', $actual->fiz);
        self::assertTrue($actual->fiz->buz);
    }

    public function testFormUrlEncoded()
    {
        $expected = [
            'foo' => 'bar',
            'fiz' => [
                'fiz' => 'buz',
                'buz' => true,
            ],
        ];

        $actual = (new BodyParser())->urlencode(
            $this->stream(http_build_query($expected))
        );

        self::assertArraySubset($expected, $actual);
    }

    public function testEmptyFormUrlEncoded()
    {
        $actual = (new BodyParser())->urlencode(
            $this->stream('')
        );

        self::assertInternalType('array', $actual);
    }

    public function testCsv()
    {
        $actual = (new BodyParser())->csv(
            $this->stream(<<<'CSV'
col1,col2,col3,col4
foo,bar,fiz,buz
CSV
)
        );

        self::assertArraySubset(
            [
                [
                    'col1',
                    'col2',
                    'col3',
                    'col4',
                ],
                [
                    'foo',
                    'bar',
                    'fiz',
                    'buz',
                ],
            ],
            $actual
        );
    }

    public function testEmptyCsv()
    {
        $actual = (new BodyParser())->csv(
            $this->stream('')
        );

        self::assertInternalType('array', $actual);
    }
}
