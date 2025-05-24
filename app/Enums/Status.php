<?php

namespace App\Enums;

enum Status: string
{
    case PENDING = 'pending';
    case FINISHED = 'finished';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case CANCELED = 'canceled';

    public static function values(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
    }

    public static function taskValues(): array
    {
        return array_map(fn($case) => $case->value, array_filter(self::cases(), fn($case) => $case !== self::COMPLETED));
    }

}
