<?php

namespace App\Libraries;

use App\Enums\Status;
use App\Models\GroupTrip;
use App\Models\Guide;
use App\Models\Reservation;
use App\Models\Scopes\GuideScope;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Spatie\SimpleExcel\SimpleExcelWriter;


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
        $groups = GroupTrip::where('status','=', Status::FINISHED)
                           ->where('extra_cost','>',0)
                           ->whereMonth('updated_at', $month)
                           ->whereYear('updated_at', $year)->get();
        foreach ($groups as $group) {
            $data['total_expenses'] += $group->extra_cost;
        }
        $guides = Guide::get();
        foreach ($guides as $guide) {
            $data['total_expenses'] += $guide->const_salary + $guide->extra_salary;
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

    public static function importProjects(string $name)
    {
        try {
            $fileName = "$name.xlsx";
            $path = "storage/uploads/guides_salary/$fileName";

            // جلب كل الصفوف
            $projects = Guide::select('id', 'name', 'email', 'const_salary', 'extra_salary')->withoutGlobalScope(GuideScope::class)->get();

            // جلب أسماء الأعمدة تلقائيًا من أول صف
            $headers = $projects->first() ? array_keys($projects->first()->toArray()) : [];

            File::ensureDirectoryExists(dirname($path));
            $rows = $projects->map(fn($g) => [
                'id' => $g->id,
                'name' => $g->name,
                'email' => $g->email,
                'const_salary' => $g->const_salary,
                'extra_salary' => $g->extra_salary,
            ]);

            SimpleExcelWriter::create($path)
                ->addHeader($headers)
                ->addRows($rows->toArray());
        }catch (\Exception $exception){

        }
    }

    public static function exportProjects(string $data):string|null
    {
        return Storage::disk('public')->exists('uploads/guides_salary/'."$data.xlsx") ?
               Storage::disk('public')->url('uploads/guides_salary/'."$data.xlsx") : null;

    }

}
