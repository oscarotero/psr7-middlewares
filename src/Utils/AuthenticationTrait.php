<?php declare(strict_types=1);

namespace Psr7Middlewares\Utils;

/**
 * Utilities used by authentication middlewares.
 */
trait AuthenticationTrait
{
    private $users;
    private $realm = 'Login';

    /**
     * Defines de users.
     *
     * @param array $users [username => password]
     */
    public function __construct(array $users)
    {
        $this->users = $users;
    }

    /**
     * Set the realm value.
     *
     * @param string $realm
     *
     * @return self
     */
    public function realm(string $realm): self
    {
        $this->realm = $realm;

        return $this;
    }
}
