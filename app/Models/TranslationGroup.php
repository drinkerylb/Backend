<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TranslationGroup extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'key',
        'description',
        'is_system'
    ];

    protected $casts = [
        'is_system' => 'boolean'
    ];

    public function translations()
    {
        return $this->hasMany(Translation::class);
    }

    public function getTranslationsForLanguage($language)
    {
        return $this->translations()
            ->forLanguage($language)
            ->get()
            ->pluck('value', 'key');
    }

    public function syncTranslations(array $translations, $language)
    {
        $languageId = is_numeric($language) ? $language : $language->id;

        foreach ($translations as $key => $value) {
            $this->translations()->updateOrCreate(
                [
                    'language_id' => $languageId,
                    'key' => $key
                ],
                [
                    'value' => $value,
                    'is_auto_translated' => false,
                    'last_translated_at' => now()
                ]
            );
        }
    }

    public function deleteTranslations($language = null)
    {
        $query = $this->translations();
        
        if ($language) {
            $query->forLanguage($language);
        }

        $query->delete();
    }

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($group) {
            if ($group->is_system) {
                throw new \Exception('System translation groups cannot be deleted.');
            }
        });
    }
}
