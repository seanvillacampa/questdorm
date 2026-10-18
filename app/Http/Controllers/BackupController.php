<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BackupController extends Controller
{
    /**
     * Stream a live mysqldump as a downloadable .sql.gz file.
     * Owner only.
     */
    public function download(): StreamedResponse
    {
        abort_unless(auth()->user()->hasRole('owner'), 403);

        $host     = config('database.connections.mysql.host');
        $port     = config('database.connections.mysql.port', 3306);
        $dbname   = config('database.connections.mysql.database');
        $user     = config('database.connections.mysql.username');
        $password = config('database.connections.mysql.password');

        $stamp    = now('Asia/Manila')->format('Y-m-d_H-i');
        $filename = "questdorm-backup_{$stamp}.sql.gz";

        AuditLog::record('Backup', 'System', 0, 'Database backup downloaded by ' . auth()->user()->name);

        return response()->streamDownload(function () use ($host, $port, $dbname, $user, $password) {
            $descriptors = [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ];

            $env = array_merge($_ENV, ['MYSQL_PWD' => $password]);

            $dumpCmd = implode(' ', [
                'mysqldump',
                '--host=' . escapeshellarg($host),
                '--port=' . intval($port),
                '--user=' . escapeshellarg($user),
                '--single-transaction',
                '--quick',
                '--routines',
                '--triggers',
                '--no-tablespaces',
                '--set-gtid-purged=OFF',
                '--column-statistics=0',
                escapeshellarg($dbname),
            ]);

            // Pipe mysqldump → gzip → stdout
            $proc = proc_open("$dumpCmd | gzip -9", $descriptors, $pipes, null, $env);

            if (!is_resource($proc)) {
                echo 'ERROR: Could not start mysqldump process.';
                return;
            }

            fclose($pipes[0]);

            while (!feof($pipes[1])) {
                echo fread($pipes[1], 65536);
                flush();
            }

            fclose($pipes[1]);
            fclose($pipes[2]);
            proc_close($proc);
        }, $filename, [
            'Content-Type'        => 'application/gzip',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Accept an uploaded .sql or .sql.gz file and restore the database.
     * Owner only. Extremely destructive — wipes the current DB.
     */
    public function restore(Request $request)
    {
        abort_unless(auth()->user()->hasRole('owner'), 403);

        $request->validate([
            'backup_file' => 'required|file|mimes:gz,sql,plain|max:102400', // 100 MB max
        ]);

        $file     = $request->file('backup_file');
        $origName = $file->getClientOriginalName();
        $isGzip   = str_ends_with(strtolower($origName), '.gz');

        $tmpPath  = $file->getRealPath();

        $host     = config('database.connections.mysql.host');
        $port     = config('database.connections.mysql.port', 3306);
        $dbname   = config('database.connections.mysql.database');
        $user     = config('database.connections.mysql.username');
        $password = config('database.connections.mysql.password');

        $env = array_merge($_ENV, ['MYSQL_PWD' => $password]);

        $mysqlCmd = implode(' ', [
            'mysql',
            '--host=' . escapeshellarg($host),
            '--port=' . intval($port),
            '--user=' . escapeshellarg($user),
            escapeshellarg($dbname),
        ]);

        $cmd = $isGzip
            ? "gunzip -c " . escapeshellarg($tmpPath) . " | $mysqlCmd"
            : "$mysqlCmd < " . escapeshellarg($tmpPath);

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $proc = proc_open($cmd, $descriptors, $pipes, null, $env);

        if (!is_resource($proc)) {
            return back()->withErrors(['backup_file' => 'Could not start restore process. Check server configuration.']);
        }

        fclose($pipes[0]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($proc);

        if ($exitCode !== 0) {
            Log::error('Database restore failed', ['stderr' => $stderr, 'exit_code' => $exitCode]);
            return back()->withErrors(['backup_file' => 'Restore failed: ' . ($stderr ?: 'Unknown error. Check server logs.')]);
        }

        AuditLog::record('Restore', 'System', 0, 'Database restored from file: ' . $origName . ' by ' . auth()->user()->name);

        Log::info('Database restored', ['file' => $origName, 'user' => auth()->user()->name]);

        return back()->with('success', "Database successfully restored from \"{$origName}\". Please verify your data.");
    }
}
