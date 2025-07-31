<?php

namespace App\Enums;

enum MediaType: string
{
    case PDF = 'pdf';
    case MP4 = 'mp4';
    case AVI = 'avi';
    case MOV = 'mov';
    case MPEG = 'mpeg';
    case JPG = 'jpg';
    case JPEG = 'jpeg';
    case PNG ='png';
    case WEBP ='webp';

    public static function values(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
    }

    public static function images()
    {
        return array_map(fn($case) => $case->value, [self::JPG, self::JPEG, self::PNG ,self::WEBP]);
    }

    public static function videos()
    {
        return array_map(fn($case) => $case->value, [self::AVI, self::MOV, self::MPEG, self::MP4]);
    }

    public static function pdf()
    {
        return array_map(fn($case) => $case->value,[self::PDF]);
    }
}
