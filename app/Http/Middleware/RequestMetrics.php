<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class RequestMetrics
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $startTime = microtime(true);
        
        try {
            $response = $next($request);
            
            // Track successful request
            $this->trackRequest($request, $response->getStatusCode(), $startTime);
            
            return $response;
        } catch (\Exception $e) {
            // Track error
            $this->trackError($request, $e);
            
            throw $e;
        }
    }

    /**
     * Track successful request
     */
    private function trackRequest(Request $request, int $statusCode, float $startTime)
    {
        $duration = (microtime(true) - $startTime) * 1000; // milliseconds
        
        // Get route name or path
        $route = $request->route() ? $request->route()->getName() : $request->path();
        $method = $request->method();
        $path = $this->normalizePath($request->path());
        
        // Increment request counter (simplified key for better cache performance)
        $cacheKey = "http_requests_total:{$method}::{$statusCode}";
        Cache::increment($cacheKey, 1);
        
        // Track request duration (only for API endpoints to reduce cache usage)
        if ($request->is('api/*')) {
            $durationKey = "http_request_duration:{$method}:{$path}";
            $durations = Cache::get($durationKey, []);
            $durations[] = $duration;
            // Keep only last 50 durations to reduce memory
            if (count($durations) > 50) {
                $durations = array_slice($durations, -50);
            }
            Cache::put($durationKey, $durations, now()->addHours(1));
        }
        
        // Track by status code
        $statusKey = "http_status_total:{$statusCode}";
        Cache::increment($statusKey, 1);
        
        // Track API vs Web requests
        if ($request->is('api/*')) {
            Cache::increment('api_requests_total', 1);
        } else {
            Cache::increment('web_requests_total', 1);
        }
        
        // Log detailed request information
        // For API endpoints (except metrics), and important web routes
        $shouldLog = false;
        
        if ($request->is('api/*') && $request->path() !== 'api/metrics') {
            // Log all API requests except metrics
            $shouldLog = true;
        } elseif (!$request->is('api/*')) {
            // Log important web routes (employee management, dashboard, etc.)
            $importantPaths = [
                'employees', 'positions', 'files', 'assets', 
                'dashboard', 'home', 'settings', 'activity'
            ];
            foreach ($importantPaths as $path) {
                if (str_contains($request->path(), $path)) {
                    $shouldLog = true;
                    break;
                }
            }
        }
        
        if ($shouldLog) {
            $this->logRequestDetails($request, $statusCode, $duration);
        }
    }

    /**
     * Log detailed request information
     */
    private function logRequestDetails(Request $request, int $statusCode, float $duration)
    {
        $isApi = $request->is('api/*');
        
        $logData = [
            'type' => $isApi ? 'api_request' : 'web_request',
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'path' => $request->path(),
            'route' => $request->route() ? $request->route()->getName() : null,
            'status_code' => $statusCode,
            'duration_ms' => round($duration, 2),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'timestamp' => now()->toIso8601String(),
        ];
        
        // Add authenticated user info if available
        if ($request->user()) {
            $logData['user_id'] = $request->user()->id;
            $logData['user_email'] = $request->user()->email;
            $logData['workspace_id'] = $request->user()->workspace_id ?? null;
        }
        
        // Add request parameters (query string)
        $queryParams = $request->query();
        if (!empty($queryParams)) {
            $logData['query_params'] = $queryParams;
        }
        
        // Add request payload (for POST, PUT, PATCH)
        if (in_array($request->method(), ['POST', 'PUT', 'PATCH'])) {
            $payload = $request->all();
            
            // Remove sensitive data
            $sensitiveKeys = ['password', 'password_confirmation', 'token', 'api_key', 'secret'];
            foreach ($sensitiveKeys as $key) {
                if (isset($payload[$key])) {
                    $payload[$key] = '***REDACTED***';
                }
            }
            
            $logData['payload'] = $payload;
        }
        
        // Add headers (optional, can be verbose)
        if (config('app.debug', false)) {
            $logData['headers'] = $request->headers->all();
        }
        
        // Log based on status code (without stack traces)
        if ($statusCode >= 400) {
            Log::channel('single')->warning('API Request', $logData);
        } else {
            Log::channel('single')->info('API Request', $logData);
        }
    }

    /**
     * Normalize path to reduce cache keys (remove IDs, etc.)
     */
    private function normalizePath(string $path): string
    {
        // Replace numeric IDs with {id}
        $path = preg_replace('/\/\d+/', '/{id}', $path);
        // Replace UUIDs with {uuid}
        $path = preg_replace('/\/[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}/i', '/{uuid}', $path);
        return $path;
    }

    /**
     * Track error
     */
    private function trackError(Request $request, \Exception $e)
    {
        $method = $request->method();
        $path = $this->normalizePath($request->path());
        $statusCode = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;
        
        // Increment error counter (simplified key)
        $errorKey = "http_errors_total:::{$statusCode}";
        Cache::increment($errorKey, 1);
        
        // Track total errors
        Cache::increment('http_errors_total', 1);
        
        // Track by exception type (simplified)
        $exceptionType = class_basename($e); // Just class name, not full namespace
        $exceptionKey = "exceptions_total:{$exceptionType}";
        Cache::increment($exceptionKey, 1);
        
        // Log detailed error information
        $errorLog = [
            'type' => 'api_error',
            'method' => $method,
            'url' => $request->fullUrl(),
            'path' => $request->path(),
            'route' => $request->route() ? $request->route()->getName() : null,
            'status_code' => $statusCode,
            'exception' => get_class($e),
            'exception_message' => $e->getMessage(),
            'exception_file' => $e->getFile(),
            'exception_line' => $e->getLine(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'timestamp' => now()->toIso8601String(),
        ];
        
        // Add request parameters
        $queryParams = $request->query();
        if (!empty($queryParams)) {
            $errorLog['query_params'] = $queryParams;
        }
        
        // Add request payload
        if (in_array($request->method(), ['POST', 'PUT', 'PATCH'])) {
            $payload = $request->all();
            
            // Remove sensitive data
            $sensitiveKeys = ['password', 'password_confirmation', 'token', 'api_key', 'secret'];
            foreach ($sensitiveKeys as $key) {
                if (isset($payload[$key])) {
                    $payload[$key] = '***REDACTED***';
                }
            }
            
            $errorLog['payload'] = $payload;
        }
        
        // Add stack trace for debugging (only in debug mode)
        if (config('app.debug', false)) {
            $errorLog['stack_trace'] = $e->getTraceAsString();
        }
        
        // Log error without stack trace in the message
        Log::channel('single')->error('API Error', $errorLog);
    }
}

