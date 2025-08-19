<?php

namespace App\Parser\Implementations;

use Override;
use Yosymfony\Toml\Toml;

class TomlParser extends AbstractParser {

    /**
     * Processes the given data array to extract and return a list of versions
     * from the dependencies.
     *
     * @param array $data  An associative array containing a 'dependencies' key
     *                     where each dependency is an associative array that
     *                     includes a 'version' key.
     *
     * @return array An array of version strings extracted from the dependencies.
     */
    #[Override]
    public function getItems(array $data): array {
        return array_map(static function (array $value): string {
            return $value['version'];
        }, $data['dependencies']);
    }

    /**
     * Parses the provided TOML content string and returns the parsed data as an array.
     *
     * @param string $content The TOML formatted string to be parsed.
     *
     * @return array An associative array representing the parsed TOML data.
     */
    #[Override]
    public function parse(string $content): array {
        return Toml::parse($content);
    }
}