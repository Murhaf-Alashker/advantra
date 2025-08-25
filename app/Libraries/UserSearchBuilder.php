<?php

namespace App\Libraries;

class UserSearchBuilder extends SearchBuilder
{
    /**
     * Create a new class instance.
     */
    public function __construct(UserSearchClass $userSearchClass)
    {
        parent::__construct($userSearchClass);
    }

    public function setStatus(string $status = 'active'): UserSearchBuilder
    {
        $this->search->setStatus($status);
        return $this;
    }
}
