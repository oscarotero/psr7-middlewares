<?php
use Psr7Middlewares\Middleware;
use Psr7Middlewares\Middleware\LanguageNegotiator;

class MethodOverrideTest extends Base
{
    public function methodOverrideProvider()
    {
        return [
            ['GET', 'HEAD', 200],
            ['POST', 'HEAD', 405],
            ['GET', 'POST', 405],
        ];
    }

    /**
     * @dataProvider methodOverrideProvider
     */
    public function testLanguages($original, $overrided, $status)
    {
        $response = $this->dispatch(
            [
                Middleware::MethodOverride()
            ],
            $this->request('', ['X-Http-Method-Override' => $overrided])->withMethod($original),
            $this->response()
        );

        $this->assertEquals($status, $response->getStatusCode());
    }
}
