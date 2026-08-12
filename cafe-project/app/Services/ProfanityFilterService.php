<?php

namespace App\Services;

use App\Models\ProhibitedWord;

class ProfanityFilterService
{
    /**
     * Lọc các từ khóa vi phạm thành ***
     */
    public function filter(?string $text): ?string
    {
        if (!$text) {
            return $text;
        }

        $prohibitedWords = ProhibitedWord::where('is_active', true)
            ->pluck('word')
            ->toArray();

        foreach ($prohibitedWords as $word) {
            $word = trim($word);

            if ($word === '') {
                continue;
            }

            $pattern = '/' . preg_quote($word, '/') . '/iu';

            $text = preg_replace_callback(
                $pattern,
                function ($matches) {
                    return str_repeat('*', mb_strlen($matches[0]));
                },
                $text
            );
        }

        return $text;
    }
}