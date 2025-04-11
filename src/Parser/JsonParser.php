<?php

namespace App\Parser;

use App\PackageItem;

class JsonParser extends ArrayParser {

    public static function getItems(array $array): array {
        $result = [];

        $array = array_filter($array, static fn ($item) => $item['type'] !== "inline");

        foreach ($array as $package) {
            $row_parsed = [];
            preg_match('/([\w\-]+)-(\S+)/', $package['dest'], $row_parsed);

            $result[$row_parsed[1]] = $row_parsed[2];
        }

        return $result;
    }
}