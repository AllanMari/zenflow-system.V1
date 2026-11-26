<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DatabaseBackupService;
use App\Services\DatabaseRestoreService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Throwable;

class DatabaseBackupController extends Controller
{
    public function index(DatabaseBackupService $service, DatabaseRestoreService $restoreService)
    {
        $restoreState = $restoreService->detectInterruptedRestore();

        return view('admin.database-backup', [
            'backups' => $service->listBackups(),
            'restoreState' => $restoreState,
            'maintenanceActive' => $restoreService->isMaintenanceActive(),
        ]);
    }

    public function create(Request $request, DatabaseBackupService $service)
    {
        try {
            $manifest = $service->createBackup($request->user());

            return back()->with(
                'success',
                'Database backup ' . $manifest['backup_id'] . ' created and verified successfully.'
            );
        } catch (Throwable $e) {
            report($e);

            return back()->with('error', 'Backup failed: ' . $e->getMessage());
        }
    }

    public function download(string $backupId, DatabaseBackupService $service)
    {
        try {
            $path = $service->downloadPath($backupId);
            $service->writeAuditEvent([
                'event' => 'backup_download',
                'backup_id' => $backupId,
                'actor_id' => auth()->id(),
                'actor_name' => auth()->user()?->full_name,
            ]);

            return Storage::disk('local')->download(
                $path,
                'backup_' . $backupId . '.sql',
                ['Content-Type' => 'application/sql']
            );
        } catch (Throwable $e) {
            report($e);

            return back()->with('error', 'Download failed: ' . $e->getMessage());
        }
    }

    public function restore(Request $request, string $backupId, DatabaseRestoreService $restoreService)
    {
        $user = $request->user();
        if (!$user || !$user->hasRole('admin')) {
            abort(403);
        }

        $rateKey = 'database-restore:' . $user->id . '|' . $request->ip();
        if (RateLimiter::tooManyAttempts($rateKey, 5)) {
            return back()->with(
                'error',
                'Too many failed password attempts. Please wait a few minutes before trying again.'
            );
        }

        $validated = $request->validate([
            'admin_password' => ['required', 'string'],
        ], [
            'admin_password.required' => 'Admin password is required before restoring the database.',
        ]);

        if (!Hash::check($validated['admin_password'], (string) $user->password)) {
            RateLimiter::hit($rateKey, 300);

            return back()->with('error', 'Admin password verification failed. Database restore was cancelled.');
        }

        RateLimiter::clear($rateKey);

        try {
            $result = $restoreService->restore($backupId, $user);

            return back()->with(
                'success',
                'Database restored and verified from backup ' . $backupId . '. Safety backup: ' . ($result['safety_backup_id'] ?? 'created') . '.'
            );
        } catch (Throwable $e) {
            report($e);

            return back()->with('error', 'Restore failed: ' . $e->getMessage());
        }
    }
}
