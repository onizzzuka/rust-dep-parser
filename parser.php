<?php

require __DIR__ . '/vendor/autoload.php';

use App\Parser\ParserFactory;

const FILES_FOR_PARSE_DIR = __DIR__ . "/files_for_parse";
const OUTPUT_DIR          = __DIR__ . "/output";
const OUTPUT_FILE         = "packages.txt";

const ALLOWED_FILE_EXTENSIONS = ['json', 'toml', 'lock'];

Parser::run();

class Parser {

    private array $parsersPool = [];

    private array $filesToProceedPool = [];

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
            $this->prepareFilesAndParsers();
            $this->processFiles();
        }
        catch (RuntimeException $e) {
            $this->handleError($e);
        }
    }

    /**
     * Fill a list of files and the parsers pool.
     * The method scans a specified directory for parseable files,
     * determines the appropriate parser for each file based on its extension,
     * and add a new instance of parser to the pool.
     */
    private function prepareFilesAndParsers(): void {
        $directory = FILES_FOR_PARSE_DIR;

        foreach ($this->getParseableFiles($directory) as $filename) {
            $extension = pathinfo($filename, PATHINFO_EXTENSION);

            $this->addParserToPool($extension);
            $this->addFileToProceedPool($directory, $filename, $extension);
        }
    }

    /**
     * Adds a parser instance to the internal pool for the specified file extension.
     *
     * If no parser is currently stored for the given extension and the extension
     * is listed in {@see ALLOWED_FILE_EXTENSIONS}, a new parser is created via
     * {@see ParserFactory::createByExtension} and added to the pool.
     *
     * @param string $extension The file extension for which to add a parser.
     *
     * @return void
     */
    private function addParserToPool(string $extension): void {
        if (!isset($this->parsersPool[$extension]) && in_array(strtolower($extension), ALLOWED_FILE_EXTENSIONS)) {
            $this->parsersPool[$extension] = ParserFactory::createByExtension($extension);
        }
    }

    /**
     * Adds a file to the internal pool of files to be processed based on {@see ALLOWED_FILE_EXTENSIONS}.
     *
     * @param string $directory The directory path where the file resides.
     * @param string $filename  The name of the file to add.
     * @param string $extension The file extension, used for validation against allowed extensions.
     */
    private function addFileToProceedPool(string $directory, string $filename, string $extension): void {
        if (in_array(strtolower($extension), ALLOWED_FILE_EXTENSIONS)) {
            $this->filesToProceedPool[] = $directory . '/' . $filename;
        }

    }

    /**
     * Retrieves a list of parseable files from a specified directory.
     *
     * @param string $directory The path to the directory to scan.
     *
     * @return array An array of filenames contained in the directory,
     *               excluding '.' and '..'.
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
     * Processes an array of file parsers to parse files, format package data,
     * and save the output to a file.
     *
     * @return void
     */
    private function processFiles(): void {
        $packages = $this->parseFiles();
        if (!empty($packages)) {
            $this->ensureOutputDirectory();
            $packagesText = $this->formatPackagesText($packages);
            file_put_contents(OUTPUT_DIR . "/" . OUTPUT_FILE, $packagesText);
        }
    }

    /**
     * Parses files using the provided file parsers and extracts items from
     * their contents.
     *
     * @return array A merged array of items extracted from the parsed files.
     *               Returns an empty array if no valid items are extracted or
     *               no files are successfully parsed.
     */
    private function parseFiles(): array {
        $parsedItems = [];
        foreach ($this->filesToProceedPool as $file) {
            if (!file_exists($file)) {
                $this->handleError(new RuntimeException("File not found: $file"));
                continue;
            }

            try {
                $content = file_get_contents($file);

                $extension = pathinfo($file, PATHINFO_EXTENSION);
                $parser    = $this->parsersPool[$extension];

                $data  = $parser->parse($content);
                $items = $parser->getItems($data);

                if (is_array($items)) {
                    $parsedItems[] = $items;
                }
            }
            catch (JsonException|Throwable $e) {
                $this->handleError($e);
            }
        }

        return empty($parsedItems) ? [] : array_merge(...$parsedItems);
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
     * Formats an array of package names and versions into a specific text
     * structure.
     *
     * @param array $packages An associative array where keys are package names
     *                        and values are their versions.
     *
     * @return string A formatted string containing the packages and versions,
     *                each on a new line, prefixed with a tab character.
     */
    private function formatPackagesText(array $packages): string {
        ksort($packages);
        $lines_counter = 0;

        $lines = array_map(
            static function ($name, $version) use (&$lines_counter) {
                return $lines_counter++ === 0 ? "$name@$version" : "\t$name@$version";
            },
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