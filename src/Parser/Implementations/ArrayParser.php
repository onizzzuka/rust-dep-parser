<?php

namespace App\Parser\Implementations;

use App\Parser\ParserInterface;

abstract class ArrayParser implements ParserInterface {

    abstract public static function getItems(array $data): array;

    abstract public function parse(string $content): array;
}