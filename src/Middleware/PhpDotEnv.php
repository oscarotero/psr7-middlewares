<?php

namespace Psr7Middlewares\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Middleware to load a .env into PHP Environment Variables
 *
 * @author Robert Schönthal <robert.schoenthal@gmail.com>
 */
class PhpDotEnv
{
    /**
     * @var string
     */
    private $dir;
    /**
     * @var string
     */
    private $filename;

    public function __construct($dir, $filename = '.env')
    {
        $this->dir = $dir;
        $this->filename = $filename;
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        $this->loadEnvironment();

        return $next($request, $response, $next);
    }

    private function loadEnvironment()
    {
        if (!file_exists(realpath($this->dir) . '/' . $this->filename)) {
            return false;
        }

        if (class_exists('Dotenv\Dotenv')) { //2.x branch
            $dotenv = new \Dotenv\Dotenv($this->dir, $this->filename);
            $dotenv->load();

            return true;
        } elseif (class_exists('DotEnv')) { //1.x branch
            \Dotenv::load($this->dir, $this->filename);

            return true;
        }

        throw new \RuntimeException('no suitable .env loader found');
    }
}
