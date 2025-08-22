<?php

namespace App\Libraries;

use App\Models\City;

class SearchBuilder
{
    protected SearchClass $search;
    public function __construct(SearchClass $searchClass)
    {
        $this->search = $searchClass;
    }
    public function setContains(string $contains = null): SearchBuilder
    {
        $this->search->setContains($contains);
        return $this;
    }

    public function setPrice(string $minPrice = null,string $maxPrice = null): SearchBuilder
    {
        $this->search->setPrice($minPrice,$maxPrice);
        return $this;
    }

    public function setOrderBy(string $orderBy = null): SearchBuilder
    {
        $this->search->setOrderBy($orderBy);
        return $this;
    }

    public function setOrderType(string $orderType = null): SearchBuilder
    {
        $this->search->setOrderType($orderType);
        return $this;
    }

    public function search()
    {
        return $this->search->search();
    }
}
