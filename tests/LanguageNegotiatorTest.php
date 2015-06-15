<?php
use Psr7Middlewares\Middleware;
use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\Response;
use Relay\Relay;

class LanguageNegotiatorTest extends PHPUnit_Framework_TestCase
{
    protected function makeTest($header, array $availables, $language)
    {
        $dispatcher = new Relay([
            Middleware::LanguageNegotiator($availables),
            function ($request, $response, $next) use ($language) {
                $this->assertEquals($language, $request->getAttribute('LANGUAGE'));

                $response->getBody()->write('Ok');

                return $response;
            },
        ]);

        $request = (new ServerRequest())->withHeader('Accept-Language', $header);
        $response = $dispatcher($request, new Response());

        $this->assertEquals('Ok', (string) $response->getBody());
    }

    public function testLanguages()
    {
        $this->makeTest(
            'gl-es, es;q=0.8, en;q=0.7',
            [],
            'gl'
        );

        $this->makeTest(
            'gl-es, es;q=0.8, en;q=0.7',
            ['es', 'en'],
            'es'
        );

        $this->makeTest(
            'gl-es, es;q=0.8, en;q=0.7',
            ['en', 'es'],
            'es'
        );

        $this->makeTest(
            '',
            [],
            null
        );

        $this->makeTest(
            '',
            ['es', 'en'],
            'es'
        );
        $this->makeTest(
            '',
            ['en', 'es'],
            'en'
        );
    }
}
