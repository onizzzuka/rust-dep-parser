<?php

namespace App\Parser\Implementations;

use Yosymfony\Toml\Toml;

class TomlParser extends AbstractParser {

    public static function getItems(array $data): array {

        return array_map(static function ($value) {
            return $value['version'];
        }, $data['dependencies']);
    }

    public function parse(string $content): array {
        return Toml::parse($content);
    }
}