<?php

use Psr7Middlewares\Middleware;
use MatthiasMullie\Scrapbook\Adapters\MemoryStore;
use MatthiasMullie\Scrapbook\Psr6\Pool;

class ImageTransformerTest extends Base
{
    public function imagesProvider()
    {
        return [
            ['http://domain.com/my-images/small.image.png', 50, 50, ['small.' => 'resizeCrop,50,50'], true],
            ['http://domain.com/my-images/image.png', 512, 512, ['small.' => 'resizeCrop,50,50']],
            ['http://domain.com/small.image.png', 0, 0, ['small.' => 'resizeCrop,50,50']],
            ['http://domain.com/my-images/invalid.image.png', 0, 0, ['small.' => 'resizeCrop,50,50']],
            ['http://domain.com/my-images/resizeCrop,40,40.image.png', 0, 0, ['small.' => 'resizeCrop,50,50']],
            ['http://domain.com/my-images/small.image.png', 50, 50, ['my-images/small.' => 'resizeCrop,50,50'], true],
        ];
    }

    /**
     * @dataProvider imagesProvider
     */
    public function testImageTransformer($url, $width, $height, $sizes, $cached = false)
    {
        //Use the images located in imagecow tests
        $storage = __DIR__.'/assets';
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

                    Middleware::ImageTransformer($sizes)->cache($cache),

                    Middleware::readResponse($storage)
                        ->basePath('/my-images'),

                    function ($request, $response, $next) use (&$usedAfter) {
                        ++$usedAfter;

                        return $next($request, $response);
                    },
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
}
