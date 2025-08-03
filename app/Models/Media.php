<?php

namespace App\Models;

use App\Libraries\FileManager;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;

class Media extends Model
{
    /** @use HasFactory<\Database\Factories\MediaFactory> */
    use HasFactory;

    protected $fillable = [
        'path',
        'type'
    ];


    protected function casts(): array
    {
        return [
            'created_at' => 'datetime:Y-m-d H:i:s',
            'updated_at' => 'datetime:Y-m-d H:i:s',
        ];
    }

    public function mediable(): MorphTo
    {
        return $this->morphTo();
    }

//    public function getUrlAttribute(): string
//    {
//            if (!$this->path) return '';
//        $fileManager = new FileManager();
//            $urls = $fileManager->upload($this->path, $this->filename);
//            return $urls[0] ?? '';
//        }



}
