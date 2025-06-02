<?php

namespace App\Parser;

interface ParserInterface {
    public function getItems(array $data): array;
    public function parse(string $content): array;
}