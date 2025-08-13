<?php

namespace App\Services;

use App\Http\Resources\UserResource;
use App\Models\BusinessInfo;
use App\Models\Event;
use App\Models\GroupTrip;
use App\Models\Reservation;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;


class AdminService
{
    /**
     * Create a new class instance.
     */
    protected EventService $eventService;
    protected UserService $userService;
    protected GroupTripService $groupTripService;
    protected CityService $cityService;
    protected GuideService $guideService;

    public function __construct(EventService $eventService, UserService $userService, GroupTripService $groupTripService,CityService $cityService, GuideService $guideService)
    {
        $this->eventService = $eventService;
        $this->userService = $userService;
        $this->groupTripService = $groupTripService;
        $this->cityService = $cityService;
        $this->guideService = $guideService;
    }

    public function getBusinessInfo (string $year):array
    {
        $data = [];
        $infos = BusinessInfo::whereYear('created_at', $year)->orderBy('created_at')->get();
        foreach ($infos as $info) {
            $info->total_expenses = $info->total_income - $info->total_profit;
            $data[$year.'/'.Carbon::parse($info->created_at)->format('m')] = $info;
        }
        $data[$year.'/'.Carbon::now()->format('M')] = $this->getCurrentMonthInfo();
        ksort($data);
        return ['businessInfo' => $data];
    }

    public function getCurrentMonthInfo():\stdClass
    {
        $data = ['total_profit' => 0, 'total_income' => 0, 'total_expenses' => 0, 'events_reserved_tickets' =>0, 'group_trip_reserved_tickets' => 0];
        $month = Carbon::now()->month;
        $groups = [];
        $reservations = Reservation::whereMonth('created_at', $month)->whereYear('created_at', Carbon::now()->year)->orderBy('created_at')->get();
        foreach ($reservations as $reservation) {

            $data['total_income'] += $reservation->ticket_price * $reservation->tickets_count;
            $data['total_expenses'] += $reservation->basic_cost * $reservation->tickets_count ;

            $type = $this->modelToTable($reservation->reservable_type);
            if($type){
                $data[$type]+= $reservation->tickets_count;
            }
        }
        $data['total_profit'] = $data['total_income'] - $data['total_expenses'];

        return (object) $data;


    }

    public function topReservedUsers():array
    {
        $mostEventsBooked = $this->topReservedUsersByType('App\Models\Event');
        $mostGroupTripsBooked = $this->topReservedUsersByType('App\Models\GroupTrip');
        return ['mostEventsBooked' => $mostEventsBooked , 'mostGroupTripsBooked' => $mostGroupTripsBooked ];
    }

    private function topReservedUsersByType($type)
    {
        $name = $this->modelToTable($type);
        return UserResource::collection(User::withCount([
            'reservations as '.$name => function ($query) use ($type) {
                $query->where('reservable_type', '=', $type);
            }
        ])->orderBy($name)->limit(5)->get());
    }

    private function modelToTable($modelClass):string
    {
        return match ($modelClass) {
            'App\Models\GroupTrip' => 'group_trip_reserved_tickets',
            'App\Models\Event' => 'events_reserved_tickets',
            default => null,
        };
    }

    public function businessPage(string $year):array
    {

        $businessInfo = $this->getBusinessInfo($year);
        $topReservedUsers = $this->topReservedUsers();
        $topRatedGroupTrips = ['topRatedGroupTrips' =>$this->groupTripService->topRatedGroupTrips()];
        $topRatedEvents = ['topRatedEvents' => $this->eventService->topRatedEvents()];
        return array_merge($businessInfo, $topRatedGroupTrips, $topRatedEvents, $topReservedUsers);

    }

}
