<?php

declare(strict_types=1);

use Alfred\Workflow\Hello;

error_reporting(E_ALL);
ini_set('display_errors', 'stderr');

require __DIR__.'/vendor/autoload.php';

require __DIR__.'/AlfredScriptFilterType.php';

/**
 * Dispatch a CLI task to the corresponding core class.
 *
 * @param list<string> $arguments
 */
function dispatchTask(string $task, array $arguments): string
{
    return match ($task) {
        'hello' => (new Hello())(...$arguments),
        default => throw new InvalidArgumentException(sprintf('Unknown task: %s', $task)),
    };
}

/**
 * Encode an Alfred Script Filter response as JSON.
 */
function toAlfredScriptFilterJson(AlfredSF $scriptFilter): string
{
    return json_encode(
        $scriptFilter,
        JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
    );
}

/**
 * Run the adapter with positional CLI arguments.
 *
 * @param list<string> $arguments
 */
function run(array $arguments): void
{
    $task = array_shift($arguments);

    if (null === $task) {
        throw new InvalidArgumentException('Usage: php AlfredAdapter.php <task> [argument ...]');
    }

    echo toAlfredScriptFilterJson(
        new AlfredSF(
            items: [
                new AlfredSFItem(title: dispatchTask($task, $arguments)),
            ],
        ),
    );
}

/**
 * Read and validate positional arguments supplied by the CLI runtime.
 *
 * @return list<string>
 */
function cliArguments(): array
{
    $arguments = $_SERVER['argv'] ?? null;

    if (!is_array($arguments)) {
        throw new RuntimeException('AlfredAdapter.php must be run from the command line.');
    }

    $cliArguments = [];

    foreach (array_slice($arguments, 1) as $argument) {
        if (!is_string($argument)) {
            throw new RuntimeException('CLI arguments must be strings.');
        }

        $cliArguments[] = $argument;
    }

    return $cliArguments;
}

try {
    run(cliArguments());
} catch (Throwable $exception) {
    echo toAlfredScriptFilterJson(RETURN_ERROR_ALFRED);

    exit(1);
}
