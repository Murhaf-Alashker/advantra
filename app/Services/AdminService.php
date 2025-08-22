<?php

namespace App\Services;

use App\Http\Resources\UserResource;
use App\Libraries\ScheduleClass;
use App\Mail\SendGiftMail;
use App\Models\BusinessInfo;
use App\Models\Category;
use App\Models\City;
use App\Models\Country;
use App\Models\Language;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;


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
        $infos = BusinessInfo::whereYear('created_at', $year)
            ->orWhere(function ($query) use ($year) {
                $query->whereYear('created_at', $year - 1)->whereMonth('created_at', 12);
            })
            ->orderBy('created_at','DESC')->get();
        foreach ($infos as $info) {
            $info->total_expenses = $info->total_income - $info->total_profit;
            $info->month_name = Carbon::parse($info->created_at)->year.'/'.Carbon::parse($info->created_at)->format('M');
            $data[] = $info;
        }
        $info = ScheduleClass::getCurrentMonthInfo(Carbon::now()->month, Carbon::now()->year);
        $info->month_name = $year.'/'.Carbon::now()->format('M');
        $num = array_unshift($data,$info);
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
        $users = User::withSum([
            'reservations as group_trip_reserved_tickets' => function ($query) {
                $query->where('reservable_type', '=', 'App\Models\GroupTrip')
                    ->whereMonth('created_at', '=', Carbon::now()->subMonth()->month)
                    ->whereYear('created_at', '=', Carbon::now()->year);
            }
        ],'tickets_count')->withSum([
            'reservations as events_reserved_tickets' => function ($query) {
                $query->where('reservable_type', '=', 'App\Models\Event')
                    ->whereMonth('created_at', '=', Carbon::now()->subMonth()->month)
                    ->whereYear('created_at', '=', Carbon::now()->year);
            }
        ],'tickets_count')
            ->orderBy($name,'DESC')->limit(5)->get();

        foreach ($users as $user){
            $user->gifted_points = $this->getUserGifts($user->id);
        }
        return UserResource::collection($users);

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
        $orderType = $orderBy == 'name' ? $this->getOrderType($orderBy) : $this->getOrderType($orderBy)." DESC";
        $sql = str_replace(':order', $orderType, $sql);
        $cities = DB::select($sql, [ $search, $search, $perPage, $offset]);
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
                    WHERE (? IS NULL OR c.name LIKE CONCAT('%', ?, '%'))
                    GROUP BY c.id,c.name
                    ORDER BY :order
                    LIMIT ?
                    OFFSET ?";
    }

    public function totalRate()
    {
        $result = DB::select('
        SELECT
       COALESCE(
                ROUND(
                    SUM(stars_count) / NULLIF(SUM(reviewer_count), 0),
                    1),
            0) AS rating
        FROM events

        ');
        return $result[0]->rating ?? 0;

    }

    public function getCitiesAndCategoriesAndLanguageIds():JsonResponse
    {
        $cities = City::select('id','name')->get();
        $categories = Category::select('id','name')->get();
        $languages = Language::select('id','name')->get();
        $countries = Country::select('id','name')->get();
        return response()->json([
            'cities' => $cities,
            'categories' => $categories,
            'languages' => $languages,
            'countries' => $countries,
        ]);
    }

    public function sendGift(array $data):JsonResponse
    {
        $user = User::findOrFail($data['user_id']);
        $success = $this->checkGift($data['user_id'],$data['points']);
        if(!$success){
            return response()->json([
                'message' => 'the user has already received a gift'
            ],400);
        }
        $points = $data['points'];
        $user->increment('points', $points);
        Mail::to($user->email)->send(new SendGiftMail($user->name, $points));
        return response()->json([
            'message' => 'Gift sent!',
        ]);
    }

    private function checkGift($id,$points):bool
    {
        $success = false;
        $month = Carbon::now()->subMonth();
        $date = $month->year.'/'.$month->month;
        $disk = Storage::disk('public');
        $content = $disk->exists('gifts.json') ? $disk->get('gifts.json') : $this->storeGiftsJsonFile();
        $data = json_decode($content, true);
        if(!isset($data[$date])) {
            $data[$date] = [];
        }
        if (!in_array($id, array_keys($data[$date]))) {
            $data[$date][$id]=  $points;
            $success = true;
        }
        Storage::disk('public')->put('gifts.json',json_encode($data));
        return $success;
    }

    private function storeGiftsJsonFile():string
    {
        Storage::disk('public')->put('gifts.json','{}');
        return '{}';
    }

    private function getUserGifts($id):string
    {
        $month = Carbon::now()->subMonth();
        $date = $month->year.'/'.$month->month;
        $data = json_decode(Storage::disk('public')->get('gifts.json'), true);
        if (in_array($id, array_keys($data[$date]))) {
            return $data[$date][$id];
        }
        return "0";
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




