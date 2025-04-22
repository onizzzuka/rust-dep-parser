<?php
require __DIR__ . '/vendor/autoload.php';

use App\Parser\ParserFactory;

const FILES_FOR_PARSE_DIR = __DIR__ . "/files_for_parse";
const OUTPUT_DIR          = __DIR__ . "/output";
const OUTPUT_FILE         = "packages.txt";

try {
    processFiles(getFilesWithParsers(FILES_FOR_PARSE_DIR));
}
catch (RuntimeException $e) {
    handleError($e);
}

function processFiles(array $files): void {
    $packages = readAndParseFiles($files);

    if (!empty($packages)) {
        createOutputDirectory();
        $packagesText = generatePackagesText($packages);
        file_put_contents(OUTPUT_DIR . "/" . OUTPUT_FILE, $packagesText);
    }
}

function getFilesWithParsers(string $directory): array {
    $files       = array_filter(scandir($directory), static fn($file) => $file !== '.' && $file !== '..');
    $fileParsers = [];

    foreach ($files as $filename) {
        $extension   = pathinfo($filename, PATHINFO_EXTENSION);
        $parserClass = ParserFactory::createByExtension($extension);

        if ($parserClass !== NULL) {
            $fullPath               = $directory . '/' . $filename;
            $fileParsers[$fullPath] = new $parserClass();
        }
    }

    return $fileParsers;
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
    $parsedItems = [];
    foreach ($files as $filePath => $parser) {
        if (!file_exists($filePath)) {
            throw new RuntimeException("File not found: $filePath");
        }

        try {
            $content        = file_get_contents($filePath);
            $data           = $parser->parse($content);
            $parsedItems[] = $parser::getItems($data);
        }
        catch (JsonException $e) {
            handleError(e: $e);
        }

    }

    return array_merge(...$parsedItems);
}

function generatePackagesText(array $packages): string {
    ksort($packages);
    $packagesLines = array_map(static fn($name, $version) => "\t$name@$version", array_keys($packages), $packages);

    return 'CRATES="' . implode("\n", $packagesLines) . "\n\"";
}
