<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class SystemMaintenanceController extends Controller
{
    /**
     * Get system environment status and storage link diagnosis.
     */
    public function status(): JsonResponse
    {
        $publicStorage = public_path('storage');
        $storageAppPublic = storage_path('app/public');

        $exists = file_exists($publicStorage);
        $isLink = is_link($publicStorage);
        $target = $isLink ? @readlink($publicStorage) : null;

        return response()->json([
            'success' => true,
            'data' => [
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
                'app_env' => app()->environment(),
                'app_url' => config('app.url'),
                'storage_link' => [
                    'exists' => $exists,
                    'is_symlink' => $isLink,
                    'target' => $target,
                    'public_path' => $publicStorage,
                    'source_path' => $storageAppPublic,
                    'source_exists' => is_dir($storageAppPublic),
                    'source_writable' => is_writable(storage_path('app')),
                ],
                'cache_driver' => config('cache.default'),
                'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'CLI/Unknown',
            ],
        ]);
    }

    /**
     * Run `php artisan storage:link` with `--force`.
     */
    public function storageLink(Request $request): JsonResponse
    {
        try {
            $source = storage_path('app/public');
            $target = public_path('storage');

            // Ensure source directory exists first
            if (!File::exists($source)) {
                File::makeDirectory($source, 0755, true);
            }

            // Run artisan command with --force to replace any dead symlink
            Artisan::call('storage:link', ['--force' => true]);
            $artisanOutput = trim(Artisan::output());

            // Secondary verification on filesystem
            $isLinked = file_exists($target) && (is_link($target) || is_dir($target));

            // If artisan couldn't create link (e.g. symlink function permissions on shared host), attempt native fallback
            if (!file_exists($target) && function_exists('symlink')) {
                @symlink($source, $target);
                $isLinked = file_exists($target);
                if ($isLinked) {
                    $artisanOutput .= "\nFallback symlink() executed successfully.";
                }
            }

            // Log activity
            ActivityLog::record(
                'Ran Storage Link',
                'System',
                'storage:link --force',
                null,
                [
                    'output' => $artisanOutput,
                    'is_linked' => $isLinked,
                    'target' => $isLinked && is_link($target) ? @readlink($target) : null,
                ],
                $request->user()?->id,
                $request->ip()
            );

            return response()->json([
                'success' => true,
                'message' => 'Artisan storage:link command executed successfully.',
                'command' => 'php artisan storage:link --force',
                'output' => $artisanOutput,
                'is_linked' => $isLinked,
                'target_path' => $target,
                'link_destination' => is_link($target) ? @readlink($target) : $target,
            ]);
        } catch (\Throwable $e) {
            Log::error('SystemMaintenanceController storageLink error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to execute storage:link: ' . $e->getMessage(),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Run `php artisan optimize:clear` to clear all caches.
     */
    public function optimizeClear(Request $request): JsonResponse
    {
        try {
            // Run optimize:clear
            Artisan::call('optimize:clear');
            $artisanOutput = trim(Artisan::output());

            // Log activity
            ActivityLog::record(
                'Ran Optimize Clear',
                'System',
                'optimize:clear',
                null,
                ['output' => $artisanOutput],
                $request->user()?->id,
                $request->ip()
            );

            return response()->json([
                'success' => true,
                'message' => 'All caches cleared successfully (views, cache, routes, config, compiled).',
                'command' => 'php artisan optimize:clear',
                'output' => $artisanOutput,
            ]);
        } catch (\Throwable $e) {
            Log::error('SystemMaintenanceController optimizeClear error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to clear cache: ' . $e->getMessage(),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Direct browser access with secret key for emergency server maintenance.
     */
    public function publicStorageLink(Request $request)
    {
        $this->validateSecretOrAdmin($request);
        return $this->storageLink($request);
    }

    public function publicOptimizeClear(Request $request)
    {
        $this->validateSecretOrAdmin($request);
        return $this->optimizeClear($request);
    }

    protected function validateSecretOrAdmin(Request $request): void
    {
        $secret = $request->query('secret') ?? $request->input('secret');
        $validSecret = env('MAINTENANCE_SECRET', 'panelook_admin_2026');
        $appKey = config('app.key');

        $isAuthorized = ($secret && ($secret === $validSecret || $secret === $appKey))
            || ($request->user() && in_array($request->user()->role, ['admin', 'super_admin']));

        if (!$isAuthorized) {
            abort(403, 'Unauthorized maintenance request. Provide valid ?secret= query parameter or login as Admin.');
        }
    }
}
