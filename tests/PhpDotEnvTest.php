<?php


class PhpDotEnvTest extends Base
{
    private static $dir;
    private static $filename;

    public static function setUpBeforeClass()
    {
        $file = tempnam(sys_get_temp_dir(), __CLASS__);

        self::$filename = basename($file);
        self::$dir = sys_get_temp_dir();

        file_put_contents($file, 'FOO=bar');
    }

    public static function tearDownAfterClass()
    {
        @unlink(self::$dir.'/'.self::$filename);
    }

    public function testItDoesNothingIfThereIsNoEnvFile()
    {
        $middleware = new \Psr7Middlewares\Middleware\PhpDotEnv(self::$dir, 'foo');

        $this->execute([$middleware]);

        $this->assertFalse(getenv('FOO'));
    }

    public function testItLoadsEnvironmentVariablesFromAFile()
    {
        $middleware = new \Psr7Middlewares\Middleware\PhpDotEnv(self::$dir, self::$filename);

        $this->execute([$middleware]);

        $this->assertEquals('bar', getenv('FOO'));
    }
}
