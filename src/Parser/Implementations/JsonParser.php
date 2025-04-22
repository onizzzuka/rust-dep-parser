<?php

namespace App\Parser\Implementations;

class JsonParser extends ArrayParser {

    public static function getItems(array $data): array {
        $result = [];

        $data = array_filter($data, static fn($item) => $item['type'] !== "inline");

        foreach ($data as $package) {
            $row_parsed = [];
            preg_match('/([\w\-]+)-(\S+)/', $package['dest'], $row_parsed);

            $result[$row_parsed[1]] = $row_parsed[2];
        }

        return $result;
    }

    public function parse(string $content): array {
        return json_decode($content, TRUE, 512, JSON_THROW_ON_ERROR);
    }
}