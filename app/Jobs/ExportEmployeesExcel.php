<?php

namespace App\Jobs;

use App\Models\Employee;
use App\Models\User;
use App\Exports\EmployeesExport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ExportEmployeesExcel implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutes
    public $tries = 3;

    protected $workspaceId;
    protected $filters;
    protected $userId;
    protected $filename;

    /**
     * Create a new job instance.
     *
     * @param int $workspaceId
     * @param array $filters
     * @param int $userId
     */
    public function __construct($workspaceId, $filters, $userId)
    {
        $this->workspaceId = $workspaceId;
        $this->filters = $filters;
        $this->userId = $userId;
        $this->filename = 'exports/data-pegawai-' . date('Y-m-d-His') . '-' . uniqid() . '.xlsx';
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $query = Employee::where('workspace_id', $this->workspaceId)
            ->with('position');

        // Apply filters
        if (!empty($this->filters['search'])) {
            $query->where('name', 'like', "%{$this->filters['search']}%");
        }
        if (!empty($this->filters['position_id'])) {
            $query->where('position_id', $this->filters['position_id']);
        }
        if (!empty($this->filters['gender'])) {
            $query->where('gender', $this->filters['gender']);
        }
        if (!empty($this->filters['birth_date_from'])) {
            $query->whereDate('birth_date', '>=', $this->filters['birth_date_from']);
        }
        if (!empty($this->filters['birth_date_to'])) {
            $query->whereDate('birth_date', '<=', $this->filters['birth_date_to']);
        }
        if (!empty($this->filters['created_from'])) {
            $query->whereDate('created_at', '>=', $this->filters['created_from']);
        }
        if (!empty($this->filters['created_to'])) {
            $query->whereDate('created_at', '<=', $this->filters['created_to']);
        }

        $employees = $query->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        // Generate Excel file
        $export = new EmployeesExport($employees);
        $filePath = storage_path('app/' . $this->filename);
        
        Excel::store($export, $this->filename, 'local');

        // Store export info in cache for user to download
        $user = User::find($this->userId);
        if ($user) {
            \Illuminate\Support\Facades\Cache::put(
                "export_excel_{$this->userId}_{$this->workspaceId}",
                [
                    'filename' => $this->filename,
                    'original_name' => 'data-pegawai-' . date('Y-m-d-His') . '.xlsx',
                    'created_at' => now(),
                ],
                now()->addHours(24) // Available for 24 hours
            );
        }
    }

    /**
     * Handle a job failure.
     *
     * @param \Throwable $exception
     * @return void
     */
    public function failed(\Throwable $exception)
    {
        \Log::error("Excel Export failed for workspace {$this->workspaceId}: " . $exception->getMessage());
    }
}

