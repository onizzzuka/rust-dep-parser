<?php

namespace App\Parser\Implementations;

use App\Parser\ParserInterface;

abstract class AbstractParser implements ParserInterface {

    abstract public function getItems(array $data): array;

    abstract public function parse(string $content): array;
}