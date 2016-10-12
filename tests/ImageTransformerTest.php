<?php

use Psr7Middlewares\Middleware;
use MatthiasMullie\Scrapbook\Adapters\MemoryStore;
use MatthiasMullie\Scrapbook\Psr6\Pool;

class ImageTransformerTest extends Base
{
    public function imagesProvider()
    {
        return [
            ['http://domain.com/small.image.png', 50, 50, ['small.' => 'resizeCrop,50,50'], true],
            ['http://domain.com/image.png', 512, 512, ['small.' => 'resizeCrop,50,50']],
            ['http://domain.com/images/small.image.png', 0, 0, ['small.' => 'resizeCrop,50,50']],
            ['http://domain.com/invalid.image.png', 0, 0, ['small.' => 'resizeCrop,50,50']],
            ['http://domain.com/resizeCrop,40,40.image.png', 0, 0, ['small.' => 'resizeCrop,50,50']],
        ];
    }

    /**
     * @dataProvider imagesProvider
     */
    public function testImageTransformer($url, $width, $height, $sizes, $cached = false)
    {
        //Use the images located in imagecow tests
        $usedBefore = 0;
        $usedAfter = 0;
        $cache = new Pool(new MemoryStore());

        for ($i = 0; $i < 2; ++$i) {
            $response = $this->execute(
                [
                    Middleware::FormatNegotiator(),

                    function ($request, $response, $next) use (&$usedBefore) {
                        ++$usedBefore;

                        return $next($request, $response);
                    },

                    Middleware::ImageTransformer($sizes)
                        ->cache($cache),

                    function ($request, $response, $next) use (&$usedAfter) {
                        ++$usedAfter;

                        return $next($request, $response);
                    },

                    Middleware::readResponse(__DIR__.'/assets'),
                ],
                $url
            );

            if ($width) {
                $info = getimagesizefromstring((string) $response->getbody());
                $this->assertEquals($width, $info[0]);
                $this->assertEquals($height, $info[1]);
            } else {
                $this->assertEmpty((string) $response->getbody());
            }
        }

        if ($width) {
            $this->assertSame(2, $usedBefore);
            $this->assertSame($cached ? 1 : 2, $usedAfter);
        }
    }

    public function urlsProvider()
    {
        return [
            ['images/picture1.jpg', 'small.', 'public/images/small.picture1.jpg'],
            ['images/picture1.jpg', 'big.', 'public/images/big.picture1.jpg'],
            ['images/picture1.jpg', 'normal.', false],
            ['images/avatar/picture1.jpg', 'normal.', 'public/images/avatar/normal.picture1.jpg'],
        ];
    }

    /**
     * @dataProvider urlsProvider
     */
    public function _testUrlGenerator($path, $transform, $result)
    {
        try {
            $response = $this->execute([
                Middleware::basePath('public'),
                Middleware::FormatNegotiator(),
                Middleware::ImageTransformer([
                    'small.' => 'resizeCrop,300,300',
                    'big.' => 'resizeCrop,3000,3000',
                    'avatar/normal.' => 'resizeCrop,32,32',
                ]),
                function ($request, $response, $next) use ($path, $transform) {
                    $generator = Middleware\ImageTransformer::getGenerator($request);

                    $response->getBody()->write($generator($path, $transform));

                    return $next($request, $response);
                },
            ]);
        } catch (\Exception $error) {
            $response = false;
        }

        if ($result === false) {
            $this->assertFalse($response);
        } else {
            $this->assertNotFalse($response);
            $this->assertEquals($result, (string) $response->getbody());
        }
    }
}
