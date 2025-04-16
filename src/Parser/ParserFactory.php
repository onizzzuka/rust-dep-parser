<?php

namespace App\Parser;

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
