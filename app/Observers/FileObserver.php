<?php

namespace App\Observers;

use App\Models\File;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

class FileObserver
{
    /**
     * Handle the File "created" event.
     *
     * @param  \App\Models\File  $file
     * @return void
     */
    public function created(File $file)
    {
        $description = "File '{$file->name}' telah diupload";
        if ($file->employee) {
            $description .= " untuk pegawai '{$file->employee->name}'";
        } else {
            $description .= " sebagai file standalone";
        }

        ActivityLog::create([
            'user_id' => Auth::id(),
            'workspace_id' => $file->workspace_id,
            'model_type' => get_class($file),
            'model_id' => $file->id,
            'action' => 'created',
            'new_values' => $file->getAttributes(),
            'description' => $description,
        ]);
    }

    /**
     * Handle the File "updated" event.
     *
     * @param  \App\Models\File  $file
     * @return void
     */
    public function updated(File $file)
    {
        $oldValues = [];
        $newValues = [];
        
        foreach ($file->getDirty() as $key => $value) {
            $oldValues[$key] = $file->getOriginal($key);
            $newValues[$key] = $value;
        }

        if (!empty($oldValues)) {
            ActivityLog::create([
                'user_id' => Auth::id(),
                'workspace_id' => $file->workspace_id,
                'model_type' => get_class($file),
                'model_id' => $file->id,
                'action' => 'updated',
                'old_values' => $oldValues,
                'new_values' => $newValues,
                'description' => "File '{$file->name}' telah diupdate",
            ]);
        }
    }

    /**
     * Handle the File "deleted" event.
     *
     * @param  \App\Models\File  $file
     * @return void
     */
    public function deleted(File $file)
    {
        ActivityLog::create([
            'user_id' => Auth::id(),
            'workspace_id' => $file->workspace_id,
            'model_type' => get_class($file),
            'model_id' => $file->id,
            'action' => 'deleted',
            'old_values' => $file->getAttributes(),
            'description' => "File '{$file->name}' telah dihapus",
        ]);
    }

    /**
     * Handle the File "restored" event.
     *
     * @param  \App\Models\File  $file
     * @return void
     */
    public function restored(File $file)
    {
        ActivityLog::create([
            'user_id' => Auth::id(),
            'workspace_id' => $file->workspace_id,
            'model_type' => get_class($file),
            'model_id' => $file->id,
            'action' => 'restored',
            'new_values' => $file->getAttributes(),
            'description' => "File '{$file->name}' telah dipulihkan",
        ]);
    }
}

