<?php

use App\Libraries\ScheduleClass;
use App\Models\BusinessInfo;
use App\Models\GroupTrip;
use App\Models\LimitedEvents;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Carbon;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function (){
    $previousMonth = Carbon::now()->subMonth();
    $month = $previousMonth->month;
    $year = $previousMonth->year;
    $info = (array) ScheduleClass::getCurrentMonthInfo($month, $year);
    BusinessInfo::create([
        'total_profit' => $info['total_profit'],
        'total_income' => $info['total_income'],
        'events_reserved_tickets' => $info['events_reserved_tickets'],
        'group_trip_reserved_tickets' => $info['group_trip_reserved_tickets'],
        'created_at' => Carbon::now()->subMonth(),
    ]);
})->monthlyOn(1, '00:00');
Schedule::call(function (){
  $groups = GroupTrip::whereMonth('starting_date', Carbon::now()->month)
                    ->whereYear('starting_date', Carbon::now()->year)
                    ->whereDay('starting_date', Carbon::now()->addDays(2))
                    ->where('remaining_tickets','>','tickets_limit')->pluck('id')
                    ->toArray();

  $limitedEvents = LimitedEvents::whereMonth('start_date', Carbon::now()->month)
          ->whereYear('start_date', Carbon::now()->year)
          ->whereDay('start_date', Carbon::now()->addDays(2))
          ->pluck('event_id')
          ->toArray();
// ارسل الرسالة هون للايفينت
    // متلا للايفينت المحدود
})->dailyAt('00:00');

