<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Translation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'language_id',
        'translation_group_id',
        'key',
        'value',
        'default_value',
        'model_type',
        'model_id',
        'field',
        'is_auto_translated',
        'last_translated_at'
    ];

    protected $casts = [
        'is_auto_translated' => 'boolean',
        'last_translated_at' => 'datetime'
    ];

    public function language()
    {
        return $this->belongsTo(Language::class);
    }

    public function group()
    {
        return $this->belongsTo(TranslationGroup::class, 'translation_group_id');
    }

    public function translatable()
    {
        return $this->morphTo('model');
    }

    public function scopeForModel($query, $model)
    {
        return $query->where('model_type', get_class($model))
            ->where('model_id', $model->id);
    }

    public function scopeForLanguage($query, $language)
    {
        return $query->where('language_id', is_numeric($language) ? $language : $language->id);
    }

    public function scopeForGroup($query, $group)
    {
        return $query->where('translation_group_id', is_numeric($group) ? $group : $group->id);
    }

    public function scopeForField($query, $field)
    {
        return $query->where('field', $field);
    }

    public static function translate($key, $language = null, $group = 'general', $replace = [])
    {
        $language = $language ?? app()->getLocale();
        $languageId = is_numeric($language) ? $language : Language::where('code', $language)->value('id');
        $groupId = is_numeric($group) ? $group : TranslationGroup::where('key', $group)->value('id');

        $translation = static::where('key', $key)
            ->where('language_id', $languageId)
            ->where('translation_group_id', $groupId)
            ->value('value');

        if (!$translation) {
            return $key;
        }

        return empty($replace) ? $translation : strtr($translation, $replace);
    }

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($translation) {
            if ($translation->isDirty('value')) {
                $translation->is_auto_translated = false;
                $translation->last_translated_at = now();
            }
        });
    }
}
