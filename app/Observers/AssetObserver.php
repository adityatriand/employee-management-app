<?php

namespace App\Observers;

use App\Models\Asset;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

class AssetObserver
{
    /**
     * Handle the Asset "created" event.
     *
     * @param  \App\Models\Asset  $asset
     * @return void
     */
    public function created(Asset $asset)
    {
        $description = "Aset '{$asset->name}' telah dibuat";
        if ($asset->assignedEmployee) {
            $description .= " dan ditugaskan ke '{$asset->assignedEmployee->name}'";
        }

        ActivityLog::create([
            'user_id' => Auth::id(),
            'model_type' => get_class($asset),
            'model_id' => $asset->id,
            'action' => 'created',
            'new_values' => $asset->getAttributes(),
            'description' => $description,
        ]);
    }

    /**
     * Handle the Asset "updated" event.
     *
     * @param  \App\Models\Asset  $asset
     * @return void
     */
    public function updated(Asset $asset)
    {
        $oldValues = [];
        $newValues = [];
        
        foreach ($asset->getDirty() as $key => $value) {
            $oldValues[$key] = $asset->getOriginal($key);
            $newValues[$key] = $value;
        }

        if (!empty($oldValues)) {
            $description = "Aset '{$asset->name}' telah diupdate";
            
            // Special handling for assignment changes
            if (isset($oldValues['assigned_to']) || isset($newValues['assigned_to'])) {
                if (empty($oldValues['assigned_to']) && !empty($newValues['assigned_to'])) {
                    $description = "Aset '{$asset->name}' telah ditugaskan";
                } elseif (!empty($oldValues['assigned_to']) && empty($newValues['assigned_to'])) {
                    $description = "Aset '{$asset->name}' telah dikembalikan";
                } elseif ($oldValues['assigned_to'] != $newValues['assigned_to']) {
                    $description = "Aset '{$asset->name}' telah dipindahkan";
                }
            }

            ActivityLog::create([
                'user_id' => Auth::id(),
                'model_type' => get_class($asset),
                'model_id' => $asset->id,
                'action' => 'updated',
                'old_values' => $oldValues,
                'new_values' => $newValues,
                'description' => $description,
            ]);
        }
    }

    /**
     * Handle the Asset "deleted" event.
     *
     * @param  \App\Models\Asset  $asset
     * @return void
     */
    public function deleted(Asset $asset)
    {
        ActivityLog::create([
            'user_id' => Auth::id(),
            'model_type' => get_class($asset),
            'model_id' => $asset->id,
            'action' => 'deleted',
            'old_values' => $asset->getAttributes(),
            'description' => "Aset '{$asset->name}' telah dihapus",
        ]);
    }

    /**
     * Handle the Asset "restored" event.
     *
     * @param  \App\Models\Asset  $asset
     * @return void
     */
    public function restored(Asset $asset)
    {
        ActivityLog::create([
            'user_id' => Auth::id(),
            'model_type' => get_class($asset),
            'model_id' => $asset->id,
            'action' => 'restored',
            'new_values' => $asset->getAttributes(),
            'description' => "Aset '{$asset->name}' telah dipulihkan",
        ]);
    }
}

