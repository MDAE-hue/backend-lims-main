<?php

namespace App\Helpers;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ActivityLogger
{
    public static function log(
        string $actionType,
        string $targetTable,
        ?int $targetId = null,
        $oldData = null,
        $newData = null,
        ?string $description = null
    ) {
        $userId = Auth::id() ?? null;

        try {
            // Simpan ke database
            $log = ActivityLog::create([
                'user_id' => $userId,
                'action_type' => $actionType,
                'target_table' => $targetTable,
                'target_id' => $targetId,
                'old_data' => $oldData ? json_encode($oldData) : null,
                'new_data' => $newData ? json_encode($newData) : null,
                'description' => $description,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to log activity: '.$e->getMessage());
        }
    }

    public static function getAll($limit = 100)
    {
        return ActivityLog::with('user')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }
}
