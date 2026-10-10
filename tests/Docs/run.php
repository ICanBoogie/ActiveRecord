<?php

/**
 * Runs a PHP block of the documentation, after the setup blocks.
 *
 * Usage: php tests/Docs/run.php <file> <line>
 *
 * Without arguments, only the setup blocks are run.
 */

namespace Test\ICanBoogie\Docs;

use ErrorException;
use LogicException;

$root = dirname(__DIR__, 2);

require "$root/vendor/autoload.php";

set_error_handler(function (int $severity, string $message, string $file, int $line): bool {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

date_default_timezone_set('UTC');

/**
 * Includes code in the global scope, so that variables are shared between the blocks.
 */
function doc_test_code_file(DocBlock $block, string $code): string
{
    $file = tempnam(sys_get_temp_dir(), 'doc-test-');
    file_put_contents($file, $code);
    register_shutdown_function(fn() => @unlink($file));

    return $file;
}

$target = null;

if (isset($argv[2])) {
    foreach (DocBlock::from_file($root, $argv[1]) as $block) {
        if ($block->line === (int) $argv[2]) {
            $target = $block;
        }
    }

    $target ?? throw new LogicException("No block at $argv[1]:$argv[2]");
}

foreach (DocBlock::setup_blocks($root) as $doc_test_block) {
    // The setup uses the database of the tests.
    $doc_test_code = str_replace(
        "'sqlite::memory:'",
        '\Test\ICanBoogie\Fixtures::dsn(), \Test\ICanBoogie\Fixtures::username(), \Test\ICanBoogie\Fixtures::password()',
        $doc_test_block->code
    );

    include doc_test_code_file($doc_test_block, $doc_test_code);
}

require __DIR__ . '/prelude.php';

if ($target) {
    foreach ($target->var_annotations() as $doc_test_name => $doc_test_type) {
        isset($$doc_test_name)
            or throw new LogicException("$target->id: \$$doc_test_name is not defined");
        $$doc_test_name instanceof $doc_test_type
            or throw new LogicException(
                "$target->id: \$$doc_test_name is a " . get_debug_type($$doc_test_name) . ", not a $doc_test_type"
            );
    }

    include doc_test_code_file($target, $target->code);
}

echo "\nOK\n";
