<?php

use Psr7Middlewares\Middleware;

class ImageTransformerTest extends Base
{
    public function imagesProvider()
    {
        return [
            ['http://domain.com/my-images/small.image.jpg', 50, 50],
            ['http://domain.com/my-images/image.jpg', 0, 0],
            ['http://domain.com/small.image.jpg', 0, 0],
        ];
    }

    /**
     * @dataProvider imagesProvider
     */
    public function testImageTransformer($url, $width, $height)
    {
        //Use the images located in imagecow tests
        $storage = dirname(__DIR__).'/vendor/imagecow/imagecow/tests/images';

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
