<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Services\CategoryService;
use App\Services\CityService;
use App\Services\EventService;
use App\Services\GroupTripService;
use App\Services\GuideService;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    protected CityService $cityService;
    protected EventService $eventService;
    protected GroupTripService $groupTripService;
    protected GuideService $guideService;
    protected CategoryService $categoryService;

    public function __construct(CityService $cityService, EventService $eventService, GroupTripService $groupTripService,GuideService $guideService, CategoryService $categoryService)
    {
        $this->cityService = $cityService;
        $this->eventService = $eventService;
        $this->groupTripService = $groupTripService;
        $this->guideService = $guideService;
        $this->categoryService = $categoryService;
    }

    public function index()
    {
        $topRatedEvents = $this->eventService->topRatedEvents();
        $latestEvents = $this->eventService->index();
        $citiesWithMostEvents =$this->cityService->citiesWithMostEvents();
        $topRatedGuides = $this->guideService->topRatedGuides();
        $latestGroupTrips = $this->groupTripService->index();
        $topRatedGroupTrips = $this->groupTripService->topRatedGroupTrips();
        $eventsByCategory = $this->categoryService->getAllCategoriesEvents();
        $guidesByCategory = $this->categoryService->getAllCategoriesGuides();
        $eventWithOffer = $this->eventService->eventsWithOffer();
        $groupTripsWithOffer = $this->groupTripService->groupTripsWithOffer();
        return response()->json(['latestGroupTrips' => $latestGroupTrips,
                                'topRatedGroupTrips' => $topRatedGroupTrips,
                                'topRatedEvents' => $topRatedEvents,
                                'latestEvents' => $latestEvents,
                                'citiesWithMostEvents' => $citiesWithMostEvents,
                                'topRatedGuides' => $topRatedGuides,
                                'eventsByCategory' => $eventsByCategory,
                                'guidesByCategory' => $guidesByCategory,
                                'groupTripsWithOffer' => $groupTripsWithOffer,
                                '$eventWithOffer' => $eventWithOffer]);

                        }
}
