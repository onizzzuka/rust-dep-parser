<?php

namespace App\Parser;

abstract class ArrayParser {

    abstract public static function getItems(array $array): array;
}