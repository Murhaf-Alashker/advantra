<?php

namespace App\Libraries;

class EventSearchBuilder extends SearchBuilder
{
    /**
     * Create a new class instance.
     */
    public function __construct(EventSearchClass $searchClass)
    {
        parent::__construct($searchClass);
    }

    public function setCategories(array $categories = []): EventSearchBuilder
    {
        $this->search->setCategories($categories);
        return $this;
    }

    public function setCities(array $cities = []): EventSearchBuilder
    {
        $this->search->setCities($cities);
        return $this;
    }

    public function withOffer(bool $hasOffer = false): EventSearchBuilder
    {
        $this->search->withOffer($hasOffer);
        return $this;
    }

    public function setStatus(string $status = 'active'): EventSearchBuilder
    {
        $this->search->setStatus($status);
        return $this;
    }
}
