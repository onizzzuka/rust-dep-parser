# Rust Dependencies Parser

A small PHP tool to parse dependency files from Rust projects. Supports `.json`, `.toml` and `.lock` formats and extracts dependency information into structured output files.

Files to parse should be placed in the `files_for_parse` directory. Processed output will be written to the `output` directory.

## Features

- Parse Rust dependency files: `.json`, `.toml`, `.lock`
- Simple CLI usage via PHP
- Outputs results to `output` for further processing

## Requirements

- PHP
- Composer

## Installation

1. Install PHP and Composer on your system.
2. Install PHP dependencies:

   composer install

## Usage

1. Place dependency files into `files_for_parse`.
2. Run the parser:

   php parser.php

3. Check results in the `output` directory.

## Project layout

- `files_for_parse` — input files (put .json/.toml/.lock here)
- `output` — generated output files

## License

This project is available under the MIT License.
