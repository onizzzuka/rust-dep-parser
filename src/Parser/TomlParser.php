<?php

namespace App\Parser;

class TomlParser extends ArrayParser {

    public static function getItems(array $array): array {

        return array_map(static function ($value) {
            return $value['version'];
        }, $array['dependencies']);
    }
}