<?php
require __DIR__ . '/vendor/autoload.php';

use App\Parser\JsonParser;
use App\Parser\LockParser;
use App\Parser\TomlParser;
use Yosymfony\Toml\Toml;

const FILES_FOR_PARSE_DIR = __DIR__ . "/files_for_parse";
const OUTPUT_DIR          = __DIR__ . "/output";
const OUTPUT_FILE         = "packages.txt";

try {
    processFiles(getFilesWithParsers(FILES_FOR_PARSE_DIR));
}
catch (RuntimeException $e) {
    handleError($e);
}

function getFilesWithParsers(string $directory): array {
    $files = scandir($directory);
    $fileParsers = [];

    foreach ($files as $file) {
        if ($file === '.' || $file === '..') {
            continue;
        }

        $extension = pathinfo($file, PATHINFO_EXTENSION);
        $parser = getParserByExtension($extension);
        if ($parser !== null) {
            $fileParsers[$directory . '/' . $file] = new $parser();
        }
    }

    return $fileParsers;
}

function getParserByExtension(string $extension): string|null {
    return match ($extension) {
        'toml' => TomlParser::class,
        'lock' => LockParser::class,
        'json' => JsonParser::class,
        default => NULL,
    };
}

function processFiles(array $files): void {
    $packages = readAndParseFiles($files);

    if (!empty($packages)) {
        createOutputDirectory();
        $packages_text = generatePackagesText($packages);
        file_put_contents(OUTPUT_DIR . "/" . OUTPUT_FILE, $packages_text);
    }
}

function handleError(Exception $e): void {
    echo "Error: " . $e->getMessage() . "\n";
}

function createOutputDirectory(): void {
    if (!is_dir(OUTPUT_DIR) && !mkdir(OUTPUT_DIR) && !is_dir(OUTPUT_DIR)) {
        throw new RuntimeException(sprintf('Directory "%s" was not created', OUTPUT_DIR));
    }
}

function readAndParseFiles(array $files): array {
    $parsed_items = [];
    foreach ($files as $filePath => $parser) {
        if (!file_exists($filePath)) {
            throw new RuntimeException("File not found: $filePath");
        }

        try {
            $parsed_items[] = $parser::getItems(
                ($parser instanceof TomlParser || $parser instanceof LockParser)
                    ? Toml::parseFile($filePath)
                    : json_decode(file_get_contents($filePath), TRUE, 512, JSON_THROW_ON_ERROR)
            );
        }
        catch (JsonException $e) {
            handleError(e: $e);
        }

    }

    return array_merge(...$parsed_items);
}

function generatePackagesText(array $packages): string {
    ksort($packages);
    $packages_lines = array_map(static fn($name, $version) => "\t$name@$version", array_keys($packages), $packages);

    return 'CRATES="' . implode("\n", $packages_lines) . "\n\"";
}
