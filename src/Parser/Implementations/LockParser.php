<?php

namespace App\Parser\Implementations;

use Yosymfony\Toml\Toml;

class LockParser extends ArrayParser {

    public static function getItems(array $data): array {
        $result = [];

        foreach ($data['package'] as $package) {
            $result[$package['name']] = $package['version'];
        }

        return $result;
    }

    public function parse(string $content): array {
        return Toml::parse($content);
    }
}