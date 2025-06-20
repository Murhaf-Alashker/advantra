<?php
namespace App\Services;

use App\Models\Media;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaService{

    public function uploadImages(array $data)
    {
        $images = $data['images'];
        if (!is_array($images)){
            $images = [$images];
        }

        $modelClassMap = [
            'event' => \App\Models\Event::class,
            'city' => \App\Models\City::class,
            'group_trip' => \App\Models\GroupTrip::class,
            'guide' => \App\Models\Guide::class,
            'user' => \App\Models\User::class,
            'solo_trip' => \App\Models\SoloTrip::class,
        ];

        $type = strtolower($data['mediable_type']);
        $modelClass = $modelClassMap[$type] ?? null;

        if (!$modelClass) {
            abort(422, 'Unknown model type');
        }

        $model = $modelClass::findOrFail($data['mediable_id']);



        $folder = match ($type) {
            'event', 'guide', 'solo_trip','group_trip' => $type . 's/' . Str::slug($model->name),
            'city',  => 'cities/' . Str::slug($model->name),
            'user' => 'users/profile/' . Str::slug($model->name),
            default => 'others'
        };

        $storedMedia = [];

        foreach ( $images as $image) {
            $filename = Str::uuid() . '.' . $image->getClientOriginalExtension();
            $path = $image->storeAs($folder, $filename, 'public');
            $storedMedia[] = $model->media()->create(['path' => $path]);
        }

        return $storedMedia;
    }

    public function deleteImages(array $data)
    {
        foreach ($data['ids'] as $id) {
            $image = Media::find($id);
            if ($image) {
                Storage::disk('public')->delete($image->path);
                $image->delete();
            }
        }
    }
   /* public function store(Model $model, UploadedFile $image): Media
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
*/
}
