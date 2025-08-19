<?php

namespace App\Parser;

use App\Parser\Implementations\JsonParser;
use App\Parser\Implementations\LockParser;
use App\Parser\Implementations\TomlParser;

class ParserFactory {

    /**
     * Creates a parser instance based on the provided file extension.
     *
     * @param string $extension The file extension used to determine the appropriate parser.
     *                          Supported values are 'toml', 'lock', and 'json'.
     *
     * @return ParserInterface|null Returns an instance of a parser that corresponds to the given extension,
     * or null if the extension is not supported.
     */
    public static function createByExtension(string $extension): ?ParserInterface {
        return match ($extension) {
            'toml' => new TomlParser(),
            'lock' => new LockParser(),
            'json' => new JsonParser(),
            default => NULL,
        };
    }
}
