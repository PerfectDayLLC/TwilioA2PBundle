<?php

use Illuminate\Support\Str;

// Have to add it here so older Laravel versions can use this
if (! function_exists('str_headline')) {
    function str_headline(string $text)
    {
        $parts = explode('_', str_replace(' ', '_', $text));

        if (count($parts) > 1) {
            $parts = array_map([Str::class, 'title'], $parts);
        }

        $studly = Str::studly(implode($parts));

        $words = preg_split('/(?=[A-Z])/', $studly, -1, PREG_SPLIT_NO_EMPTY);

        return implode(' ', $words);
    }
}
