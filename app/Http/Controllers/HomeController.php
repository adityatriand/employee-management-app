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
    public function index()
    {
        $user = auth()->user();
        
        // Get statistics
        $stats = $this->getStatistics();
        
        // Get chart data
        $chartData = $this->getChartData();
        
        // Get recent employees
        $recentEmployees = Employee::with('position')
            ->latest()
            ->take(5)
            ->get();
        
        // Admin dashboard (level 1)
        if ($user->level == 1) {
            return view('admin.dashboard', compact('stats', 'chartData', 'recentEmployees'));
        }
        
        // Regular user dashboard (level 0)
        return view('user.dashboard', compact('stats', 'chartData', 'recentEmployees'));
    }

    /**
     * Get dashboard statistics
     *
     * @return array
     */
    protected function getStatistics()
    {
        $totalEmployees = Employee::count();
        $totalPositions = Position::count();
        $maleEmployees = Employee::where('gender', 'L')->count();
        $femaleEmployees = Employee::where('gender', 'P')->count();
        
        // Calculate average age
        $averageAge = Employee::selectRaw('AVG(YEAR(CURDATE()) - YEAR(birth_date)) as avg_age')
            ->value('avg_age');
        $averageAge = $averageAge ? round($averageAge, 1) : 0;
        
        // Calculate employees added this month
        $now = Carbon::now();
        $employeesThisMonth = Employee::whereMonth('created_at', $now->month)
            ->whereYear('created_at', $now->year)
            ->count();
        
        // Calculate employees added last month
        $lastMonth = Carbon::now()->subMonth();
        $employeesLastMonth = Employee::whereMonth('created_at', $lastMonth->month)
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
     * @return array
     */
    protected function getChartData()
    {
        // Employee growth chart (last 12 months)
        $growthData = [];
        $growthLabels = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $monthStart = $date->copy()->startOfMonth();
            $monthEnd = $date->copy()->endOfMonth();
            
            $count = Employee::whereBetween('created_at', [$monthStart, $monthEnd])->count();
            $growthData[] = $count;
            $growthLabels[] = $date->format('M Y');
        }
        
        // Position distribution
        $positionData = Position::withCount('employees')
            ->orderBy('name', 'asc')
            ->get();
        $positionLabels = $positionData->pluck('name')->toArray();
        $positionCounts = $positionData->pluck('employees_count')->toArray();
        
        // Gender distribution
        $genderData = [
            Employee::where('gender', 'L')->count(),
            Employee::where('gender', 'P')->count(),
        ];
        
        // Age distribution (by age groups)
        $ageGroups = [
            '18-25' => Employee::whereRaw('YEAR(CURDATE()) - YEAR(birth_date) BETWEEN 18 AND 25')->count(),
            '26-35' => Employee::whereRaw('YEAR(CURDATE()) - YEAR(birth_date) BETWEEN 26 AND 35')->count(),
            '36-45' => Employee::whereRaw('YEAR(CURDATE()) - YEAR(birth_date) BETWEEN 36 AND 45')->count(),
            '46-55' => Employee::whereRaw('YEAR(CURDATE()) - YEAR(birth_date) BETWEEN 46 AND 55')->count(),
            '56+' => Employee::whereRaw('YEAR(CURDATE()) - YEAR(birth_date) >= 56')->count(),
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
