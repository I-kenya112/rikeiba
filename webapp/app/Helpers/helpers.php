<?php

if (!function_exists('relation_path_to_japanese')) {
    function relation_path_to_japanese(string $path): string
    {
        $map = [
            'F' => '父',
            'M' => '母',
        ];

        $result = '';
        foreach (mb_str_split($path) as $char) {
            $result .= $map[$char] ?? $char;
        }
        return $result;
    }
}
