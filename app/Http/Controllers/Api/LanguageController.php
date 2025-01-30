<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Language;
use App\Models\Translation;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class LanguageController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $languages = Language::orderBy('sort_order')
            ->when(request('active'), function ($query) {
                $query->where('is_active', true);
            })
            ->get();

        return response()->json($languages);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:5|unique:languages,code',
            'locale' => 'required|string|max:10|unique:languages,locale',
            'flag' => 'nullable|string',
            'is_rtl' => 'boolean',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'date_format' => 'nullable|array',
            'number_format' => 'nullable|array',
            'currency_format' => 'nullable|array',
            'sort_order' => 'integer'
        ]);

        DB::beginTransaction();

        try {
            if ($validated['is_default']) {
                Language::where('is_default', true)->update(['is_default' => false]);
            }

            $language = Language::create($validated);

            // Copy translations from default language if requested
            if ($request->input('copy_translations')) {
                $defaultLanguage = Language::where('is_default', true)->first();
                if ($defaultLanguage) {
                    $translations = Translation::where('language_id', $defaultLanguage->id)->get();
                    foreach ($translations as $translation) {
                        $translation->replicate()
                            ->fill([
                                'language_id' => $language->id,
                                'is_auto_translated' => true
                            ])
                            ->save();
                    }
                }
            }

            DB::commit();
            Cache::tags('languages')->flush();

            return response()->json($language, 201);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Language $language)
    {
        return response()->json($language);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Language $language)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'code' => 'sometimes|string|max:5|unique:languages,code,' . $language->id,
            'locale' => 'sometimes|string|max:10|unique:languages,locale,' . $language->id,
            'flag' => 'nullable|string',
            'is_rtl' => 'boolean',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'date_format' => 'nullable|array',
            'number_format' => 'nullable|array',
            'currency_format' => 'nullable|array',
            'sort_order' => 'integer'
        ]);

        DB::beginTransaction();

        try {
            if ($validated['is_default'] ?? false) {
                Language::where('is_default', true)
                    ->where('id', '!=', $language->id)
                    ->update(['is_default' => false]);
            }

            $language->update($validated);

            DB::commit();
            Cache::tags('languages')->flush();

            return response()->json($language);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Language $language)
    {
        if ($language->is_default) {
            return response()->json([
                'message' => 'Cannot delete default language'
            ], 422);
        }

        DB::beginTransaction();

        try {
            $language->translations()->delete();
            $language->delete();

            DB::commit();
            Cache::tags('languages')->flush();

            return response()->json(null, 204);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function setDefault(Language $language)
    {
        DB::beginTransaction();

        try {
            Language::where('is_default', true)->update(['is_default' => false]);
            $language->update(['is_default' => true]);

            DB::commit();
            Cache::tags('languages')->flush();

            return response()->json($language);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function updateOrder(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'languages' => 'required|array',
            'languages.*.id' => 'required|exists:languages,id',
            'languages.*.sort_order' => 'required|integer'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();

        try {
            foreach ($request->languages as $item) {
                Language::where('id', $item['id'])->update(['sort_order' => $item['sort_order']]);
            }

            DB::commit();
            Cache::tags('languages')->flush();

            return response()->json(['message' => 'Order updated successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function translations(Language $language)
    {
        $translations = $language->translations()
            ->with('group')
            ->when(request('group'), function ($query, $group) {
                $query->whereHas('group', function ($q) use ($group) {
                    $q->where('key', $group);
                });
            })
            ->paginate(request('per_page', 50));

        return response()->json($translations);
    }

    public function updateTranslations(Request $request, Language $language)
    {
        $validator = Validator::make($request->all(), [
            'translations' => 'required|array',
            'translations.*.group_key' => 'required|string',
            'translations.*.key' => 'required|string',
            'translations.*.value' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();

        try {
            foreach ($request->translations as $item) {
                $group = TranslationGroup::firstOrCreate(
                    ['key' => $item['group_key']],
                    ['name' => ucfirst($item['group_key'])]
                );

                Translation::updateOrCreate(
                    [
                        'language_id' => $language->id,
                        'translation_group_id' => $group->id,
                        'key' => $item['key']
                    ],
                    [
                        'value' => $item['value'],
                        'is_auto_translated' => false,
                        'last_translated_at' => now()
                    ]
                );
            }

            DB::commit();
            Cache::tags(['translations', 'languages'])->flush();

            return response()->json(['message' => 'Translations updated successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
