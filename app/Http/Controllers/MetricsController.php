<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Models\Employee;
use App\Models\User;
use App\Models\Workspace;
use App\Models\File;
use App\Models\Asset;
use Carbon\Carbon;

class MetricsController extends Controller
{
    /**
     * Expose Prometheus metrics endpoint
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $metrics = [];

        // Enable query logging for this request
        DB::enableQueryLog();

        // Application Info
        $metrics[] = $this->formatMetric('app_info', 1, [
            'version' => app()->version(),
            'environment' => app()->environment(),
        ]);

        // HTTP Request Metrics (from RequestMetrics middleware)
        $this->addRequestMetrics($metrics);

        // Database Connection Status
        try {
            DB::connection()->getPdo();
            $metrics[] = $this->formatMetric('database_connection_status', 1, []);
        } catch (\Exception $e) {
            $metrics[] = $this->formatMetric('database_connection_status', 0, []);
        }

        // Database Query Count (from query log)
        $queryLog = DB::getQueryLog();
        $queryCount = count($queryLog);
        $metrics[] = $this->formatMetric('database_queries_total', $queryCount, []);

        // Cache Metrics
        $cacheHits = Cache::get('cache_hits', 0);
        $cacheMisses = Cache::get('cache_misses', 0);
        $metrics[] = $this->formatMetric('cache_hits_total', $cacheHits, []);
        $metrics[] = $this->formatMetric('cache_misses_total', $cacheMisses, []);

        // Queue Metrics
        try {
            $queueSize = $this->getQueueSize();
            $metrics[] = $this->formatMetric('queue_size', $queueSize, [
                'queue' => 'default',
            ]);
        } catch (\Exception $e) {
            // Queue not configured
        }

        // Model Counts
        $metrics[] = $this->formatMetric('employees_total', Employee::count(), []);
        $metrics[] = $this->formatMetric('users_total', User::count(), []);
        $metrics[] = $this->formatMetric('workspaces_total', Workspace::count(), []);
        $metrics[] = $this->formatMetric('files_total', File::count(), []);
        $metrics[] = $this->formatMetric('assets_total', Asset::count(), []);

        // Active Users (logged in within last hour)
        $activeUsers = User::where('updated_at', '>=', Carbon::now()->subHour())->count();
        $metrics[] = $this->formatMetric('active_users', $activeUsers, []);

        // Storage Metrics
        try {
            $storagePath = storage_path();
            $storageSize = $this->getDirectorySize($storagePath);
            $metrics[] = $this->formatMetric('storage_size_bytes', $storageSize, [
                'path' => 'storage',
            ]);
        } catch (\Exception $e) {
            // Ignore storage errors
        }

        // Memory Usage
        $memoryUsage = memory_get_usage(true);
        $metrics[] = $this->formatMetric('memory_usage_bytes', $memoryUsage, []);

        // Peak Memory Usage
        $peakMemory = memory_get_peak_usage(true);
        $metrics[] = $this->formatMetric('memory_peak_bytes', $peakMemory, []);

        // Response Time (approximate)
        $startTime = defined('LARAVEL_START') ? LARAVEL_START : microtime(true);
        $responseTime = (microtime(true) - $startTime) * 1000; // Convert to milliseconds
        $metrics[] = $this->formatMetric('http_request_duration_ms', $responseTime, []);

        // Database Connection Pool
        try {
            $activeConnections = DB::select("SHOW STATUS LIKE 'Threads_connected'");
            if (!empty($activeConnections)) {
                $connections = (int) $activeConnections[0]->Value;
                $metrics[] = $this->formatMetric('database_connections_active', $connections, []);
            }
        } catch (\Exception $e) {
            // Ignore if not MySQL or query fails
        }

        // Uptime (if available)
        if (function_exists('sys_getloadavg')) {
            $load = sys_getloadavg();
            if ($load !== false) {
                $metrics[] = $this->formatMetric('system_load_1m', $load[0], []);
                $metrics[] = $this->formatMetric('system_load_5m', $load[1], []);
                $metrics[] = $this->formatMetric('system_load_15m', $load[2], []);
            }
        }

        return response(implode("\n", $metrics), 200)
            ->header('Content-Type', 'text/plain; version=0.0.4');
    }

    /**
     * Format metric in Prometheus format
     *
     * @param string $name
     * @param float|int $value
     * @param array $labels
     * @return string
     */
    private function formatMetric($name, $value, $labels = [])
    {
        $labelString = '';
        if (!empty($labels)) {
            $labelPairs = [];
            foreach ($labels as $key => $val) {
                $labelPairs[] = sprintf('%s="%s"', $key, $this->escapeLabelValue($val));
            }
            $labelString = '{' . implode(',', $labelPairs) . '}';
        }

        return sprintf('%s%s %s', $name, $labelString, $value);
    }

    /**
     * Escape label value for Prometheus
     *
     * @param string $value
     * @return string
     */
    private function escapeLabelValue($value)
    {
        return str_replace(['\\', '"', "\n"], ['\\\\', '\\"', '\\n'], (string) $value);
    }

    /**
     * Get total requests count (approximate)
     *
     * @return int
     */
    private function getTotalRequests()
    {
        // Increment counter on each metrics request
        $counter = Cache::increment('metrics_request_counter', 1);
        
        // If counter doesn't exist, initialize it
        if ($counter === false) {
            Cache::put('metrics_request_counter', 1, now()->addYear());
            return 1;
        }
        
        return $counter;
    }

    /**
     * Get queue size
     *
     * @return int
     */
    private function getQueueSize()
    {
        try {
            if (config('queue.default') === 'database') {
                return DB::table('jobs')->count();
            }
            return 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get directory size in bytes
     *
     * @param string $directory
     * @return int
     */
    private function getDirectorySize($directory)
    {
        $size = 0;
        try {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS)
            );
            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $size += $file->getSize();
                }
            }
        } catch (\Exception $e) {
            // Return 0 if we can't calculate
        }
        return $size;
    }

    /**
     * Add request metrics from RequestMetrics middleware
     *
     * @param array $metrics
     * @return void
     */
    private function addRequestMetrics(array &$metrics)
    {
        // Get all cached request metrics
        $cachePrefix = 'http_requests_total:';
        $allKeys = [];
        
        // We'll use a pattern to get all request metrics
        // Since we can't easily list all cache keys, we'll track common endpoints
        $commonMethods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'];
        $commonStatuses = [200, 201, 400, 401, 403, 404, 422, 500];
        
        // Track by method and status
        foreach ($commonMethods as $method) {
            foreach ($commonStatuses as $status) {
                $key = "http_requests_total:{$method}::{$status}";
                $count = Cache::get($key, 0);
                if ($count > 0) {
                    $metrics[] = $this->formatMetric('http_requests_total', $count, [
                        'method' => $method,
                        'status' => (string)$status,
                    ]);
                }
            }
        }
        
        // API vs Web requests
        $apiRequests = Cache::get('api_requests_total', 0);
        $webRequests = Cache::get('web_requests_total', 0);
        
        $metrics[] = $this->formatMetric('api_requests_total', $apiRequests, []);
        $metrics[] = $this->formatMetric('web_requests_total', $webRequests, []);
        
        // Error metrics
        $totalErrors = Cache::get('http_errors_total', 0);
        $metrics[] = $this->formatMetric('http_errors_total', $totalErrors, []);
        
        // Track errors by status code
        foreach ([400, 401, 403, 404, 422, 500, 502, 503] as $status) {
            $errorKey = "http_errors_total:::{$status}";
            $errorCount = Cache::get($errorKey, 0);
            if ($errorCount > 0) {
                $metrics[] = $this->formatMetric('http_errors_total', $errorCount, [
                    'status' => (string)$status,
                ]);
            }
        }
        
        // Average request duration (from tracked durations)
        $this->addDurationMetrics($metrics);
    }

    /**
     * Add request duration metrics
     *
     * @param array $metrics
     * @return void
     */
    private function addDurationMetrics(array &$metrics)
    {
        // Get average duration from cache
        $commonMethods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'];
        $commonPaths = ['api/employees', 'api/users', 'api/auth/login', 'api/auth/register'];
        
        foreach ($commonMethods as $method) {
            foreach ($commonPaths as $path) {
                $durationKey = "http_request_duration:{$method}:{$path}";
                $durations = Cache::get($durationKey, []);
                
                if (!empty($durations)) {
                    $avgDuration = array_sum($durations) / count($durations);
                    $maxDuration = max($durations);
                    $minDuration = min($durations);
                    
                    $metrics[] = $this->formatMetric('http_request_duration_ms', $avgDuration, [
                        'method' => $method,
                        'path' => $path,
                        'type' => 'avg',
                    ]);
                    
                    $metrics[] = $this->formatMetric('http_request_duration_ms', $maxDuration, [
                        'method' => $method,
                        'path' => $path,
                        'type' => 'max',
                    ]);
                }
            }
        }
    }
}

