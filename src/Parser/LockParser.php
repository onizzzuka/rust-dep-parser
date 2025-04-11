<?php

namespace App\Parser;

use App\PackageItem;

class LockParser extends ArrayParser {

    public static function getItems(array $array): array {
        $result = [];

        foreach ($array['package'] as $package) {
            $result[$package['name']] = $package['version'];
        }

        return $result;
    }
}