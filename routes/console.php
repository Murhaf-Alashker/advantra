<?php

use App\Libraries\ScheduleClass;
use App\Models\BusinessInfo;
use App\Models\GroupTrip;
use App\Models\Guide;
use App\Models\LimitedEvents;
use App\Models\TemporaryReservation;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

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
  $groups = GroupTrip::whereDate('starting_date', Carbon::now()->addDays(2))
                    ->whereColumn('remaining_tickets','>','tickets_limit')->pluck('id')
                    ->toArray();

  $limitedEvents = LimitedEvents::whereDate('start_date', Carbon::now()->addDays(2))
          ->whereColumn('remaining_tickets','>','tickets_limit')
          ->pluck('event_id')
          ->toArray();
  $admin = \App\Models\Admin::first();
  if(!empty($groups)){
      $admin->notify(new \App\Notifications\PersonalNotification('upcoming group trips','this group trips is 2 days away from starting',['type' => 'groupTrip' , 'id' =>$groups],$admin->fcmToken));
  }
    if(!empty($limitedEvents)){
        $admin->notify(new \App\Notifications\PersonalNotification('upcoming event','this events is 2 days away from starting',['type' => 'event','id' => $limitedEvents],$admin->fcmToken));
    }
})->everyMinute();

Schedule::call(function (){
    $expire = Carbon::now()->subMinutes(5);
    TemporaryReservation::where('created_at', '<', $expire)->delete();
})->everyFiveMinutes();

Schedule::call(function (){
    ScheduleClass::importProjects(Carbon::now()->subMonth()->year . '/' . \Carbon\Carbon::now()->subMonth()->format('M'));
    $guides = Guide::activeGuides()->update(['extra_salary' => 0.00]);
})->monthlyOn(1, '00:00');

Schedule::call(function (){
    $groups = GroupTrip::where('status','<=',\App\Enums\Status::PENDING->value)
        ->whereDate('starting_date', '<=', Carbon::now()->format('Y-m-d H:i:s'))
        ->update(['status' => \App\Enums\Status::IN_PROGRESS->value]);

    $eventIds = LimitedEvents::where('start_date', '<', Carbon::now()->format('Y-m-d H:i:s'))->pluck('event_id')->toArray();

     //   \Illuminate\Support\Facades\Storage::drive('public')->put('a1.json',$eventIds);
   // Log::info('Found event IDs: ', $eventIds);


     \App\Models\Event::whereIn('id', $eventIds)
        ->update(['status' => 'inactive']);
  //  Log::info('Count to update: ' . $query->count());

})->everyFiveMinutes();
