<?php

namespace Tests\Feature;

use App\Models\Registration;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class CheckInConcurrencyTest extends TestCase
{
    #[RunInSeparateProcess]
    public function test_two_overlapping_check_ins_have_exactly_one_winner_even_without_a_shared_cache_lock(): void
    {
        $directory = storage_path('framework/testing/check-in-race-'.Str::uuid());
        mkdir($directory, 0777, true);
        $database = $directory.'/attendance.sqlite';
        touch($database);
        $processes = [];
        try {
            config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => $database, 'database.connections.sqlite.url' => null]);
            DB::purge('sqlite');
            Artisan::call('migrate', ['--force' => true, '--no-interaction' => true]);
            $registration = Registration::factory()->create(['status' => 'new',
                'pass_id' => (string) Str::uuid(), 'pass_qr_version' => 1, 'pass_issued_at' => now()]);
            $worker = $directory.'/worker.php';
            file_put_contents($worker, <<<'PHP'
<?php
require getcwd().'/vendor/autoload.php';
$app = require getcwd().'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
class BarrierLock extends App\Services\RegistrationWorkflowLock
{
    public function __construct(private string $barrier, private string $worker) {}
    public function run(App\Models\Registration $registration, Closure $operation): mixed
    {
        $current = App\Models\Registration::findOrFail($registration->id);
        file_put_contents($this->barrier.'.'.$this->worker, 'ready');
        $deadline = microtime(true) + 15;
        while (count(glob($this->barrier.'.*')) < 2) {
            if (microtime(true) > $deadline) { throw new RuntimeException('Concurrent test barrier timed out.'); }
            usleep(1000);
        }
        return $operation($current);
    }
}
$service = new App\Services\RegistrationCheckInService(new App\Services\PassQrCode, new BarrierLock($argv[1], $argv[2]));
echo json_encode($service->checkIn($argv[3], $argv[2])['state']);
PHP);
            foreach (['door-a', 'door-b'] as $operator) {
                $process = new Process([PHP_BINARY, $worker, $directory.'/barrier', $operator, 'VISIONX2026:1:'.$registration->pass_id], base_path(), [
                    'APP_ENV' => 'testing', 'DB_CONNECTION' => 'sqlite', 'DB_DATABASE' => $database, 'DB_URL' => '',
                    'CACHE_STORE' => 'array', 'SESSION_DRIVER' => 'array', 'MAIL_MAILER' => 'array',
                ]);
                $process->setTimeout(30);
                $process->start();
                $processes[] = $process;
            }
            $results = [];
            foreach ($processes as $process) {
                $process->wait();
                $this->assertTrue($process->isSuccessful(), $process->getErrorOutput());
                $results[] = json_decode($process->getOutput(), true, flags: JSON_THROW_ON_ERROR);
            }
            sort($results);
            $this->assertSame(['already_checked_in', 'checked_in'], $results);
            $registration->refresh();
            $this->assertNotNull($registration->checked_in_at);
            $this->assertContains($registration->checked_in_by, ['door-a', 'door-b']);
            $this->assertSame(1, Registration::whereNotNull('checked_in_at')->count());
        } finally {
            foreach ($processes as $process) {
                if ($process->isRunning()) {
                    $process->stop();
                }
            }
            DB::disconnect('sqlite');
            foreach (glob($directory.'/*') as $file) {
                unlink($file);
            }
            rmdir($directory);
        }
    }
}
