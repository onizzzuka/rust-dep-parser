<?php

namespace App\Parser;

use App\Parser\Implementations\JsonParser;
use App\Parser\Implementations\LockParser;
use App\Parser\Implementations\TomlParser;

class ParserFactory {

    public static function createByExtension(string $extension): ?ParserInterface {
        return match ($extension) {
            'toml' => new TomlParser(),
            'lock' => new LockParser(),
            'json' => new JsonParser(),
            default => NULL,
        };
    }
}
