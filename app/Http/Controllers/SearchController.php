<?php

namespace App\Http\Controllers;

use App\Http\Requests\SearchRequest;
use App\Libraries\CitySearchBuilder;
use App\Libraries\CitySearchClass;
use App\Libraries\EventSearchBuilder;
use App\Libraries\EventSearchClass;
use App\Libraries\GroupTripSearchBuilder;
use App\Libraries\GroupTripSearchClass;
use App\Libraries\GuideSearchBuilder;
use App\Libraries\GuideSearchClass;

class SearchController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function search(SearchRequest $request)
    {
        $validated = $request->validated();
        $types = $validated ['types'];
        $result = [];
        foreach ($types as $type) {
            $result[$type] = $this->{$type.'Search'}($validated);
        }
        return $result;
    }

    private function eventSearch(array $data)
    {

        $events = $this->build($data,'event');

        return $events->setCategories($data['categories'] ?? [])
                      ->setCities($data['cities'] ?? [])
                      ->setStatus($data['status'] ?? 'active')
                      ->withOffer($data['with_offer'] ?? false)
                      ->search();
    }

    private function guideSearch(array $data)
    {
        $events = $this->build($data,'guide');
        return $events->setCities($data['cities'] ?? [])
                      ->setLanguages($data['languages'] ?? [])
                      ->setCategories($data['categories'] ?? [])
                      ->setStatus($data['status'] ?? 'active')
                      ->search();
    }

    private function citySearch(array $data)
    {
        $cities = $this->build($data,'city');
        return $cities->setLanguages($data['languages'] ?? [])
                      ->setStatus($data['status'] ?? 'active')
                      ->setCountries($data['countries'] ?? [])
                      ->search();
    }

    private function groupTripSearch(array $data)
    {
        $groups = $this->build($data,'groupTrip');
        return $groups->setStatus($data['status'] ?? 'pending')
                      ->withOffer($data['with_offer'] ?? false)
                      ->search();
    }

    private function build(array $data,string $type)
    {
        $builder = $this->getBuilder($type);
        return $builder->setContains($data['contains'] ?? '')
                       ->setPrice($data['minPrice'] ?? null,$data['maxPrice'] ?? null)
                       ->setOrderBy($data['orderBy'] ?? 'created_at')
                       ->setOrderType($data['order_type'] ?? 'DESC');
    }

    private function getBuilder($type)
    {
        return match ($type){
            'event' => new EventSearchBuilder(new EventSearchClass()),
            'city' => new CitySearchBuilder(new CitySearchClass()),
            'guide' => new GuideSearchBuilder(new GuideSearchClass()),
            'groupTrip' => new GroupTripSearchBuilder(new GroupTripSearchClass()),
        };
    }
}
