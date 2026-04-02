<?php

declare(strict_types=1);

/**
 * XMF ID Generation Benchmark
 *
 * Compares performance of:
 * - XMF ULID
 * - Ramsey UUID v4 (random)
 * - Ramsey UUID v7 (time-ordered)
 * - Native uniqid()
 * - Auto-increment simulation
 *
 * Usage:
 *   php Benchmarks/IdBenchmark.php
 *   php Benchmarks/IdBenchmark.php --iterations=100000
 *   php Benchmarks/IdBenchmark.php --database
 *
 * @package   Xmf
 * @author    XOOPS Development Team
 * @copyright 2026 XOOPS Project
 * @license   GPL-2.0-or-later
 */

namespace Xmf\Benchmarks;

require_once __DIR__ . '/../Ulid.php';

use Xmf\Ulid;

final class IdBenchmark
{
    private const DEFAULT_ITERATIONS = 10000;
    private const WARMUP_ITERATIONS = 1000;

    private int $iterations;
    private bool $includeDatabaseTests;
    private array $results = [];

    public function __construct(int $iterations = self::DEFAULT_ITERATIONS, bool $includeDatabaseTests = false)
    {
        $this->iterations = $iterations;
        $this->includeDatabaseTests = $includeDatabaseTests;
    }

    public function run(): void
    {
        $this->printHeader();
        $this->warmup();

        echo "\n📊 Running benchmarks with {$this->iterations} iterations...\n\n";

        // Generation benchmarks
        $this->benchmarkUlidGeneration();
        $this->benchmarkUuidV4Generation();
        $this->benchmarkUuidV7Generation();
        $this->benchmarkUniqidGeneration();
        $this->benchmarkAutoIncrement();

        // Parsing benchmarks
        echo "\n📥 Parsing Benchmarks:\n";
        $this->benchmarkUlidParsing();
        $this->benchmarkUuidParsing();

        // Comparison benchmarks
        echo "\n⚖️ Comparison Benchmarks:\n";
        $this->benchmarkUlidComparison();
        $this->benchmarkUuidComparison();

        // Sorting benchmarks
        echo "\n📈 Sorting Benchmarks (1000 IDs):\n";
        $this->benchmarkUlidSorting();
        $this->benchmarkUuidSorting();

        // Database benchmarks (optional)
        if ($this->includeDatabaseTests) {
            echo "\n💾 Database Benchmarks:\n";
            $this->benchmarkDatabaseOperations();
        }

        $this->printSummary();
    }

    private function warmup(): void
    {
        echo "🔥 Warming up...\n";

        for ($i = 0; $i < self::WARMUP_ITERATIONS; $i++) {
            Ulid::generate();
        }
    }

    // =========================================================================
    // Generation Benchmarks
    // =========================================================================

    private function benchmarkUlidGeneration(): void
    {
        $start = hrtime(true);

        for ($i = 0; $i < $this->iterations; $i++) {
            $ulid = Ulid::generate();
            $string = $ulid->toString();
        }

        $elapsed = $this->elapsed($start);
        $this->recordResult('ULID Generation', $elapsed);
    }

    private function benchmarkUuidV4Generation(): void
    {
        // Simulate UUID v4 generation (random)
        $start = hrtime(true);

        for ($i = 0; $i < $this->iterations; $i++) {
            $uuid = sprintf(
                '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                random_int(0, 0xffff),
                random_int(0, 0xffff),
                random_int(0, 0xffff),
                random_int(0, 0x0fff) | 0x4000,
                random_int(0, 0x3fff) | 0x8000,
                random_int(0, 0xffff),
                random_int(0, 0xffff),
                random_int(0, 0xffff)
            );
        }

        $elapsed = $this->elapsed($start);
        $this->recordResult('UUID v4 Generation', $elapsed);
    }

    private function benchmarkUuidV7Generation(): void
    {
        // Simulate UUID v7 generation (time-ordered)
        $start = hrtime(true);

        for ($i = 0; $i < $this->iterations; $i++) {
            $time = (int) (microtime(true) * 1000);
            $uuid = sprintf(
                '%08x-%04x-7%03x-%04x-%04x%04x%04x',
                $time >> 16,
                $time & 0xffff,
                random_int(0, 0x0fff),
                random_int(0, 0x3fff) | 0x8000,
                random_int(0, 0xffff),
                random_int(0, 0xffff),
                random_int(0, 0xffff)
            );
        }

        $elapsed = $this->elapsed($start);
        $this->recordResult('UUID v7 Generation', $elapsed);
    }

    private function benchmarkUniqidGeneration(): void
    {
        $start = hrtime(true);

        for ($i = 0; $i < $this->iterations; $i++) {
            $id = uniqid('', true);
        }

        $elapsed = $this->elapsed($start);
        $this->recordResult('uniqid() Generation', $elapsed);
    }

    private function benchmarkAutoIncrement(): void
    {
        $start = hrtime(true);
        $counter = 0;

        for ($i = 0; $i < $this->iterations; $i++) {
            $id = ++$counter;
        }

        $elapsed = $this->elapsed($start);
        $this->recordResult('Auto-increment (simulated)', $elapsed);
    }

    // =========================================================================
    // Parsing Benchmarks
    // =========================================================================

    private function benchmarkUlidParsing(): void
    {
        $ulidString = '01HV8X5Z0KDMVR8SDPY62J9ACP';

        $start = hrtime(true);

        for ($i = 0; $i < $this->iterations; $i++) {
            $ulid = Ulid::fromString($ulidString);
        }

        $elapsed = $this->elapsed($start);
        $this->recordResult('ULID Parsing', $elapsed);
    }

    private function benchmarkUuidParsing(): void
    {
        $uuidString = '550e8400-e29b-41d4-a716-446655440000';

        $start = hrtime(true);

        for ($i = 0; $i < $this->iterations; $i++) {
            // Simulate UUID parsing (validation + normalization)
            $uuid = strtolower(str_replace('-', '', $uuidString));
            $valid = strlen($uuid) === 32 && ctype_xdigit($uuid);
        }

        $elapsed = $this->elapsed($start);
        $this->recordResult('UUID Parsing', $elapsed);
    }

    // =========================================================================
    // Comparison Benchmarks
    // =========================================================================

    private function benchmarkUlidComparison(): void
    {
        $ulid1 = Ulid::fromString('01HV8X5Z0KDMVR8SDPY62J9ACP');
        $ulid2 = Ulid::fromString('01HV8X5Z0KDMVR8SDPY62J9ACQ');

        $start = hrtime(true);

        for ($i = 0; $i < $this->iterations; $i++) {
            $ulid1->equals($ulid2);
            $ulid1->compareTo($ulid2);
        }

        $elapsed = $this->elapsed($start);
        $this->recordResult('ULID Comparison', $elapsed);
    }

    private function benchmarkUuidComparison(): void
    {
        $uuid1 = '550e8400-e29b-41d4-a716-446655440000';
        $uuid2 = '550e8400-e29b-41d4-a716-446655440001';

        $start = hrtime(true);

        for ($i = 0; $i < $this->iterations; $i++) {
            $equal = $uuid1 === $uuid2;
            $compare = strcmp($uuid1, $uuid2);
        }

        $elapsed = $this->elapsed($start);
        $this->recordResult('UUID Comparison', $elapsed);
    }

    // =========================================================================
    // Sorting Benchmarks
    // =========================================================================

    private function benchmarkUlidSorting(): void
    {
        // Generate 1000 ULIDs
        $ulids = [];
        for ($i = 0; $i < 1000; $i++) {
            $ulids[] = Ulid::generate()->toString();
            usleep(10); // Small delay for time variation
        }
        shuffle($ulids);

        $start = hrtime(true);

        for ($i = 0; $i < 100; $i++) {
            $copy = $ulids;
            sort($copy);
        }

        $elapsed = $this->elapsed($start);
        $this->recordResult('ULID Sorting (1000×100)', $elapsed);
    }

    private function benchmarkUuidSorting(): void
    {
        // Generate 1000 UUIDs
        $uuids = [];
        for ($i = 0; $i < 1000; $i++) {
            $uuids[] = sprintf(
                '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                random_int(0, 0xffff),
                random_int(0, 0xffff),
                random_int(0, 0xffff),
                random_int(0, 0x0fff) | 0x4000,
                random_int(0, 0x3fff) | 0x8000,
                random_int(0, 0xffff),
                random_int(0, 0xffff),
                random_int(0, 0xffff)
            );
        }
        shuffle($uuids);

        $start = hrtime(true);

        for ($i = 0; $i < 100; $i++) {
            $copy = $uuids;
            sort($copy);
        }

        $elapsed = $this->elapsed($start);
        $this->recordResult('UUID Sorting (1000×100)', $elapsed);
    }

    // =========================================================================
    // Database Benchmarks
    // =========================================================================

    private function benchmarkDatabaseOperations(): void
    {
        echo "  ⚠️ Database benchmarks require MySQL connection.\n";
        echo "  Set DB_HOST, DB_NAME, DB_USER, DB_PASS environment variables.\n";

        $host = getenv('DB_HOST') ?: 'localhost';
        $name = getenv('DB_NAME') ?: 'xoops_benchmark';
        $user = getenv('DB_USER') ?: 'root';
        $pass = getenv('DB_PASS') ?: '';

        try {
            $pdo = new \PDO(
                "mysql:host={$host};dbname={$name};charset=utf8mb4",
                $user,
                $pass,
                [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
            );

            $this->setupBenchmarkTables($pdo);
            $this->benchmarkDatabaseInserts($pdo);
            $this->benchmarkDatabaseSelects($pdo);
            $this->benchmarkDatabaseRangeQueries($pdo);
            $this->cleanupBenchmarkTables($pdo);

        } catch (\PDOException $e) {
            echo "  ❌ Database connection failed: {$e->getMessage()}\n";
        }
    }

    private function setupBenchmarkTables(\PDO $pdo): void
    {
        $pdo->exec('DROP TABLE IF EXISTS bench_ulid');
        $pdo->exec('DROP TABLE IF EXISTS bench_uuid');
        $pdo->exec('DROP TABLE IF EXISTS bench_auto');

        $pdo->exec('
            CREATE TABLE bench_ulid (
                id CHAR(26) PRIMARY KEY,
                data VARCHAR(255)
            ) ENGINE=InnoDB
        ');

        $pdo->exec('
            CREATE TABLE bench_uuid (
                id CHAR(36) PRIMARY KEY,
                data VARCHAR(255)
            ) ENGINE=InnoDB
        ');

        $pdo->exec('
            CREATE TABLE bench_auto (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                data VARCHAR(255)
            ) ENGINE=InnoDB
        ');
    }

    private function benchmarkDatabaseInserts(\PDO $pdo): void
    {
        $iterations = min(1000, $this->iterations);

        // ULID inserts
        $stmt = $pdo->prepare('INSERT INTO bench_ulid (id, data) VALUES (?, ?)');
        $start = hrtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            $stmt->execute([Ulid::generate()->toString(), "data-{$i}"]);
        }
        $elapsed = $this->elapsed($start);
        $this->recordResult("DB Insert ULID ({$iterations})", $elapsed);

        // UUID inserts
        $stmt = $pdo->prepare('INSERT INTO bench_uuid (id, data) VALUES (?, ?)');
        $start = hrtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            $uuid = sprintf(
                '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                random_int(0, 0xffff), random_int(0, 0xffff),
                random_int(0, 0xffff), random_int(0, 0x0fff) | 0x4000,
                random_int(0, 0x3fff) | 0x8000, random_int(0, 0xffff),
                random_int(0, 0xffff), random_int(0, 0xffff)
            );
            $stmt->execute([$uuid, "data-{$i}"]);
        }
        $elapsed = $this->elapsed($start);
        $this->recordResult("DB Insert UUID ({$iterations})", $elapsed);

        // Auto-increment inserts
        $stmt = $pdo->prepare('INSERT INTO bench_auto (data) VALUES (?)');
        $start = hrtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            $stmt->execute(["data-{$i}"]);
        }
        $elapsed = $this->elapsed($start);
        $this->recordResult("DB Insert Auto-Inc ({$iterations})", $elapsed);
    }

    private function benchmarkDatabaseSelects(\PDO $pdo): void
    {
        $iterations = min(1000, $this->iterations);

        // Get sample IDs
        $ulids = $pdo->query('SELECT id FROM bench_ulid LIMIT 100')->fetchAll(\PDO::FETCH_COLUMN);
        $uuids = $pdo->query('SELECT id FROM bench_uuid LIMIT 100')->fetchAll(\PDO::FETCH_COLUMN);

        // ULID selects
        $stmt = $pdo->prepare('SELECT * FROM bench_ulid WHERE id = ?');
        $start = hrtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            $stmt->execute([$ulids[$i % count($ulids)]]);
            $stmt->fetch();
        }
        $elapsed = $this->elapsed($start);
        $this->recordResult("DB Select ULID ({$iterations})", $elapsed);

        // UUID selects
        $stmt = $pdo->prepare('SELECT * FROM bench_uuid WHERE id = ?');
        $start = hrtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            $stmt->execute([$uuids[$i % count($uuids)]]);
            $stmt->fetch();
        }
        $elapsed = $this->elapsed($start);
        $this->recordResult("DB Select UUID ({$iterations})", $elapsed);
    }

    private function benchmarkDatabaseRangeQueries(\PDO $pdo): void
    {
        // Range query (benefits ULID's sortability)
        $start = hrtime(true);
        for ($i = 0; $i < 100; $i++) {
            $stmt = $pdo->query('SELECT * FROM bench_ulid ORDER BY id LIMIT 100');
            $stmt->fetchAll();
        }
        $elapsed = $this->elapsed($start);
        $this->recordResult('DB Range ULID (100×100)', $elapsed);

        $start = hrtime(true);
        for ($i = 0; $i < 100; $i++) {
            $stmt = $pdo->query('SELECT * FROM bench_uuid ORDER BY id LIMIT 100');
            $stmt->fetchAll();
        }
        $elapsed = $this->elapsed($start);
        $this->recordResult('DB Range UUID (100×100)', $elapsed);
    }

    private function cleanupBenchmarkTables(\PDO $pdo): void
    {
        $pdo->exec('DROP TABLE IF EXISTS bench_ulid');
        $pdo->exec('DROP TABLE IF EXISTS bench_uuid');
        $pdo->exec('DROP TABLE IF EXISTS bench_auto');
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    private function elapsed(int $start): float
    {
        return (hrtime(true) - $start) / 1_000_000; // Convert to milliseconds
    }

    private function recordResult(string $name, float $elapsedMs): void
    {
        $opsPerSecond = ($this->iterations / $elapsedMs) * 1000;

        $this->results[$name] = [
            'elapsed_ms' => $elapsedMs,
            'ops_per_second' => $opsPerSecond,
        ];

        printf(
            "  %-30s %8.2f ms  (%s ops/sec)\n",
            $name,
            $elapsedMs,
            number_format($opsPerSecond, 0)
        );
    }

    private function printHeader(): void
    {
        echo "\n";
        echo "╔══════════════════════════════════════════════════════════════╗\n";
        echo "║           XMF ULID Performance Benchmark                     ║\n";
        echo "╚══════════════════════════════════════════════════════════════╝\n";
        echo "\n";
        echo "PHP Version: " . PHP_VERSION . "\n";
        echo "OS: " . PHP_OS . "\n";
        echo "Date: " . date('Y-m-d H:i:s') . "\n";
    }

    private function printSummary(): void
    {
        echo "\n";
        echo "╔══════════════════════════════════════════════════════════════╗\n";
        echo "║                        Summary                               ║\n";
        echo "╚══════════════════════════════════════════════════════════════╝\n";
        echo "\n";

        // Find fastest generation method
        $generationMethods = array_filter(
            $this->results,
            fn($k) => str_contains($k, 'Generation'),
            ARRAY_FILTER_USE_KEY
        );

        if (!empty($generationMethods)) {
            uasort($generationMethods, fn($a, $b) => $a['elapsed_ms'] <=> $b['elapsed_ms']);

            echo "🏆 Generation Speed Ranking:\n";
            $rank = 1;
            foreach ($generationMethods as $name => $result) {
                printf(
                    "   %d. %-25s %8.2f ms\n",
                    $rank++,
                    str_replace(' Generation', '', $name),
                    $result['elapsed_ms']
                );
            }
        }

        echo "\n";
        echo "📌 Key Insights:\n";
        echo "   • ULID provides time-ordered IDs with good generation speed\n";
        echo "   • UUID v4 is random and causes index fragmentation\n";
        echo "   • ULID's lexicographic sorting matches chronological order\n";
        echo "   • For high-volume tables, ULID + CHAR(26) is recommended\n";
        echo "\n";
    }
}

// =========================================================================
// CLI Entry Point
// =========================================================================

if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($argv[0] ?? '')) {
    $iterations = 10000;
    $database = false;

    foreach ($argv as $arg) {
        if (str_starts_with($arg, '--iterations=')) {
            $iterations = (int) substr($arg, 13);
        }
        if ($arg === '--database') {
            $database = true;
        }
        if ($arg === '--help' || $arg === '-h') {
            echo "Usage: php IdBenchmark.php [options]\n";
            echo "\n";
            echo "Options:\n";
            echo "  --iterations=N   Number of iterations (default: 10000)\n";
            echo "  --database       Include database benchmarks\n";
            echo "  --help, -h       Show this help\n";
            echo "\n";
            echo "Environment variables for database tests:\n";
            echo "  DB_HOST          Database host (default: localhost)\n";
            echo "  DB_NAME          Database name (default: xoops_benchmark)\n";
            echo "  DB_USER          Database user (default: root)\n";
            echo "  DB_PASS          Database password (default: empty)\n";
            exit(0);
        }
    }

    $benchmark = new IdBenchmark($iterations, $database);
    $benchmark->run();
}
