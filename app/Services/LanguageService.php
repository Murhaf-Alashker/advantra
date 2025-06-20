<?php
namespace App\Services;


use App\Http\Requests\LanguageRequest;
use App\Http\Resources\LanguageResource;
use App\Models\Language;

class LanguageService{

    public function store(array $data){

     $lang = Language::create($data);
       return $lang;
    }

    public function destroy(Language $language){
        return $language->delete();
    }
}
