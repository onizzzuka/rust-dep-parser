<?php

namespace App\Parser\Implementations;

use Override;
use Yosymfony\Toml\Toml;

class LockParser extends AbstractParser {

    #[Override]
    public function getItems(array $data): array {
        $result = [];

        foreach ($data['package'] as $package) {
            $result[$package['name']] = $package['version'];
        }

        return $result;
    }

    #[Override]
    public function parse(string $content): array {
        return Toml::parse($content);
    }
}