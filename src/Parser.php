<?php

namespace App;

//require __DIR__ . '/../vendor/autoload.php';

use App\Parser\ParserFactory;
use Exception;
use JsonException;
use RuntimeException;
use Throwable;

class Parser {

    private const ALLOWED_FILE_EXTENSIONS = ['json', 'toml', 'lock'];

    private array $parsersPool = [];

    private array $filesToProceedPool = [];

    public function __construct(protected string $filesForParseDirName,
                                protected string $outputDirName,
                                protected string $outputFileName
    ) {
    }

    public function run(): void {
        $this->process();
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
     * Fill a list with files and the parsers pool.
     * The method scans a specified directory for parseable files,
     * determines the appropriate parser for each file based on its extension,
     * and add a new instance of parser to the pool.
     */
    private function prepareFilesAndParsers(): void {
        $directory = $this->filesForParseDirName;

        foreach ($this->getParseableFiles($directory) as $filename) {
            $extension = pathinfo($filename, PATHINFO_EXTENSION);

            if (!$this->isAllowedExtension($extension)) {
                continue;
            }

            $this->parsersPool[$extension] ??= ParserFactory::createByExtension($extension);
            $this->filesToProceedPool[]    = $directory . '/' . $filename;
        }
    }

    /**
     * Check if a given file extension is allowed for parsing.
     *
     * The extension is normalized to lower case and compared against
     * the class constant `ALLOWED_FILE_EXTENSIONS`.
     *
     * @param string $extension File extension to check.
     *
     * @return bool True if the extension is allowed, false otherwise.
     */
    private function isAllowedExtension(string $extension): bool {
        return in_array(strtolower($extension), self::ALLOWED_FILE_EXTENSIONS, TRUE);
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

        if (empty($packages)) {
            return;
        }

        $this->ensureOutputDirectory();
        file_put_contents($this->outputDirName . "/" . $this->outputFileName, $this->formatPackagesText($packages));
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
                $extension = pathinfo($file, PATHINFO_EXTENSION);
                $parser    = $this->parsersPool[$extension];
                $data      = $parser->parse(file_get_contents($file));
                $items     = $parser->getItems($data);

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
        if (!is_dir($this->outputDirName) && !mkdir($this->outputDirName, 0777, TRUE) && !is_dir($this->outputDirName)) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $this->outputDirName));
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
        $lines = [];

        foreach ($packages as $name => $version) {
            $lines[] = empty($lines) ? "$name@$version" : "\t$name@$version";

        }

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