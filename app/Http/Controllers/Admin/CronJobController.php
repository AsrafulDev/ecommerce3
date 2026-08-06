<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CronJobSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class CronJobController extends Controller
{
    public function index()
    {
        // Ensure the courier sync job row always exists (e.g. after a server/machine
        // update the cron_job_settings table may be empty — auto-create the default).
        CronJobSetting::firstOrCreate(
            ['job_key' => 'courier_status_sync'],
            [
                'job_title'         => 'Courier Status Sync',
                'job_description'   => 'Pathao / Steadfast / RedX courier tracking status auto-sync.',
                'is_enabled'        => true,
                'frequency_minutes' => 10,
                'order_limit'       => 50,
            ]
        );

        $jobs = CronJobSetting::orderBy('id')->get();
        return view('backEnd.cronjob.index', compact('jobs'));
    }

    public function toggle(Request $request, $id)
    {
        $job = CronJobSetting::findOrFail($id);
        $job->is_enabled = !$job->is_enabled;
        $job->save();

        $state = $job->is_enabled ? 'চালু' : 'বন্ধ';
        return response()->json([
            'success'    => true,
            'is_enabled' => $job->is_enabled,
            'message'    => "\"{$job->job_title}\" এখন {$state} করা হয়েছে।",
        ]);
    }

    public function update_settings(Request $request, $id)
    {
        $request->validate([
            'frequency_minutes' => 'required|integer|min:1|max:1440',
            'order_limit'       => 'required|integer|min:1|max:500',
        ]);

        $job = CronJobSetting::findOrFail($id);
        $job->update([
            'frequency_minutes' => $request->frequency_minutes,
            'order_limit'       => $request->order_limit,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'সেটিংস সেভ হয়েছে।',
        ]);
    }

    public function run_now(Request $request, $id)
    {
        $job = CronJobSetting::findOrFail($id);

        if ($job->job_key !== 'courier_status_sync') {
            return response()->json(['success' => false, 'message' => 'Unknown job.'], 400);
        }

        if ($job->last_run_status === 'running') {
            return response()->json(['success' => false, 'message' => 'ইতিমধ্যে চলছে, একটু অপেক্ষা করুন।'], 409);
        }

        try {
            $limit = $job->order_limit ?: 50;
            Artisan::call("courier:check-status --limit={$limit} --force");
            $output = Artisan::output();

            // Reload to get updated stats
            $job->refresh();

            return response()->json([
                'success'        => true,
                'message'        => 'সফলভাবে রান করা হয়েছে।',
                'last_run_at'    => $job->last_run_at ? $job->last_run_at->format('d M Y, h:i A') : null,
                'last_run_status'=> $job->last_run_status,
                'last_run_result'=> $job->last_run_result,
                'updated_count'  => $job->last_updated_count,
                'failed_count'   => $job->last_failed_count,
                'output'         => trim($output),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function status(Request $request, $id)
    {
        $job = CronJobSetting::findOrFail($id);
        return response()->json([
            'is_enabled'      => $job->is_enabled,
            'last_run_at'     => $job->last_run_at ? $job->last_run_at->format('d M Y, h:i A') : null,
            'last_run_status' => $job->last_run_status,
            'last_run_result' => $job->last_run_result,
            'updated_count'   => $job->last_updated_count,
            'failed_count'    => $job->last_failed_count,
        ]);
    }
}
