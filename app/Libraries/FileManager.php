<?php

namespace App\Libraries;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileManager
{
    public function upload(string $path, string $filename = null): array
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

    }


    public function store(string $path, $file,string $type = 'pic'): string
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

    public function delete(string $path, string $filename = null)
    {
        if ($filename != null) {
            $path = Str::of($path)->finish('/');

            return Storage::disk('public')->delete($path . $filename);
        }

        return Storage::disk('public')->deleteDirectory($path);
    }
}
