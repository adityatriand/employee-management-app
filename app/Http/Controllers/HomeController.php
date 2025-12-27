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
        // Cache chart data for 5 minutes
        return Cache::remember("chart_data_{$workspace->id}", 300, function () use ($workspace) {
            $now = Carbon::now();
            
            // Optimized: Single query for employee growth (last 12 months)
            $months = [];
            for ($i = 11; $i >= 0; $i--) {
                $date = $now->copy()->subMonths($i);
                $months[] = [
                    'start' => $date->copy()->startOfMonth(),
                    'end' => $date->copy()->endOfMonth(),
                    'label' => $date->format('M Y'),
                ];
            }
            
            // Single query to get all monthly counts
            $growthData = [];
            $growthLabels = [];
            foreach ($months as $month) {
                $count = Employee::where('workspace_id', $workspace->id)
                    ->whereBetween('created_at', [$month['start'], $month['end']])
                    ->count();
                $growthData[] = $count;
                $growthLabels[] = $month['label'];
            }
            
            // Position distribution (already optimized with withCount)
            $positionData = Position::where('workspace_id', $workspace->id)
                ->withCount(['employees' => function($q) use ($workspace) {
                    $q->where('workspace_id', $workspace->id);
                }])
                ->orderBy('name', 'asc')
                ->get();
            $positionLabels = $positionData->pluck('name')->toArray();
            $positionCounts = $positionData->pluck('employees_count')->toArray();
            
            // Optimized: Single query for gender distribution
            $genderStats = Employee::where('workspace_id', $workspace->id)
                ->selectRaw('
                    SUM(CASE WHEN gender = "L" THEN 1 ELSE 0 END) as male,
                    SUM(CASE WHEN gender = "P" THEN 1 ELSE 0 END) as female
                ')
                ->first();
            $genderData = [
                $genderStats->male ?? 0,
                $genderStats->female ?? 0,
            ];
            
            // Optimized: Single query for age distribution
            $ageStats = Employee::where('workspace_id', $workspace->id)
                ->whereNotNull('birth_date')
                ->selectRaw('
                    SUM(CASE WHEN birth_date <= ? AND birth_date >= ? THEN 1 ELSE 0 END) as age_18_25,
                    SUM(CASE WHEN birth_date <= ? AND birth_date >= ? THEN 1 ELSE 0 END) as age_26_35,
                    SUM(CASE WHEN birth_date <= ? AND birth_date >= ? THEN 1 ELSE 0 END) as age_36_45,
                    SUM(CASE WHEN birth_date <= ? AND birth_date >= ? THEN 1 ELSE 0 END) as age_46_55,
                    SUM(CASE WHEN birth_date <= ? THEN 1 ELSE 0 END) as age_56_plus
                ', [
                    $now->copy()->subYears(18), $now->copy()->subYears(25),
                    $now->copy()->subYears(26), $now->copy()->subYears(35),
                    $now->copy()->subYears(36), $now->copy()->subYears(45),
                    $now->copy()->subYears(46), $now->copy()->subYears(55),
                    $now->copy()->subYears(56),
                ])
                ->first();
            
            $ageGroups = [
                '18-25' => $ageStats->age_18_25 ?? 0,
                '26-35' => $ageStats->age_26_35 ?? 0,
                '36-45' => $ageStats->age_36_45 ?? 0,
                '46-55' => $ageStats->age_46_55 ?? 0,
                '56+' => $ageStats->age_56_plus ?? 0,
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
        });
    }
}
