<?php

namespace App\Libraries;

class GuideSearchBuilder extends SearchBuilder
{
    /**
     * Create a new class instance.
     */
    public function __construct(GuideSearchClass $searchClass)
    {
        parent::__construct($searchClass);
    }

    public function setCities(array $cities = []): GuideSearchBuilder
    {
        $this->search->setCities($cities);
        return $this;
    }

    public function setLanguages(array $languages = []): GuideSearchBuilder
    {
        $this->search->setLanguages($languages);
        return $this;
    }

    public function setCategories(array $categories = []): GuideSearchBuilder
    {
        $this->search->setCategories($categories);
        return $this;
    }

    public function setStatus(string $status = 'active'): GuideSearchBuilder
    {
        $this->search->setStatus($status);
        return $this;
    }
}
