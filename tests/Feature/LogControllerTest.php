<?php

beforeEach(function () {
    $this->tempDir = sys_get_temp_dir().'/kick-tests-'.uniqid();
    mkdir($this->tempDir, 0755, true);

    config(['kick.logs.path' => $this->tempDir]);

    // Create test log file
    $content = "[2024-01-01 12:00:00] production.INFO: Test info message\n[2024-01-01 12:00:01] production.ERROR: Test error message\n[2024-01-01 12:00:02] production.WARNING: Test warning message";
    file_put_contents($this->tempDir.'/laravel.log', $content);
});

afterEach(function () {
    if (is_dir($this->tempDir)) {
        array_map('unlink', glob($this->tempDir.'/*') ?: []);
        rmdir($this->tempDir);
    }
});

describe('authentication', function () {
    it('returns 401 without token', function () {
        $this->getJson('/kick/logs')
            ->assertStatus(401)
            ->assertJson(['message' => 'Authentication token required.']);
    });

    it('returns 401 with invalid token', function () {
        $this->getJson('/kick/logs', [
            'Authorization' => 'Bearer invalid-token',
        ])
            ->assertStatus(401)
            ->assertJson(['message' => 'Invalid authentication token.']);
    });

    it('returns 403 with insufficient scope', function () {
        $this->getJson('/kick/logs', [
            'Authorization' => 'Bearer test-token-limited',
        ])
            ->assertStatus(403)
            ->assertJson(['message' => 'Token does not have required scope: logs:read']);
    });

    it('allows access with correct scope', function () {
        $this->getJson('/kick/logs', [
            'Authorization' => 'Bearer test-token-logs',
        ])
            ->assertStatus(200);
    });

    it('allows access with wildcard scope', function () {
        $this->getJson('/kick/logs', [
            'Authorization' => 'Bearer test-token-full',
        ])
            ->assertStatus(200);
    });
});

describe('list logs endpoint', function () {
    it('lists available log files', function () {
        file_put_contents($this->tempDir.'/worker.log', 'worker content');

        $this->getJson('/kick/logs', [
            'Authorization' => 'Bearer test-token-full',
        ])
            ->assertStatus(200)
            ->assertJsonStructure([
                'files' => [
                    '*' => ['name', 'size', 'modified'],
                ],
            ])
            ->assertJsonCount(2, 'files');
    });

    it('returns empty files array when no logs exist', function () {
        array_map('unlink', glob($this->tempDir.'/*') ?: []);

        $this->getJson('/kick/logs', [
            'Authorization' => 'Bearer test-token-full',
        ])
            ->assertStatus(200)
            ->assertJson(['files' => []]);
    });
});

describe('read log endpoint', function () {
    it('reads log file entries', function () {
        $this->getJson('/kick/logs/laravel.log', [
            'Authorization' => 'Bearer test-token-full',
        ])
            ->assertStatus(200)
            ->assertJsonStructure([
                'file',
                'entries' => [
                    '*' => ['line', 'content'],
                ],
                'total_lines',
                'has_more',
                'lines_requested',
                'offset',
            ])
            ->assertJson([
                'file' => 'laravel.log',
                'total_lines' => 3,
            ]);
    });

    it('filters by log level', function () {
        $this->getJson('/kick/logs/laravel.log?level=ERROR', [
            'Authorization' => 'Bearer test-token-full',
        ])
            ->assertStatus(200)
            ->assertJson([
                'total_lines' => 1,
            ]);
    });

    it('filters by search term', function () {
        $this->getJson('/kick/logs/laravel.log?search=warning', [
            'Authorization' => 'Bearer test-token-full',
        ])
            ->assertStatus(200)
            ->assertJson([
                'total_lines' => 1,
            ]);
    });

    it('returns 400 for non-existent file', function () {
        $this->getJson('/kick/logs/missing.log', [
            'Authorization' => 'Bearer test-token-full',
        ])
            ->assertStatus(400)
            ->assertJson([
                'error' => 'Log file not found: missing.log',
            ]);
    });

    it('returns 400 for path traversal attempt', function () {
        $this->getJson('/kick/logs/..laravel.log', [
            'Authorization' => 'Bearer test-token-full',
        ])
            ->assertStatus(400)
            ->assertJson([
                'error' => 'Invalid filename.',
            ]);
    });

    it('returns 400 for disallowed file extension', function () {
        $this->getJson('/kick/logs/secret.txt', [
            'Authorization' => 'Bearer test-token-full',
        ])
            ->assertStatus(400)
            ->assertJson([
                'error' => 'File type not allowed.',
            ]);
    });

    it('respects lines parameter', function () {
        $lines = [];
        for ($i = 1; $i <= 50; $i++) {
            $lines[] = "[2024-01-01 12:00:{$i}0] production.INFO: Line {$i}";
        }
        file_put_contents($this->tempDir.'/large.log', implode("\n", $lines));

        $this->getJson('/kick/logs/large.log?lines=10', [
            'Authorization' => 'Bearer test-token-full',
        ])
            ->assertStatus(200)
            ->assertJsonCount(10, 'entries')
            ->assertJson([
                'has_more' => true,
            ]);
    });

    it('supports pagination with offset', function () {
        $lines = [];
        for ($i = 1; $i <= 50; $i++) {
            $lines[] = "[2024-01-01 12:00:{$i}0] production.INFO: Line {$i}";
        }
        file_put_contents($this->tempDir.'/large.log', implode("\n", $lines));

        $response = $this->getJson('/kick/logs/large.log?lines=10&offset=40', [
            'Authorization' => 'Bearer test-token-full',
        ])
            ->assertStatus(200)
            ->assertJsonCount(10, 'entries')
            ->assertJson([
                'has_more' => false,
            ]);
    });
});
