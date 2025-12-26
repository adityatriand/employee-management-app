<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Position;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $workspace = $request->get('workspace'); // Set by WorkspaceMiddleware
        
        if (!$workspace) {
            abort(404, 'Workspace not found');
        }
        
        // Regular user dashboard (level 0) - limited access
        if ($user->level == 0) {
            // Get employee record for this user
            $employee = Employee::where('workspace_id', $workspace->id)
                ->where('user_id', $user->id)
                ->with(['position', 'files', 'assets'])
                ->first();
            
            if (!$employee) {
                // If no employee record, show message
                return view('user.dashboard', compact('workspace'))->with('error', 'Profil pegawai tidak ditemukan. Silakan hubungi administrator.');
            }
            
            // Get employee files and assets
            $files = $employee->files()->orderBy('created_at', 'desc')->take(5)->get();
            $assets = $employee->assets()->orderBy('assigned_date', 'desc')->take(5)->get();
            
            // Show limited dashboard - only own profile
            return view('user.dashboard', compact('workspace', 'employee', 'files', 'assets'));
        }
        
        // Admin dashboard (level 1)
        // Get statistics scoped to workspace
        $stats = $this->getStatistics($workspace);
        
        // Get chart data scoped to workspace
        $chartData = $this->getChartData($workspace);
        
        // Get recent employees scoped to workspace
        $recentEmployees = Employee::where('workspace_id', $workspace->id)
            ->with('position')
            ->latest()
            ->take(5)
            ->get();
        
        return view('admin.dashboard', compact('workspace', 'stats', 'chartData', 'recentEmployees'));
    }

    /**
     * Get dashboard statistics
     *
     * @param \App\Models\Workspace $workspace
     * @return array
     */
    protected function getStatistics($workspace)
    {
        // Cache statistics for 5 minutes
        return \Illuminate\Support\Facades\Cache::remember("dashboard_stats_{$workspace->id}", 300, function () use ($workspace) {
            $now = Carbon::now();
            $lastMonth = $now->copy()->subMonth();
            
            // Single query to get all employee counts
            $employeeStats = Employee::where('workspace_id', $workspace->id)
                ->selectRaw('
                    COUNT(*) as total,
                    SUM(CASE WHEN gender = "L" THEN 1 ELSE 0 END) as male,
                    SUM(CASE WHEN gender = "P" THEN 1 ELSE 0 END) as female,
                    SUM(CASE WHEN MONTH(created_at) = ? AND YEAR(created_at) = ? THEN 1 ELSE 0 END) as this_month,
                    SUM(CASE WHEN MONTH(created_at) = ? AND YEAR(created_at) = ? THEN 1 ELSE 0 END) as last_month
                ', [
                    $now->month, $now->year,
                    $lastMonth->month, $lastMonth->year
                ])
                ->first();
            
            $totalEmployees = $employeeStats->total ?? 0;
            $maleEmployees = $employeeStats->male ?? 0;
            $femaleEmployees = $employeeStats->female ?? 0;
            $employeesThisMonth = $employeeStats->this_month ?? 0;
            $employeesLastMonth = $employeeStats->last_month ?? 0;
            
            // Calculate average age (cached separately)
            $averageAge = \Illuminate\Support\Facades\Cache::remember("avg_age_{$workspace->id}", 3600, function () use ($workspace, $now) {
                $employees = Employee::where('workspace_id', $workspace->id)
                    ->select('birth_date')
                    ->get();
                
                if ($employees->count() > 0) {
                    $totalAge = $employees->sum(function ($employee) use ($now) {
                        return $employee->birth_date ? $now->diffInYears($employee->birth_date) : 0;
                    });
                    return round($totalAge / $employees->count(), 1);
                }
                return 0;
            });
            
            // Get positions count (cached)
            $totalPositions = \Illuminate\Support\Facades\Cache::remember("positions_count_{$workspace->id}", 3600, function () use ($workspace) {
                return Position::where('workspace_id', $workspace->id)->count();
            });
            
            // Calculate trend
            $trend = 0;
            if ($employeesLastMonth > 0) {
                $trend = (($employeesThisMonth - $employeesLastMonth) / $employeesLastMonth) * 100;
            } elseif ($employeesThisMonth > 0 && $employeesLastMonth == 0) {
                $trend = 100;
            }
            
            return [
                'total_employees' => $totalEmployees,
                'total_positions' => $totalPositions,
                'male_employees' => $maleEmployees,
                'female_employees' => $femaleEmployees,
                'average_age' => $averageAge,
                'employees_this_month' => $employeesThisMonth,
                'employees_last_month' => $employeesLastMonth,
                'trend' => round($trend, 1),
            ];
        });
    }

    /**
     * Get chart data
     *
     * @param \App\Models\Workspace $workspace
     * @return array
     */
    protected function getChartData($workspace)
    {
        // Employee growth chart (last 12 months)
        $growthData = [];
        $growthLabels = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $monthStart = $date->copy()->startOfMonth();
            $monthEnd = $date->copy()->endOfMonth();
            
            $count = Employee::where('workspace_id', $workspace->id)
                ->whereBetween('created_at', [$monthStart, $monthEnd])
                ->count();
            $growthData[] = $count;
            $growthLabels[] = $date->format('M Y');
        }
        
        // Position distribution
        $positionData = Position::where('workspace_id', $workspace->id)
            ->withCount(['employees' => function($q) use ($workspace) {
                $q->where('workspace_id', $workspace->id);
            }])
            ->orderBy('name', 'asc')
            ->get();
        $positionLabels = $positionData->pluck('name')->toArray();
        $positionCounts = $positionData->pluck('employees_count')->toArray();
        
        // Gender distribution
        $genderData = [
            Employee::where('workspace_id', $workspace->id)->where('gender', 'L')->count(),
            Employee::where('workspace_id', $workspace->id)->where('gender', 'P')->count(),
        ];
        
        // Age distribution (by age groups) - using safe date calculations
        $now = Carbon::now();
        $ageGroups = [
            '18-25' => Employee::where('workspace_id', $workspace->id)
                ->whereDate('birth_date', '<=', $now->copy()->subYears(18))
                ->whereDate('birth_date', '>=', $now->copy()->subYears(25))
                ->count(),
            '26-35' => Employee::where('workspace_id', $workspace->id)
                ->whereDate('birth_date', '<=', $now->copy()->subYears(26))
                ->whereDate('birth_date', '>=', $now->copy()->subYears(35))
                ->count(),
            '36-45' => Employee::where('workspace_id', $workspace->id)
                ->whereDate('birth_date', '<=', $now->copy()->subYears(36))
                ->whereDate('birth_date', '>=', $now->copy()->subYears(45))
                ->count(),
            '46-55' => Employee::where('workspace_id', $workspace->id)
                ->whereDate('birth_date', '<=', $now->copy()->subYears(46))
                ->whereDate('birth_date', '>=', $now->copy()->subYears(55))
                ->count(),
            '56+' => Employee::where('workspace_id', $workspace->id)
                ->whereDate('birth_date', '<=', $now->copy()->subYears(56))
                ->count(),
        ];
        
        return [
            'growth' => [
                'labels' => $growthLabels,
                'data' => $growthData,
            ],
            'position' => [
                'labels' => $positionLabels,
                'data' => $positionCounts,
            ],
            'gender' => [
                'labels' => ['Laki-Laki', 'Perempuan'],
                'data' => $genderData,
            ],
            'age' => [
                'labels' => array_keys($ageGroups),
                'data' => array_values($ageGroups),
            ],
        ];
    }
}
