This script reads all PHP DTOs from php/Models and parses them to perform necessary transformations for php.

## Used packages

* symfony/finder for traversing php-files
* nikic/php-parser for extracting and modifying the PHP-AST.


## Usage
    $ composer --working-dir ./scripts/php/ install
    $ composer --working-dir ./scripts/php/ parse

    $ pnpm build:php:parse

## Performed modifications

* Added `OfcpEvent` as interface to DTOs for de-/serialization.

  `OfcpEvent` extends `\JsonSerialize` and introduces `OfcpEvent::from_json(string|array $json)` as method

* Removed `\JsonSerialize` from DTOs introduced by modelina.