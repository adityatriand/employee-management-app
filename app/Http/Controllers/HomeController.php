<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Position;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        $totalEmployees = Employee::where('workspace_id', $workspace->id)->count();
        $totalPositions = Position::where('workspace_id', $workspace->id)->count();
        $maleEmployees = Employee::where('workspace_id', $workspace->id)->where('gender', 'L')->count();
        $femaleEmployees = Employee::where('workspace_id', $workspace->id)->where('gender', 'P')->count();
        
        // Calculate average age
        $averageAge = Employee::where('workspace_id', $workspace->id)
            ->selectRaw('AVG(YEAR(CURDATE()) - YEAR(birth_date)) as avg_age')
            ->value('avg_age');
        $averageAge = $averageAge ? round($averageAge, 1) : 0;
        
        // Calculate employees added this month
        $now = Carbon::now();
        $employeesThisMonth = Employee::where('workspace_id', $workspace->id)
            ->whereMonth('created_at', $now->month)
            ->whereYear('created_at', $now->year)
            ->count();
        
        // Calculate employees added last month
        $lastMonth = Carbon::now()->subMonth();
        $employeesLastMonth = Employee::where('workspace_id', $workspace->id)
            ->whereMonth('created_at', $lastMonth->month)
            ->whereYear('created_at', $lastMonth->year)
            ->count();
        
        // Calculate trend (percentage change)
        $trend = 0;
        if ($employeesLastMonth > 0) {
            $trend = (($employeesThisMonth - $employeesLastMonth) / $employeesLastMonth) * 100;
        } elseif ($employeesThisMonth > 0 && $employeesLastMonth == 0) {
            $trend = 100; // 100% increase from 0
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
        
        // Age distribution (by age groups)
        $ageGroups = [
            '18-25' => Employee::where('workspace_id', $workspace->id)
                ->whereRaw('YEAR(CURDATE()) - YEAR(birth_date) BETWEEN 18 AND 25')->count(),
            '26-35' => Employee::where('workspace_id', $workspace->id)
                ->whereRaw('YEAR(CURDATE()) - YEAR(birth_date) BETWEEN 26 AND 35')->count(),
            '36-45' => Employee::where('workspace_id', $workspace->id)
                ->whereRaw('YEAR(CURDATE()) - YEAR(birth_date) BETWEEN 36 AND 45')->count(),
            '46-55' => Employee::where('workspace_id', $workspace->id)
                ->whereRaw('YEAR(CURDATE()) - YEAR(birth_date) BETWEEN 46 AND 55')->count(),
            '56+' => Employee::where('workspace_id', $workspace->id)
                ->whereRaw('YEAR(CURDATE()) - YEAR(birth_date) >= 56')->count(),
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
