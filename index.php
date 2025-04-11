<?php
require __DIR__ . '/vendor/autoload.php';

use App\Parser\JsonParser;
use App\Parser\LockParser;
use App\Parser\TomlParser;
use Yosymfony\Toml\Toml;

$parsed_items = [
    TomlParser::getItems(Toml::parseFile(__DIR__ . "/files_for_parse/Cargo.toml")),
    LockParser::getItems(Toml::parseFile(__DIR__ . "/files_for_parse/Cargo.lock")),
    JsonParser::getItems(json_decode(file_get_contents(__DIR__ . "/files_for_parse/cargo-sources.json"), TRUE, 512, JSON_THROW_ON_ERROR)),
    JsonParser::getItems(json_decode(file_get_contents(__DIR__ . "/files_for_parse/cargo-sources-gatherer.json"), TRUE, 512, JSON_THROW_ON_ERROR)),
];

$packages = array_merge(
    TomlParser::getItems(Toml::parseFile(__DIR__ . "/files_for_parse/Cargo.toml")),
    LockParser::getItems(Toml::parseFile(__DIR__ . "/files_for_parse/Cargo.lock")),
    JsonParser::getItems(json_decode(file_get_contents(__DIR__ . "/files_for_parse/cargo-sources.json"), TRUE, 512, JSON_THROW_ON_ERROR)),
    JsonParser::getItems(json_decode(file_get_contents(__DIR__ . "/files_for_parse/cargo-sources-gatherer.json"), TRUE, 512, JSON_THROW_ON_ERROR)),
);

if ($packages !== []) {
    if (!mkdir('output') && !is_dir('output')) {
        throw new \RuntimeException(sprintf('Directory "%s" was not created', 'output'));
    }

    ksort($packages);
    $packages_text = "CRATES=\"\n";
    foreach ($packages as $package_name => $package_version) {
        $packages_text .= "\t" . $package_name . "@" . $package_version . "\n";
    }
    $packages_text .= "\"";

    $file = fopen(__DIR__ . "/output/packages.txt", 'wb');

    fwrite($file, $packages_text);
}