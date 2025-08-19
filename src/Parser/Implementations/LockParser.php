<?php

namespace App\Parser\Implementations;

use Override;
use Yosymfony\Toml\Toml;

class LockParser extends AbstractParser {

    /**
     * Processes the provided data to extract package names and their versions.
     *
     * @param array $data An associative array containing package information where 'package' is a key holding an array of packages.
     *                    Each package is expected to have 'name' and 'version' keys.
     *
     * @return array An associative array mapping package names to their respective versions.
     */
    #[Override]
    public function getItems(array $data): array {
        $result = [];

        foreach ($data['package'] as $package) {
            $result[$package['name']] = $package['version'];
        }

        return $result;
    }

    /**
     * Parses the given content string and returns an associative array representation of it.
     *
     * @param string $content The content to be parsed.
     *
     * @return array The parsed representation of the content as an associative array.
     */
    #[Override]
    public function parse(string $content): array {
        return Toml::parse($content);
    }
}