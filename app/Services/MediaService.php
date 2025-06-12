<?php
namespace App\Services;

use App\Models\Media;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaService{
    public function store(Model $model, UploadedFile $image): Media
    {
        $folder = Str::plural(Str::lower(class_basename($model)));
        $path = $image->store($folder, 'public');
        return $model->media()->create([
            'path' => $path,
        ]);
    }

    public function storeMany(Model $model, array $images): array
    {
        $stored = [];

        foreach ($images as $image) {

            if ($image instanceof UploadedFile) {
                $stored[] = $this->store($model, $image);
            }
        }

        return $stored;
    }

    public function deleteImages(array $data){

        foreach ($data['ids'] as $id){
            $image = Media::find($id);
            if($image) {
                Storage::disk('public')->delete($image->path);
                $image->delete();

            }
        }
        return response()->json([
            'message' => 'Images deleted successfully',204
        ]);
    }

}
