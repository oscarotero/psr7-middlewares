<?php
use Psr7Middlewares\Middleware;
use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\Response;
use Relay\Relay;

class AcceptLanguageTest extends PHPUnit_Framework_TestCase
{
    protected function makeTest($header, array $availables, array $accept_language, $preferred_language)
    {
        $dispatcher = new Relay([
            Middleware::AcceptLanguage($availables),
            function ($request, $response, $next) use ($accept_language, $preferred_language) {
                $this->assertEquals($accept_language, $request->getAttribute('ACCEPT_LANGUAGE'));
                $this->assertEquals($preferred_language, $request->getAttribute('PREFERRED_LANGUAGE'));

                $response->getBody()->write('Ok');

                return $response;
            },
        ]);

        $request = (new ServerRequest())
            ->withHeader('Accept-Language', $header);

        $response = $dispatcher($request, new Response());

        $this->assertEquals('Ok', (string) $response->getBody());
    }

    public function testLanguages()
    {
        $this->makeTest(
            'gl-es, es;q=0.8, en;q=0.7',
            [],
            ['gl' => 1, 'es' => 0.8, 'en' => 0.7],
            'gl'
        );

        $this->makeTest(
            'gl-es, es;q=0.8, en;q=0.7',
            ['es', 'en'],
            ['gl' => 1, 'es' => 0.8, 'en' => 0.7],
            'es'
        );

        $this->makeTest(
            'gl-es, es;q=0.8, en;q=0.7',
            ['en', 'es'],
            ['gl' => 1, 'es' => 0.8, 'en' => 0.7],
            'es'
        );

        $this->makeTest(
            '',
            [],
            [],
            null
        );

        $this->makeTest(
            '',
            ['es', 'en'],
            [],
            'es'
        );
        $this->makeTest(
            '',
            ['en', 'es'],
            [],
            'en'
        );
    }
}
