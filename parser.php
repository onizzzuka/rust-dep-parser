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

    /**
     * Processes files by finding parsers, processing the files,
     * and handling potential runtime exceptions.
     *
     * @return void
     */
    private function process(): void {
        try {
            $fileParsers = $this->findFilesWithParsers();
            $this->processFiles($fileParsers);
        }
        catch (RuntimeException $e) {
            $this->handleError($e);
        }
    }

    /**
     * Finds and returns a list of files along with their associated parsers.
     * The method scans a specified directory for parseable files,
     * determines the appropriate parser for each file based on its extension,
     * and maps the file paths to their respective parser instances.
     *
     * @return array An associative array where the keys are file paths and the values are parser instances.
     */
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

    /**
     * Retrieves a list of parseable files from a specified directory.
     *
     * @param string $directory The path to the directory to scan.
     *
     * @return array An array of filenames contained in the directory, excluding '.' and '..'.
     */
    private function getParseableFiles(string $directory): array {
        $entries = scandir($directory) ?: [];

        return array_values(
            array_filter(
                $entries,
                static fn($entry): bool => $entry !== '.' && $entry !== '..'
            )
        );
    }

    /**
     * Processes an array of file parsers to parse files, format package data, and save the output to a file.
     *
     * @param array $fileParsers An array of file parsers used to process the files.
     *
     * @return void
     */
    private function processFiles(array $fileParsers): void {
        $packages = $this->parseFiles($fileParsers);
        if (!empty($packages)) {
            $this->ensureOutputDirectory();
            $packagesText = $this->formatPackagesText($packages);
            file_put_contents(OUTPUT_DIR . "/" . OUTPUT_FILE, $packagesText);
        }
    }

    /**
     * Parses files using the provided file parsers and extracts items from their contents.
     *
     * @param array $fileParsers An associative array where the keys are file paths and the values are parser instances. Each parser must implement methods to parse file content and extract items.
     *
     * @return array A merged array of items extracted from the parsed files. Returns an empty array if no valid items are extracted or no files are successfully parsed.
     */
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

    /**
     * Ensures that the output directory exists, creating it if necessary.
     *
     * @return void
     * @throws RuntimeException If the output directory cannot be created.
     */
    private function ensureOutputDirectory(): void {
        if (!is_dir(OUTPUT_DIR) && !mkdir(OUTPUT_DIR, 0777, TRUE) && !is_dir(OUTPUT_DIR)) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', OUTPUT_DIR));
        }
    }

    /**
     * Formats an array of package names and versions into a specific text structure.
     *
     * @param array $packages An associative array where keys are package names and values are their versions.
     *
     * @return string A formatted string containing the packages and versions, each on a new line, prefixed with a tab character.
     */
    private function formatPackagesText(array $packages): string {
        ksort($packages);
        $lines = array_map(
            static fn($name, $version) => "\t$name@$version",
            array_keys($packages),
            $packages
        );

        return 'CRATES="' . implode("\n", $lines) . "\n\"";
    }

    /**
     * Handles an exception by outputting its message.
     *
     * @param Exception $e The exception to handle.
     *
     * @return void
     */
    private function handleError(Exception $e): void {
        echo "Error: " . $e->getMessage() . "\n";
    }
}