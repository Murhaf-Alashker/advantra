<?php

namespace App\Traits;

use App\Libraries\FileManager;
use App\Models\Media;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;

trait MediaHandler
{
    public function media(): MorphMany
    {
        return $this->morphMany(Media::class, 'mediable');
    }

    public function storeMedia($path): void
    {
        $validFiles = $this->prepare($path);
        if(empty($validFiles['files']) || empty($validFiles['path'])) {return;}
        $this->storeValidFiles($validFiles['files'], $validFiles['path']);



    }

    public function deleteMedia(string $path, array|string|int|null $id = null): void
    {
        $path = Str::of($path)->finish('/').$this->id;
        if(!$id){
            FileManager::delete($path);
            $this->media()->delete();
        }
        else{
            $id = is_array($id) ? $id : [$id];

            $medias = $this->media()->whereIn('id', $id);

            $allMedias = (clone $medias)->get();

            if (count($allMedias) > 0) {

                foreach ($allMedias as $media) {

                    FileManager::delete($path, $media->path, $media->type);
                }
                $medias->delete();
            }
        }
    }

    public function getMedia(string $path, string|array|int|null $ids = null): array
    {
        $media = [
            'images' => [],
            'videos' => [],
            'pdf' => []
        ];
        $path = Str::of($path)->finish('/').$this->id;

        if(!$ids){
            return $this->getAllTypesOfMedia($path);
        }

        $ids = is_array($ids)? $ids : [$ids];
        $allMedia = $this->media()->whereIn('id', $ids)->get();
        foreach ($allMedia as $singleMedia) {
            $url =  FileManager::upload($path.'/'.$singleMedia->type, $singleMedia->path);
            $media[$singleMedia->type][] = ['id' => $singleMedia->id , 'url' => $url[0]];
        }

        return $media;

    }

    public function getAllTypesOfMedia($path): array
    {
        $path = Str::of($path)->finish('/').$this->id;
        return FileManager::bringMediaWithType($path);
    }

    public function updateMedia(string $path, array|string|int|null $ids = null): void
    {
        if($ids){
           $ids = is_array($ids)? $ids : [$ids];
           $idsToDelete = $this->media()->whereNOTIn('id', $ids)->pluck('id')->toArray();
           $this->deleteMedia($path, $idsToDelete);
        }

        $this->storeMedia($path);

    }

    private function prepare($path): array
    {
        $request = request();

        if (!$request->hasFile('media')) {
            return[];
        }
        $path = Str::of($path)->finish('/').$this->id;

        $mediaInput = $request->file('media');

        $files = is_array($mediaInput) ? $mediaInput : [$mediaInput];

        return ['path' => $path , 'files' => array_filter($files, fn($file) => $file->isValid())];
    }

    private function storeValidFiles(array $validFiles, string $path): void
    {
        if (count($validFiles) > 0) {
            $storedFiles = FileManager::storeMany($path, $validFiles);
            $this->media()->createMany($storedFiles);
        }
    }


}
