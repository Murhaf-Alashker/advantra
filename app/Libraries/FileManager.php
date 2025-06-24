<?php

namespace App\Libraries;

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

        $files = Storage::disk('public')->files($path);

       return array_map(fn ($file) => Storage::disk('public')->url($file), $files);
      //  return [1];
    }


    public static function store(string $path, $file,string $type = 'pic'): string
    {
        $path = Str::of($path)->finish('/');

        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();

        $filename = Str::snake($filename);

        if($type === 'pic')
        {
            $file->storeAs($path, $filename, 'public');
        }

        else if($type === 'pdf')
        {
            Storage::disk('public')->put($path . $filename, $file->getContent());
        }

        return $filename;
    }

    public static function storeMany(string $path, array $files, string $type = 'pic'): array
    {
        $stored = [];

        foreach ($files as $file) {
            $filename = self::store($path, $file, $type);
            $stored[] = $filename;
        }

        return $stored;
    }


    public static function delete(string $path, string $filename = null)
    {
        if ($filename != null) {
            $path = Str::of($path)->finish('/');

            return Storage::disk('public')->delete($path . $filename);
        }

        return Storage::disk('public')->deleteDirectory($path);
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

}
