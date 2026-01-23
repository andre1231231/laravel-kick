<?php

use StuMason\Kick\Services\LogReader;

beforeEach(function () {
    $this->tempDir = sys_get_temp_dir().'/kick-tests-'.uniqid();
    mkdir($this->tempDir, 0755, true);
});

afterEach(function () {
    // Clean up temp directory
    if (is_dir($this->tempDir)) {
        array_map('unlink', glob($this->tempDir.'/*') ?: []);
        rmdir($this->tempDir);
    }
});

it('lists log files in directory', function () {
    file_put_contents($this->tempDir.'/laravel.log', 'test content');
    file_put_contents($this->tempDir.'/worker.log', 'worker content');
    file_put_contents($this->tempDir.'/not-a-log.txt', 'ignored');

    $reader = new LogReader($this->tempDir);
    $files = $reader->listFiles();

    expect($files)->toHaveCount(2);
    expect($files->pluck('name')->all())->toContain('laravel.log', 'worker.log');
});

it('returns empty collection for non-existent directory', function () {
    $reader = new LogReader('/non/existent/path');
    $files = $reader->listFiles();

    expect($files)->toBeEmpty();
});

it('reads log file content', function () {
    $content = "[2024-01-01 12:00:00] production.INFO: Test message\n[2024-01-01 12:00:01] production.ERROR: Error message";
    file_put_contents($this->tempDir.'/laravel.log', $content);

    $reader = new LogReader($this->tempDir);
    $result = $reader->read('laravel.log');

    expect($result['entries'])->toHaveCount(2);
    expect($result['total_lines'])->toBe(2);
});

it('filters log entries by level', function () {
    $content = "[2024-01-01 12:00:00] production.INFO: Info message\n[2024-01-01 12:00:01] production.ERROR: Error message\n[2024-01-01 12:00:02] production.INFO: Another info";
    file_put_contents($this->tempDir.'/laravel.log', $content);

    $reader = new LogReader($this->tempDir);
    $result = $reader->read('laravel.log', 100, 0, null, 'ERROR');

    expect($result['entries'])->toHaveCount(1);
    expect($result['entries'][0]['content'])->toContain('Error message');
});

it('filters log entries by search term', function () {
    $content = "[2024-01-01 12:00:00] production.INFO: User logged in\n[2024-01-01 12:00:01] production.INFO: Order created\n[2024-01-01 12:00:02] production.INFO: User logged out";
    file_put_contents($this->tempDir.'/laravel.log', $content);

    $reader = new LogReader($this->tempDir);
    $result = $reader->read('laravel.log', 100, 0, 'User');

    expect($result['entries'])->toHaveCount(2);
});

it('paginates log entries', function () {
    $lines = [];
    for ($i = 1; $i <= 10; $i++) {
        $lines[] = "[2024-01-01 12:00:{$i}0] production.INFO: Line {$i}";
    }
    file_put_contents($this->tempDir.'/laravel.log', implode("\n", $lines));

    $reader = new LogReader($this->tempDir);

    $firstPage = $reader->read('laravel.log', 3, 0);
    expect($firstPage['entries'])->toHaveCount(3);
    expect($firstPage['has_more'])->toBeTrue();

    $secondPage = $reader->read('laravel.log', 3, 3);
    expect($secondPage['entries'])->toHaveCount(3);
    expect($secondPage['has_more'])->toBeTrue();
});

it('respects max lines limit', function () {
    $lines = [];
    for ($i = 1; $i <= 1000; $i++) {
        $lines[] = "Line {$i}";
    }
    file_put_contents($this->tempDir.'/laravel.log', implode("\n", $lines));

    $reader = new LogReader($this->tempDir, ['log'], 500);
    $result = $reader->read('laravel.log', 1000);

    expect($result['entries'])->toHaveCount(500);
});

it('prevents path traversal attacks', function () {
    $reader = new LogReader($this->tempDir);

    expect(fn () => $reader->read('../../../etc/passwd'))
        ->toThrow(InvalidArgumentException::class, 'Invalid filename');
});

it('rejects disallowed file extensions', function () {
    file_put_contents($this->tempDir.'/secret.txt', 'sensitive data');

    $reader = new LogReader($this->tempDir);

    expect(fn () => $reader->read('secret.txt'))
        ->toThrow(InvalidArgumentException::class, 'File type not allowed');
});

it('throws exception for non-existent file', function () {
    $reader = new LogReader($this->tempDir);

    expect(fn () => $reader->read('missing.log'))
        ->toThrow(InvalidArgumentException::class, 'Log file not found');
});

it('can tail recent entries', function () {
    $lines = [];
    for ($i = 1; $i <= 100; $i++) {
        $lines[] = "[2024-01-01 12:00:{$i}0] production.INFO: Line {$i}";
    }
    file_put_contents($this->tempDir.'/laravel.log', implode("\n", $lines));

    $reader = new LogReader($this->tempDir);
    $entries = $reader->tail('laravel.log', 5);

    expect($entries)->toHaveCount(5);
});
