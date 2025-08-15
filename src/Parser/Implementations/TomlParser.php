<?php

namespace App\Parser\Implementations;

use Override;
use Yosymfony\Toml\Toml;

class TomlParser extends AbstractParser {

    #[Override]
    public function getItems(array $data): array {
        return array_map(static function (array $value): string {
            return $value['version'];
        }, $data['dependencies']);
    }

    #[Override]
    public function parse(string $content): array {
        return Toml::parse($content);
    }
}