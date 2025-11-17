<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Parser;

new Parser(
    filesForParseDirName: __DIR__ . "/files_for_parse",
    outputDirName: __DIR__ . "/output",
    outputFileName: "packages.txt",
)->run();