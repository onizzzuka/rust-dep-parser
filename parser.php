<?php

require __DIR__ . '/vendor/autoload.php';

use App\Parser\ParserFactory;

const FILES_FOR_PARSE_DIR = __DIR__ . "/files_for_parse";
const OUTPUT_DIR          = __DIR__ . "/output";
const OUTPUT_FILE         = "packages.txt";

Parser::run();

class Parser {

    public static function run(): void {
        $parser = new self();
        $parser->process();
    }

    private function process(): void {
        try {
            $fileParsers = $this->findFilesWithParsers();
            $this->processFiles($fileParsers);
        }
        catch (RuntimeException $e) {
            $this->handleError($e);
        }
    }

    private function findFilesWithParsers(): array {
        $directory   = FILES_FOR_PARSE_DIR;
        $fileParsers = [];

        foreach ($this->getParseableFiles($directory) as $filename) {
            $extension   = pathinfo($filename, PATHINFO_EXTENSION);
            $parserClass = ParserFactory::createByExtension($extension);

            if ($parserClass !== NULL) {
                $fullPath               = $directory . '/' . $filename;
                $fileParsers[$fullPath] = new $parserClass();
            }
        }

        return $fileParsers;
    }

    private function getParseableFiles(string $directory): array {
        $entries = scandir($directory) ?: [];

        return array_values(
            array_filter(
                $entries,
                static fn($entry): bool => $entry !== '.' && $entry !== '..'
            )
        );
    }

    private function processFiles(array $fileParsers): void {
        $packages = $this->parseFiles($fileParsers);
        if (!empty($packages)) {
            $this->ensureOutputDirectory();
            $packagesText = $this->formatPackagesText($packages);
            file_put_contents(OUTPUT_DIR . "/" . OUTPUT_FILE, $packagesText);
        }
    }

    private function parseFiles(array $fileParsers): array {
        $parsedItems = [];
        foreach ($fileParsers as $filePath => $parser) {
            if (!file_exists($filePath)) {
                $this->handleError(new RuntimeException("File not found: $filePath"));
                continue;
            }

            try {
                $content = file_get_contents($filePath);
                $data    = $parser->parse($content);
                $items   = $parser->getItems($data);
                if (is_array($items)) {
                    $parsedItems[] = $items;
                }
            }
            catch (JsonException|Throwable $e) {
                $this->handleError($e);
            }
        }

        return array_merge(...$parsedItems);
    }

    private function ensureOutputDirectory(): void {
        if (!is_dir(OUTPUT_DIR) && !mkdir(OUTPUT_DIR, 0777, TRUE) && !is_dir(OUTPUT_DIR)) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', OUTPUT_DIR));
        }
    }

    private function formatPackagesText(array $packages): string {
        ksort($packages);
        $lines = array_map(
            static fn($name, $version) => "\t$name@$version",
            array_keys($packages),
            $packages
        );

        return 'CRATES="' . implode("\n", $lines) . "\n\"";
    }

    private function handleError(Exception $e): void {
        echo "Error: " . $e->getMessage() . "\n";
    }
}