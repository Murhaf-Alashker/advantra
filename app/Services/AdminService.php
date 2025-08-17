<?php

namespace App\Services;

use App\Http\Resources\UserResource;
use App\Libraries\ScheduleClass;
use App\Models\BusinessInfo;
use App\Models\City;
use App\Models\Event;
use App\Models\GroupTrip;
use App\Models\Guide;
use App\Models\Reservation;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use phpseclib3\Math\PrimeField\Integer;


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
            $data[$year.'/'.Carbon::parse($info->created_at)->format('M')] = $info;
        }
        $data[$year.'/'.Carbon::now()->format('M')] = ScheduleClass::getCurrentMonthInfo(Carbon::now()->month, Carbon::now()->year);
        ksort($data);
        return ['businessInfo' => $data];
    }

    public function topReservedUsers():array
    {
        $mostEventsBooker = $this->topReservedUsersByType('App\Models\Event');
        $mostGroupTripsBooker = $this->topReservedUsersByType('App\Models\GroupTrip');
        return ['mostEventsBooker' => $mostEventsBooker , 'mostGroupTripsBooker' => $mostGroupTripsBooker ];
    }

    private function topReservedUsersByType($type)
    {
        $name = ScheduleClass::modelToTable($type);
        return UserResource::collection(User::withCount([
            'reservations as '.$name => function ($query) use ($type) {
                $query->where('reservable_type', '=', $type);
            }
        ])->orderBy($name)->limit(5)->get());
    }

    public function businessPage(string $year):array
    {

        $businessInfo = $this->getBusinessInfo($year);
        $topReservedUsers = $this->topReservedUsers();
        $topRatedGroupTrips = ['topRatedGroupTrips' =>$this->groupTripService->topRatedGroupTrips()];
        $topRatedEvents = ['topRatedEvents' => $this->eventService->topRatedEvents()];
        return array_merge($businessInfo, $topRatedGroupTrips, $topRatedEvents, $topReservedUsers);

    }

    public function cityPage(int $page, string $orderBy, string|null $search):LengthAwarePaginator
    {
        $perPage = 5;
        $total = DB::selectOne('SELECT COUNT(*) as total FROM cities')->total;
        $offset = ($page - 1) * $perPage;
        $sql = $this->getInfoUsingRawSQL();
        $sql = str_replace(':order', $this->getOrderType($orderBy), $sql);
        $cities = DB::select($sql, [$search, $search, $search, $perPage, $offset]);
        return new LengthAwarePaginator(
            $cities,
            $total,
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );
    }

    public function getOrderType($type)
    {
        return match ($type) {
            'rate' => 'rating',
            'visitor' => 'monthly_visitors',
            'name' => 'c.name',
            'revenue' => 'current_revenue',
            'events' => 'events_count',
            'guides' => 'guides_count',
            default => 'name',
        };
    }

    public function getInfoUsingRawSQL(): string
    {
        return "Select
                    c.id,c.name,
                    COUNT(DISTINCT e.id) AS events_count,
                    COUNT(DISTINCT g.id) AS guides_count,

                    SUM(
                        CASE
                             WHEN MONTH(r.created_at) = MONTH(CURRENT_DATE())
                                 AND YEAR(r.created_at) = YEAR(CURRENT_DATE())
                            THEN r.ticket_price * r.basic_cost
                            ELSE 0
                        END
                    ) AS current_revenue,

                    SUM(
                        CASE
                             WHEN (
                                (MONTH(CURRENT_DATE()) = 1
                                 AND MONTH(r.created_at) = 12
                                 AND YEAR(r.created_at) = YEAR(CURRENT_DATE()) - 1)
                                OR
                                (MONTH(CURRENT_DATE()) <> 1
                                 AND MONTH(r.created_at) = MONTH(CURRENT_DATE()) - 1
                                 AND YEAR(r.created_at) = YEAR(CURRENT_DATE()))
                             )
                            THEN r.ticket_price * r.basic_cost
                            ELSE 0
                        END
                    ) AS last_revenue,

                    SUM(
                        CASE
                             WHEN MONTH(r.created_at) = MONTH(CURRENT_DATE())
                                 AND YEAR(r.created_at) = YEAR(CURRENT_DATE())
                            THEN r.tickets_count
                            ELSE 0
                        END
                    ) AS monthly_visitors,


                    COALESCE(
                        ROUND(
                            SUM(e.stars_count) / NULLIF(SUM(e.reviewer_count), 0),
                            1),
                    0) AS rating

                    FROM cities c
                        LEFT JOIN
                            guides g ON g.city_id = c.id
                        LEFT JOIN
                            events e ON e.city_id = c.id
                        LEFT JOIN
                            reservations r ON r.reservable_id = e.id AND r.reservable_type = 'App\\\Models\\\Event'
                    WHERE (? IS NULL OR c.name LIKE CONCAT('%', ?, '%') OR c.description LIKE CONCAT('%', ?, '%'))
                    GROUP BY c.id,c.name
                    ORDER BY :order DESC
                    LIMIT ?
                    OFFSET ?";
    }

    public function comparingSQL()
    {
        return DB::select("
    SELECT
        c.id,
        c.name,

        -- الأحداث و المرشدين
        COUNT(DISTINCT e.id) AS events_count,
        COUNT(DISTINCT g.id) AS guides_count,

        /* ====== القيم من الاستعلام الجديد ====== */
        SUM(
            CASE
                WHEN MONTH(r.created_at) = MONTH(CURRENT_DATE())
                     AND YEAR(r.created_at) = YEAR(CURRENT_DATE())
                THEN r.ticket_price * r.basic_cost
                ELSE 0
            END
        ) AS revenue_new,

        SUM(
            CASE
                WHEN (
                    (MONTH(CURRENT_DATE()) = 1
                     AND MONTH(r.created_at) = 12
                     AND YEAR(r.created_at) = YEAR(CURRENT_DATE()) - 1)
                    OR
                    (MONTH(CURRENT_DATE()) <> 1
                     AND MONTH(r.created_at) = MONTH(CURRENT_DATE()) - 1
                     AND YEAR(r.created_at) = YEAR(CURRENT_DATE()))
                )
                THEN r.ticket_price * r.basic_cost
                ELSE 0
            END
        ) AS last_revenue_new,

        SUM(
            CASE
                WHEN MONTH(r.created_at) = MONTH(CURRENT_DATE())
                     AND YEAR(r.created_at) = YEAR(CURRENT_DATE())
                THEN r.tickets_count
                ELSE 0
            END
        ) AS monthly_visitors_new,

        ROUND(
            SUM(e.stars_count) / NULLIF(SUM(e.reviewer_count), 0),
            1
        ) AS rating_new,

        /* ====== القيم من الاستعلام القديم (subqueries) ====== */
        (
            SELECT SUM(r2.ticket_price * r2.basic_cost)
            FROM reservations r2
            LEFT JOIN events ev2 ON ev2.id = r2.reservable_id
            WHERE ev2.city_id = c.id
              AND r2.reservable_type = 'App\\Models\\Event'
              AND MONTH(r2.created_at) = MONTH(CURRENT_DATE())
              AND YEAR(r2.created_at) = YEAR(CURRENT_DATE())
        ) AS revenue_old,

        (
            SELECT SUM(r3.ticket_price * r3.basic_cost)
            FROM reservations r3
            LEFT JOIN events ev3 ON ev3.id = r3.reservable_id
            WHERE ev3.city_id = c.id
              AND r3.reservable_type = 'App\\Models\\Event'
              AND (
                    (MONTH(CURRENT_DATE()) = 1
                     AND MONTH(r3.created_at) = 12
                     AND YEAR(r3.created_at) = YEAR(CURRENT_DATE()) - 1)
                    OR
                    (MONTH(CURRENT_DATE()) <> 1
                     AND MONTH(r3.created_at) = MONTH(CURRENT_DATE()) - 1
                     AND YEAR(r3.created_at) = YEAR(CURRENT_DATE()))
              )
        ) AS last_revenue_old,

        (
            SELECT SUM(r4.tickets_count)
            FROM reservations r4
            LEFT JOIN events ev4 ON ev4.id = r4.reservable_id
            WHERE ev4.city_id = c.id
              AND r4.reservable_type = 'App\\Models\\Event'
              AND MONTH(r4.created_at) = MONTH(CURRENT_DATE())
              AND YEAR(r4.created_at) = YEAR(CURRENT_DATE())
        ) AS monthly_visitors_old,

        (
            SELECT ROUND(SUM(ev5.stars_count) / NULLIF(SUM(ev5.reviewer_count), 0), 1)
            FROM events ev5
            WHERE ev5.city_id = c.id
        ) AS rating_old

    FROM cities c
    LEFT JOIN guides g ON g.city_id = c.id
    LEFT JOIN events e ON e.city_id = c.id
    LEFT JOIN reservations r
           ON r.reservable_id = e.id
          AND r.reservable_type = 'App\\Models\\Event'
    GROUP BY c.id, c.name
");

    }

}
