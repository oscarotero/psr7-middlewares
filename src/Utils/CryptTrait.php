<?php

namespace Psr7Middlewares\Utils;

use phpseclib\Crypt\AES;

/**
 * Trait used by all middlewares that needs encrypt/decrypt functions.
 */
trait CryptTrait
{
    protected $cipher;

    /**
     * Set the key.
     * 
     * @param string $key
     *
     * @return self
     */
    public function key($key)
    {
        $this->cipher = new AES();
        $this->cipher->setKey($key);

        return $this;
    }

    /**
     * Encrypt the given value.
     *
     * @param string $value
     * 
     * @return string
     */
    protected function encrypt($value)
    {
        if ($this->cipher) {
            return bin2hex($this->cipher->encrypt($value));
        }

        return $value;
    }

    /**
     * Decrypt the given value.
     *
     * @param string $value
     * 
     * @return string
     */
    protected function decrypt($value)
    {
        if ($this->cipher) {
            return $this->cipher->decrypt(hex2bin($value));
        }

        return $value;
    }
}
