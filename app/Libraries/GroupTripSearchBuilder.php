<?php

namespace App\Libraries;

class GroupTripSearchBuilder extends SearchBuilder
{
    /**
     * Create a new class instance.
     */
    public function __construct(GroupTripSearchClass $searchClass)
    {
        parent::__construct($searchClass);
    }

    public function setStatus(string $status = 'pending'): GroupTripSearchBuilder
    {
        $this->search->setStatus($status);
        return $this;
    }

    public function withOffer(bool $hasOffer = false): GroupTripSearchBuilder
    {
        $this->search->withOffer($hasOffer);
        return $this;
    }
}
