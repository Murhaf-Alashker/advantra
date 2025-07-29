<?php

namespace App\Libraries;

use App\Models\City;
use App\Models\Event;
use App\Models\GroupTrip;
use App\Models\Guide;
use Illuminate\Support\Facades\App;
use function Laravel\Prompts\search;

class SearchClass
{
    protected $models = [
        'city' => City::class,
        'event' => Event::class,
        'guide' => Guide::class,
        'group_trip' => GroupTrip::class,
    ];
    protected string $type;
    protected array|null $range = null;
    protected string $contains = '';
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
    }

    public function setType($type): void
    {
        $this->type = $type;
    }

    public function setRange(array|null $range = null): void
    {
        if($this->type !== 'city')
        $this->range = $range;
    }

    public function setContains(string $contains): void
    {
        $key = strip_tags($contains);
        $key = preg_replace('/\s+/', ' ', $key);
        $key = trim($key);
        $this->contains = $key;
    }

    public function search(): array
    {
        $result = [];


        if($this->type === 'all'){
            foreach($this->models as $key => $model) {
            $result[$key] = $this->searchForType($key,$model);
            }
        }
        else if (array_key_exists($this->type, $this->models)){
            $result[$this->type] = $this->searchForType($this->type,$this->models[$this->type]);
        }
        return $result;
    }

    private function searchForType($key,$model)
    {
        $query = (new $model)->newQuery();
        if(App::getLocale() === 'en') {
            $query->whereAny(['name', 'description'], 'like', '%' . $this->contains . '%');
        }

        elseif (App::getLocale() === 'ar'){
            $query->whereHas('translations', function ($query) use ($key) {
                $query->whereIn('key', [$key.'name', $key.'description'])
                    ->where('translation', 'like', '%' . $this->contains . '%');
            });
        }

        if($this->type !== 'city' && $this->range)
        {
            $query->where('price' , '>=' , $this->range['min'])->where('price' , '<=' , $this->range['max']);
        }

        return $query->latest()->limit(10)->get();
    }
}
