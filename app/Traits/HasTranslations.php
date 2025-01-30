<?php

namespace App\Traits;

use App\Models\Language;
use App\Models\Translation;
use App\Models\TranslationGroup;

trait HasTranslations
{
    public function translations()
    {
        return $this->morphMany(Translation::class, 'model');
    }

    public function translate($field, $language = null, $fallback = true)
    {
        $language = $language ?? app()->getLocale();
        $languageId = is_numeric($language) ? $language : Language::where('code', $language)->value('id');

        $translation = $this->translations()
            ->where('field', $field)
            ->where('language_id', $languageId)
            ->value('value');

        if (!$translation && $fallback) {
            // Fallback to default language
            $defaultLanguageId = Language::where('is_default', true)->value('id');
            if ($languageId !== $defaultLanguageId) {
                $translation = $this->translations()
                    ->where('field', $field)
                    ->where('language_id', $defaultLanguageId)
                    ->value('value');
            }
        }

        return $translation ?? $this->getAttribute($field);
    }

    public function translateOrNew($field, $language)
    {
        $languageId = is_numeric($language) ? $language : Language::where('code', $language)->value('id');

        return $this->translations()
            ->firstOrNew([
                'field' => $field,
                'language_id' => $languageId
            ]);
    }

    public function setTranslation($field, $value, $language, $isAutoTranslated = false)
    {
        $languageId = is_numeric($language) ? $language : Language::where('code', $language)->value('id');

        $translation = $this->translations()->updateOrCreate(
            [
                'field' => $field,
                'language_id' => $languageId
            ],
            [
                'value' => $value,
                'default_value' => $this->getAttribute($field),
                'is_auto_translated' => $isAutoTranslated,
                'last_translated_at' => now()
            ]
        );

        return $translation;
    }

    public function setTranslations($field, array $translations)
    {
        foreach ($translations as $language => $value) {
            $this->setTranslation($field, $value, $language);
        }

        return $this;
    }

    public function deleteTranslations($field = null, $language = null)
    {
        $query = $this->translations();

        if ($field) {
            $query->where('field', $field);
        }

        if ($language) {
            $languageId = is_numeric($language) ? $language : Language::where('code', $language)->value('id');
            $query->where('language_id', $languageId);
        }

        $query->delete();

        return $this;
    }

    public function getTranslatedAttributes()
    {
        return property_exists($this, 'translatable') ? $this->translatable : [];
    }

    public function isTranslatable($field)
    {
        return in_array($field, $this->getTranslatedAttributes());
    }

    public function getTranslationsArray()
    {
        $translations = [];
        $languages = Language::all();

        foreach ($this->getTranslatedAttributes() as $field) {
            foreach ($languages as $language) {
                $translations[$field][$language->code] = $this->translate($field, $language->code);
            }
        }

        return $translations;
    }

    protected static function bootHasTranslations()
    {
        static::deleting(function ($model) {
            if (!method_exists($model, 'isForceDeleting') || $model->isForceDeleting()) {
                $model->translations()->delete();
            }
        });
    }
} 