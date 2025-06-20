<?php

namespace App\Http\Controllers;

use App\Http\Requests\LanguageRequest;
use App\Http\Resources\LanguageResource;
use App\Models\Language;
use App\Services\LanguageService;
use Illuminate\Http\Request;

class LanguageController extends Controller
{
    protected LanguageService  $languageService;

    public function __construct(LanguageService $languageService){
        $this->languageService = $languageService;
    }
    public function store(LanguageRequest $request)
    {
        $validated = $request->validated();
        $LangData = collect($validated)->except('name_ar')->all();
        $lang= $this->languageService->store($LangData);
        $lang->translations()->create(['key' => 'language.name',
            'translation' => $validated['name_ar'],
        ]);

        return response()->json([new LanguageResource($lang),201]);
    }

    public function destroy(Language $language){
        $this->languageService->destroy($language);
        return response()->json([
            'message' => 'Language deleted successfully'
        ]);
    }
}
