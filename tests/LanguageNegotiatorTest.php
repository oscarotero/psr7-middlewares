<?php

use Psr7Middlewares\Middleware;
use Psr7Middlewares\Middleware\LanguageNegotiator;

class LanguageNegotiatorTest extends Base
{
    public function languagesProvider()
    {
        return [
            [
                'gl-es, es;q=0.8, en;q=0.7',
                ['gl'],
                'gl',
            ], [
                'gl-es, es;q=0.8, en;q=0.7',
                ['es', 'en'],
                'es',
            ], [
                'gl-es, es;q=0.8, en;q=0.7',
                ['en', 'es'],
                'es',
            ], [
                '',
                [],
                null,
            ], [
                '',
                ['es', 'en'],
                'es',
            ], [
                '',
                ['en', 'es'],
                'en',
            ],
        ];
    }

    /**
     * @dataProvider languagesProvider
     */
    public function testLanguages($acceptLanguage, array $languages, $language)
    {
        $response = $this->execute(
            [
                Middleware::LanguageNegotiator($languages),
                function ($request, $response, $next) use ($language) {
                    $response->getBody()->write(LanguageNegotiator::getLanguage($request));

                    return $next($request, $response);
                },
            ],
            '',
            ['Accept-Language' => $acceptLanguage]
        );

        $this->assertEquals($language, (string) $response->getBody());
        $this->assertEquals($language, (string) $response->getHeaderLine('Content-Language'));
    }

    public function languagesPathProvider()
    {
        return [
            [
                '',
                '',
                'gl-es, es;q=0.8, en;q=0.7',
                ['gl'],
                '/gl/',
                '',
            ],
            [
                '',
                '',
                'gl-es, es;q=0.8, en;q=0.7',
                ['gl'],
                '/gl/',
                '',
            ],
            [
                '/web',
                '/web/ES',
                'gl-es, es;q=0.8, en;q=0.7',
                ['gl', 'es'],
                '',
                'es /',
            ],
            [
                '',
                'es',
                'gl-es, es;q=0.8, en;q=0.7',
                ['gl', 'es'],
                '',
                'es /',
            ],
            [
                '',
                '/es/ola',
                'gl-es, es;q=0.8, en;q=0.7',
                ['gl', 'es'],
                '',
                'es /ola',
            ],
            [
                '',
                '/mola/ola',
                'gl-es, es;q=0.8, en;q=0.7',
                ['gl', 'es'],
                '/es/mola/ola',
                '',
            ],
            [
                '/mola',
                '/mola/ola',
                'gl-es, es;q=0.8, en;q=0.7',
                ['gl', 'es'],
                '/mola/es/ola',
                '',
            ],
        ];
    }

    /**
     * @dataProvider languagesPathProvider
     */
    public function testLanguagesPath($basePath, $path, $acceptLanguage, array $languages, $location, $body)
    {
        $response = $this->execute(
            [
                Middleware::basePath($basePath),

                Middleware::LanguageNegotiator($languages)
                    ->usePath()
                    ->redirect(),

                function ($request, $response, $next) {
                    $response->getBody()->write(LanguageNegotiator::getLanguage($request).' '.$request->getUri()->getPath());

                    return $next($request, $response);
                },
            ],
            $path,
            ['Accept-Language' => $acceptLanguage]
        );

        $this->assertEquals($body, (string) $response->getBody());

        if (empty($location)) {
            $this->assertEquals(200, $response->getStatusCode());
            $this->assertEmpty($response->getHeaderLine('Location'));
        } else {
            $this->assertEquals(302, $response->getStatusCode());
            $this->assertEquals($location, $response->getHeaderLine('Location'));
        }
    }
}
