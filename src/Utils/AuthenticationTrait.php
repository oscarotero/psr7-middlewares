<?php

namespace Psr7Middlewares\Utils;

/**
 * Utilities used by authentication middlewares.
 */
trait AuthenticationTrait
{
    protected $users = [];
    protected $realm = 'Login';

    /**
     * Constructor. Defines de users.
     *
     * @param array|null $users [username => password]
     */
    public function __construct(array $users = null)
    {
        if ($users !== null) {
            $this->users($users);
        }
    }

    /**
     * Configure the users and passwords.
     *
     * @param array $users [username => password]
     *
     * @return self
     */
    public function users(array $users)
    {
        $this->users = $users;

        return $this;
    }

    /**
     * Set the realm value.
     *
     * @param string $realm
     *
     * @return self
     */
    public function realm($realm)
    {
        $this->realm = $realm;

        return $this;
    }
}
