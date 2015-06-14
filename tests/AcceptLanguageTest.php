<?php
use Psr7Middlewares\Middleware;
use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\Response;
use Relay\Relay;

class AcceptLanguageTest extends PHPUnit_Framework_TestCase
{
    protected function makeTest($header, array $availables, array $client_languages, $client_preferred_language)
    {
        $dispatcher = new Relay([
            Middleware::AcceptLanguage($availables),
            function ($request, $response, $next) use ($client_languages, $client_preferred_language) {
                $this->assertEquals($client_languages, $request->getAttribute('ACCEPT_LANGUAGE'));
                $this->assertEquals($client_preferred_language, $request->getAttribute('PREFERRED_LANGUAGE'));

                $response->getBody()->write('Ok');

                return $response;
            }
        ]);

        $request = (new ServerRequest())
            ->withHeader('Accept-Language', $header);

        $response = $dispatcher($request, new Response());

        $this->assertEquals('Ok', (string) $response->getBody());
    }

    public function testLanguages()
    {
        $this->makeTest('gl-es, es;q=0.8, en;q=0.7', [], ['gl' => 1, 'es' => 0.8, 'en' => 0.7], 'gl');
        $this->makeTest('gl-es, es;q=0.8, en;q=0.7', ['es', 'en'], ['gl' => 1, 'es' => 0.8, 'en' => 0.7], 'es');
        $this->makeTest('gl-es, es;q=0.8, en;q=0.7', ['en', 'es'], ['gl' => 1, 'es' => 0.8, 'en' => 0.7], 'es');

        $this->makeTest('', [], [], null);
        $this->makeTest('', ['es', 'en'], [], 'es');
        $this->makeTest('', ['en', 'es'], [], 'en');
    }
}
