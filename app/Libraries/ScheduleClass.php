<?php

namespace App\Libraries;

use App\Models\Reservation;

class ScheduleClass
{
    public static function getCurrentMonthInfo($month,$year):\stdClass
    {
        $data = ['total_profit' => 0, 'total_income' => 0, 'total_expenses' => 0, 'events_reserved_tickets' =>0, 'group_trip_reserved_tickets' => 0];
        $reservations = Reservation::whereMonth('created_at', $month)->whereYear('created_at', $year)->orderBy('created_at')->get();
        foreach ($reservations as $reservation) {

            $data['total_income'] += $reservation->ticket_price * $reservation->tickets_count;
            $data['total_expenses'] += $reservation->basic_cost * $reservation->tickets_count ;

            $type = self::modelToTable($reservation->reservable_type);
            if($type){
                $data[$type]+= $reservation->tickets_count;
            }
        }
        $data['total_profit'] = $data['total_income'] - $data['total_expenses'];

        return (object) $data;
    }

    public static function modelToTable($modelClass):string
    {
        return match ($modelClass) {
            'App\Models\GroupTrip' => 'group_trip_reserved_tickets',
            'App\Models\Event' => 'events_reserved_tickets',
            default => null,
        };
    }
}
