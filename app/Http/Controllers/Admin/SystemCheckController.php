<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Audit\SystemCheck;
use App\Services\Audit\SystemRepair;
use Illuminate\Http\Request;

class SystemCheckController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:setting-edit');
    }

    public function index(SystemCheck $check)
    {
        return view('backEnd.system.check', ['checks' => $check->run()]);
    }

    public function repair(Request $request, SystemRepair $repair)
    {
        $request->validate(['confirm' => 'accepted'], ['confirm.accepted' => 'Tick the box to confirm the repair.']);

        $summary = $repair->run();
        log_activity('system', 'repair', 'System repair: stock products set '.$summary['stock_products'], null, $summary);

        return redirect()->route('admin.system.check')->with('success', 'Repair finished. Review the results below.');
    }
}
