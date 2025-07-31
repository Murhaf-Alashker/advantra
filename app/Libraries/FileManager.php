<?php

namespace App\Libraries;

use App\Enums\MediaType;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileManager
{
    public static function upload(string $path, string $filename = null): array
    {
        if ($filename != null) {

            $path = Str::of($path)->finish('/');

            $fullPath = $path . $filename;

            if (Storage::disk('public')->exists($fullPath)) {

                return [Storage::disk('public')->url($fullPath)];

            }

            return [];
        }
        if(Storage::disk('public')->exists($path))
        {
            $files = Storage::disk('public')->files($path);

            return array_map(fn ($file) => Storage::disk('public')->url($file), $files);
        }

        return [];
    }


    public static function store(string $path, $file): array
    {
        $type = 'images';

        $extension = $file->getClientOriginalExtension();

        if(in_array($extension,MediaType::pdf())){
            $type = 'pdf';
        }

        else if(in_array($extension,MediaType::videos())){
            $type = 'videos';
        }

        $path = Str::of($path)->finish('/');

        $filename = Str::uuid() . '.' . $extension;

        $filename = Str::snake($filename);

        if($type === 'pdf')
        {
            Storage::disk('public')->put($path . $filename, $file->getContent());
        }

        else
        {
            $finalPath = $path . $type . '/';
            $file->storeAs($finalPath, $filename, 'public');
        }


        return ['path' => $filename, 'type' => $type];

    }

    public static function storeMany(string $path, array $files, string $type = 'images'): array
    {
        $stored = [];

        foreach ($files as $file) {
            $filename = self::store($path, $file);
            $stored[] = $filename;
        }

        return $stored;
    }


    public static function delete(string $path, string $filename = null, string $type = 'images')
    {
        if ($filename != null) {
            $path = Str::of($path)->finish('/');
            $path = $path . $type.'/';
            if(Storage::disk('public')->exists($path . $filename)){
            Storage::disk('public')->delete($path . $filename);
            }
        }else{
            Storage::disk('public')->deleteDirectory($path);
        }
    }

    public static function bringMedia($collection, $path)
    {
        return $collection->map(function ($media) use ($path) {
            $url = self::upload($path, $media->path);
            return [
                'id' => $media->id,
                'url' => $url[0] ?? null,
            ];
        });
    }

    public static function bringMediaWithType($path): array
    {
        $images = self::upload($path. '/images');
        $videos = self::upload($path. '/videos');
        $pdfs = self::upload($path. '/pdf');
        return [
            'images' => $images,
            'videos' => $videos,
            'pdfs' => $pdfs,
        ];
    }

    public static function updateSinglePic(string $path, string $oldFilename, $file): array
    {
        self::delete($path, $oldFilename);
        return self::store($path, $file);
    }

}
