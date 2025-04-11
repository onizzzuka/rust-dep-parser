<?php

namespace App\Parser;

use App\PackageItem;

class TomlParser extends ArrayParser {

    public static function getItems(array $array): array {

        return array_map(static function ($value) {
            return $value['version'];
        }, $array['dependencies']);
    }
}