<?php

namespace App\Libraries;

class CitySearchBuilder extends SearchBuilder
{
    /**
     * Create a new class instance.
     */
    public function __construct(CitySearchClass $searchClass)
    {
        parent::__construct($searchClass);
    }

    public function setLanguages(array $languages = []): CitySearchBuilder
    {
        $this->search->setLanguages($languages);
        return $this;
    }

    public function setCountries(array $countries = []): CitySearchBuilder
    {
        $this->search->setCountries($countries);
        return $this;
    }

    public function setStatus(string $status = 'active'): CitySearchBuilder
    {
        $this->search->setStatus($status);
        return $this;
    }
}
