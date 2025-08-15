<?php

namespace App\Parser\Implementations;

use JsonException;
use Override;

class JsonParser extends AbstractParser {

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