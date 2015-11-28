<?php

namespace Psr7Middlewares\Utils;

use RuntimeException;

/**
 * Trait used by all middlewares with encrypt/decrypt functions
 * Most of code is from https://github.com/illuminate/encryption.
 */
trait CryptTrait
{
    protected $cipher;
    protected $key;

    /**
     * Set the key and cipher used by the crypt.
     * 
     * @param string $key
     * @param string $cipher
     *
     * @return self
     */
    public function crypt($key, $cipher = 'AES-128-CBC')
    {
        $length = mb_strlen($key, '8bit');

        if (!(($cipher === 'AES-128-CBC' && $length === 16) || ($cipher === 'AES-256-CBC' && $length === 32))) {
            throw new RuntimeException('The only supported ciphers are AES-128-CBC and AES-256-CBC with the correct key lengths.');
        }

        $this->key = $key;
        $this->cipher = $cipher;

        return $this;
    }

    /**
     * Generates the default cipher and key.
     */
    protected function generateCryptKey()
    {
        $this->crypt(substr(md5(__DIR__), 0, 16));
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
        $iv = random_bytes(16);
        $value = openssl_encrypt(serialize($value), $this->cipher, $this->key, 0, $iv);

        if ($value === false) {
            throw new RuntimeException('Could not encrypt the data.');
        }

        $iv = base64_encode($iv);
        $mac = hash_hmac('sha256', $iv.$value, $this->key);

        return base64_encode(json_encode(compact('iv', 'value', 'mac')));
    }

    /**
     * Decrypt the given value.
     *
     * @param string $payload
     * 
     * @return string
     */
    public function decrypt($payload)
    {
        $payload = json_decode(base64_decode($payload), true);

        if (!$payload || $this->invalidPayload($payload)) {
            throw new RuntimeException('The payload is invalid.');
        }

        if (!$this->validMac($payload)) {
            throw new RuntimeException('The MAC is invalid.');
        }

        $iv = base64_decode($payload['iv']);
        $decrypted = openssl_decrypt($payload['value'], $this->cipher, $this->key, 0, $iv);

        if ($decrypted === false) {
            throw new RuntimeException('Could not decrypt the data.');
        }

        return unserialize($decrypted);
    }

    /**
     * Create a MAC for the given value.
     *
     * @param string $iv
     * @param string $value
     * 
     * @return string
     */
    protected function hash($iv, $value)
    {
        return hash_hmac('sha256', $iv.$value, $this->key);
    }

    /**
     * Verify that the encryption payload is valid.
     *
     * @param array|mixed $data
     * 
     * @return bool
     */
    protected function invalidPayload($data)
    {
        return !is_array($data) || !isset($data['iv']) || !isset($data['value']) || !isset($data['mac']);
    }

    /**
     * Determine if the MAC for the given payload is valid.
     *
     * @param array $payload
     * 
     * @throws \RuntimeException
     *
     * @return bool
     */
    protected function validMac(array $payload)
    {
        $bytes = random_bytes(16);
        $calcMac = hash_hmac('sha256', $this->hash($payload['iv'], $payload['value']), $bytes, true);

        return hash_equals(hash_hmac('sha256', $payload['mac'], $bytes, true), $calcMac);
    }
}
