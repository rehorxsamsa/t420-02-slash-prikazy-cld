<?php

declare(strict_types=1);

/**
 * Minimalistický test runner bez frameworku.
 * Spuštění:  php tests/run.php
 *
 * Každý soubor tests/*Test.php vrací pole pojmenovaných testů (callable).
 * Test používá funkce assert_true / assert_same definované níže.
 */

require dirname(__DIR__) . '/autoload.php';

// Autoloader pro testovací helpery: Tests\ -> tests/
spl_autoload_register(static function (string $class): void {
    $prefix = 'Tests\\';
    if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    $file = __DIR__ . '/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

$passed = 0;
$failed = 0;
$failures = [];

/**
 * @param mixed $expected
 * @param mixed $actual
 */
function assert_same(mixed $expected, mixed $actual, string $message = ''): void
{
    if ($expected !== $actual) {
        throw new RuntimeException(
            sprintf(
                "%s\n    očekáváno: %s\n    skutečnost: %s",
                $message !== '' ? $message : 'assert_same selhalo',
                var_export($expected, true),
                var_export($actual, true),
            )
        );
    }
}

function assert_true(bool $condition, string $message = ''): void
{
    if (!$condition) {
        throw new RuntimeException($message !== '' ? $message : 'assert_true selhalo');
    }
}

function assert_throws(string $exceptionClass, callable $fn, string $message = ''): void
{
    try {
        $fn();
    } catch (\Throwable $e) {
        if ($e instanceof $exceptionClass) {
            return;
        }
        throw new RuntimeException(
            sprintf('Očekávána výjimka %s, ale přišla %s', $exceptionClass, $e::class)
        );
    }
    throw new RuntimeException(
        $message !== '' ? $message : sprintf('Očekávána výjimka %s, ale žádná nepřišla', $exceptionClass)
    );
}

// Načti všechny *Test.php
$testFiles = glob(__DIR__ . '/*Test.php') ?: [];

foreach ($testFiles as $file) {
    /** @var array<string, callable> $tests */
    $tests = require $file;
    $suite = basename($file);

    foreach ($tests as $name => $test) {
        try {
            $test();
            $passed++;
            echo "  ✅ {$suite} :: {$name}\n";
        } catch (\Throwable $e) {
            $failed++;
            $failures[] = "{$suite} :: {$name}\n     {$e->getMessage()}";
            echo "  ❌ {$suite} :: {$name}\n";
        }
    }
}

echo "\n";
echo str_repeat('─', 40) . "\n";
echo sprintf("Prošlo: %d   Selhalo: %d\n", $passed, $failed);

if ($failed > 0) {
    echo "\nDetaily selhání:\n";
    foreach ($failures as $f) {
        echo "  • {$f}\n";
    }
    exit(1);
}

echo "Vše OK ✨\n";
exit(0);
