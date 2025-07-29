<?php

namespace App\Libraries;

use App\Models\City;

class SearchBuilder
{
    protected SearchClass $search;
    public function __construct()
    {
        $this->search = new SearchClass();
    }

    public function whereType(string $type = 'all'): SearchBuilder
    {
        $this->search->setType($type);
        return $this;
    }

    public function whereContain(string $q): SearchBuilder
    {
        $this->search->setContains($q);
        return $this;
    }

    public function withRange(string|null $min, string|null $max): SearchBuilder
    {
        $range = null;

        if($min && $max){
            $range = ['min' => $min, 'max' => $max];
        }
        $this->search->setRange($range);
        return $this;
    }

    public function search()
    {
        return $this->search->search();
    }
}
