<?php

use Psr7Middlewares\Middleware;

class ImageTransformerTest extends Base
{
    public function imagesProvider()
    {
        return [
            ['http://domain.com/my-images/small.image.png', 50, 50, ['small.' => 'resizeCrop,50,50']],
            ['http://domain.com/my-images/image.png', 512, 512, ['small.' => 'resizeCrop,50,50']],
            ['http://domain.com/small.image.png', 0, 0, ['small.' => 'resizeCrop,50,50']],
            ['http://domain.com/my-images/invalid.image.png', 0, 0, ['small.' => 'resizeCrop,50,50']],
            ['http://domain.com/my-images/resizeCrop,40,40.image.png', 0, 0, ['small.' => 'resizeCrop,50,50']],
        ];
    }

    /**
     * @dataProvider imagesProvider
     */
    public function testImageTransformer($url, $width, $height, $sizes)
    {
        //Use the images located in imagecow tests
        $storage = __DIR__.'/assets';

        $response = $this->execute(
            [
                Middleware::FormatNegotiator(),

                Middleware::ImageTransformer($sizes),

                Middleware::readResponse($storage)
                    ->basePath('/my-images'),
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
}
