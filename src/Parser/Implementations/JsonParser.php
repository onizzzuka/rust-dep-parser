<?php

namespace App\Parser\Implementations;

use JsonException;
use Override;

class JsonParser extends AbstractParser {

    /**
     * Filters and extracts specific components from the input data.
     *
     * @param array $data An array of data items where each item is expected to have a 'type' and 'dest' key.
     *                    The 'type' key is used to filter out items of type "inline".
     *                    The 'dest' key is processed to extract specific structured information.
     *
     * @return array Returns an associative array where the keys are extracted components,
     *               and the corresponding values are derived from the 'dest' data of filtered items.
     */
    #[Override]
    public function getItems(array $data): array {
        $result = [];

        $data = array_filter($data, static fn($item) => $item['type'] !== "inline");

        foreach ($data as $package) {
            $row_parsed = [];
            preg_match('/([\w\-]+)-(\S+)/', $package['dest'], $row_parsed);

            $result[$row_parsed[1]] = $row_parsed[2];
        }

        return $result;
    }

    /**
     * Parses a JSON-encoded string into an associative array.
     *
     * @param string $content A JSON-encoded string to be decoded.
     *
     * @return array Returns an associative array derived from the decoded JSON string.
     *               If decoding fails, an empty array is returned.
     */
    #[Override]
    public function parse(string $content): array {
        try {
            return json_decode($content, TRUE, 512, JSON_THROW_ON_ERROR);
        }
        catch (JsonException $e) {
            echo "Json parse error: " . $e->getMessage();
            return [];
        }
    }
}