<?php

declare(strict_types=1);

namespace Psl\Example\Shell;

use Psl\Async;
use Psl\IO;
use Psl\Shell;

require __DIR__ . '/../../vendor/autoload.php';

Async\main(static function (): void {
    try {
        Shell\execute('sleep', ['1'], timeout_ms: 500000);
    } catch (Shell\Exception\TimeoutException $exception) {
        IO\output_handle()->write($exception->getMessage() . "\n");
    }
});