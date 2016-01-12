<?php

namespace Psr7Middlewares\Utils;

use RuntimeException;

/**
 * Trait used by all middlewares that needs encrypt/decrypt functions.
 */
trait CryptTrait
{
    private $key;
    private $authentication;

    /**
     * Set the keys to encrypt and authenticate.
     * 
     * @param string $key
     *
     * @return self
     */
    public function key($key)
    {
        $this->key = self::hkdf($key, 'KeyForEncryption');
        $this->authentication = self::hkdf($key, 'KeyForAuthentication');

        return $this;
    }

    /**
     * Encrypt the given value.
     *
     * @param string $value
     * 
     * @return string
     */
    private function encrypt($value)
    {
        if (empty($this->key) || empty($this->authentication)) {
            throw new RuntimeException('No crypt keys provided');
        }

        $iv = mcrypt_create_iv(16, MCRYPT_DEV_URANDOM);
        $cipher = mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $this->key, json_encode($value), 'ctr', $iv);
        $hmac = hash_hmac('sha256', $iv.$cipher, $this->authentication, true);

        return base64_encode($hmac.$iv.$cipher);
    }

    /**
     * Decrypt the given value.
     *
     * @param string $value
     * 
     * @return string
     */
    private function decrypt($value)
    {
        if (empty($this->key) || empty($this->authentication)) {
            throw new RuntimeException('No crypt keys provided');
        }

        $decoded = base64_decode($value);
        $hmac = mb_substr($decoded, 0, 32, '8bit');
        $iv = mb_substr($decoded, 32, 16, '8bit');
        $cipher = mb_substr($decoded, 48, null, '8bit');
        $calculated = hash_hmac('sha256', $iv.$cipher, $this->authentication, true);

        if (Helpers::hashEquals($hmac, $calculated)) {
            $value = rtrim(mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $this->key, $cipher, 'ctr', $iv), "\0");

            return json_decode($value, true);
        }
    }

    /**
     * Get derived key
     * http://tools.ietf.org/html/rfc5869.
     * 
     * @param string $ikm  Initial Keying Material
     * @param string $info What sort of key are we deriving?
     * 
     * @return string
     */
    private static function hkdf($ikm, $info = '')
    {
        $salt = str_repeat("\x00", 32);
        $prk = hash_hmac('sha256', $ikm, $salt, true);

        $t = $last_block = '';
        $length = 0;

        for ($block_index = 1; $length < 16; ++$block_index) {
            $last_block = hash_hmac('sha256', $last_block.$info.chr($block_index), $prk, true);
            $t .= $last_block;
            $length = mb_strlen($t, '8bit');
        }

        return mb_substr($t, 0, 16, '8bit');
    }
}
