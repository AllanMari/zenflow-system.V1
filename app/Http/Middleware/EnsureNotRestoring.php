<?php

namespace App\Http\Middleware;

use App\Services\DatabaseRestoreService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureNotRestoring
{
    public function handle(Request $request, Closure $next): Response
    {
        if (DatabaseRestoreService::$inProcessRestoreActive) {
            return $next($request);
        }

        $restoreService = app(DatabaseRestoreService::class);
        if (!$restoreService->isMaintenanceActive()) {
            return $next($request);
        }

        $state = $restoreService->detectInterruptedRestore();
        $isActivelyRestoring = $restoreService->isRestoreActivelyRunning();

        // While a restore process actively holds the operation lock, block all
        // ordinary requests (including login and new restore attempts). Only
        // allow an already-authenticated Admin to view the status page.
        if ($isActivelyRestoring) {
            if ($request->isMethod('GET') && $request->routeIs('admin.database-backup.index')) {
                return $next($request);
            }

            return $this->unavailableResponse($request, 'ZenFlow is temporarily unavailable while database restoration is actively running.');
        }

        // Recovery mode (e.g., RECOVERY_REQUIRED or interrupted restore where no
        // process holds backup.lock): allow Admin status/restore routes and,
        // if the auth/session tables are intact, allow login/logout so an
        // Admin whose session was wiped can re-authenticate to recover.
        if ($request->routeIs('admin.database-backup.index', 'admin.database-backup.restore')) {
            return $next($request);
        }

        if ($request->routeIs('login', 'login.store', 'logout') || $request->is('login', 'logout')) {
            if ($restoreService->canAuthenticateDuringRecovery()) {
                return $next($request);
            }

            return $this->unavailableResponse(
                $request,
                'Database recovery is required, but authentication tables are unavailable. Restore the pre-restore safety backup via hosting database tools.'
            );
        }

        return $this->unavailableResponse(
            $request,
            'ZenFlow is in recovery mode after an incomplete restoration. An Administrator must complete recovery before normal access resumes.'
        );
    }

    private function unavailableResponse(Request $request, string $message): Response
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 503);
        }

        abort(503, $message);
    }
}
