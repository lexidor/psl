<?php

declare(strict_types=1);

namespace Psl\Example\IO;

use Psl\Async;
use Psl\IO;
use Psl\Regex;
use function fopen;
use function getopt;
use function memory_get_peak_usage;
use function microtime;
use function round;
use function strlen;

require __DIR__ . '/../../vendor/autoload.php';

Async\main(static function (): int {

    $args = getopt('i:o:t:');
    $input_file = $args['i'] ?? '/dev/zero';
    $output_file = $args['o'] ?? '/dev/null';
    $seconds = (int)($args['t'] ?? 5);

    // passing file descriptors requires mapping paths (https://bugs.php.net/bug.php?id=53465)
    $input_file = Regex\replace($input_file, '(^/dev/fd/)', 'php://fd/');
    $output_file = Regex\replace($output_file, '(^/dev/fd/)', 'php://fd/');

    $stdout = IO\output_handle();
    $input = new IO\Stream\CloseReadHandle(fopen($input_file, 'rb'));
    $output = new IO\Stream\CloseWriteHandle(fopen($output_file, 'wb'));

    $stdout->writeAll('piping from ' . $input_file . ' to ' . $output_file . ' (for max ' . $seconds . ' second(s)) ...' . PHP_EOL);

    Async\Scheduler::delay($seconds * 1000000, $input->close(...));

    $start = microtime(true);
    $bytes = 0;

    try {
        while (($chunk = $input->readAll(65536))) {
            $output->writeAll($chunk);
            $bytes += strlen($chunk);

            Async\later();
        }
    } catch (IO\Exception\AlreadyClosedException) {
    }

    $seconds = microtime(true) - $start;

    $bytes_formatted = round($bytes / 1024 / 1024 / $seconds, 1);

    $stdout->writeAll('read ' . $bytes . ' byte(s) in ' . round($seconds, 3) . ' second(s) => ' . $bytes_formatted . ' MiB/s' . PHP_EOL);
    $stdout->writeAll('peak memory usage of ' . round(memory_get_peak_usage(true) / 1024 / 1024, 1) . ' MiB' . PHP_EOL);

    return 0;
});