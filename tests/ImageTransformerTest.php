<?php

use Psr7Middlewares\Middleware;

class ImageTransformerTest extends Base
{
    public function imagesProvider()
    {
        return [
            ['http://domain.com/my-images/small.image.png', 50, 50],
            ['http://domain.com/my-images/image.png', 0, 0],
            ['http://domain.com/small.image.png', 0, 0],
        ];
    }

    /**
     * @dataProvider imagesProvider
     */
    public function testImageTransformer($url, $width, $height)
    {
        //Use the images located in imagecow tests
        $storage = __DIR__.'/assets';

        $response = $this->execute(
            [
                Middleware::FormatNegotiator(),

                Middleware::ImageTransformer()
                    ->storage($storage)
                    ->basePath('/my-images')
                    ->sizes(['small' => 'resizeCrop,50,50']),
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
