<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\District;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Toastr;

class DistrictController extends Controller
{
    public function index(Request $request)
    {
        $districts = District::orderBy('district')->orderBy('area_name')->get();
        $districtNames = District::distinct()->orderBy('district')->pluck('district');
        $markedCount = District::where('charge_update_required', 1)->count();
        return view('backEnd.district.index', compact('districts', 'districtNames', 'markedCount'));
    }

    /**
     * Restore the default store: insert any default district/area rows that are
     * missing (does NOT delete existing user records).
     */
    public function syncDefault()
    {
        $defaultRows = \Database\Seeders\DistrictSeeder::defaultRows();

        $existing = DB::table('districts')->get(['district', 'area_name'])
            ->map(fn($r) => strtolower(trim($r->district)).'|'.strtolower(trim($r->area_name)))
            ->flip();

        $inserted = 0;
        $now = now();
        $maxAreaId = (int) DB::table('districts')->max('area_id');

        foreach ($defaultRows as $row) {
            $key = strtolower(trim($row['district'])).'|'.strtolower(trim($row['area_name']));
            if ($existing->has($key)) {
                continue;
            }
            $maxAreaId++;
            DB::table('districts')->insert([
                'area_id'        => $maxAreaId,
                'area_name'      => $row['area_name'],
                'district'       => $row['district'],
                'created_at'     => $now,
                'updated_at'     => $now,
            ]);
            $inserted++;
        }

        if ($inserted > 0) {
            Toastr::success($inserted . ' default district/area record(s) restored.', 'Success');
        } else {
            Toastr::info('Default districts already present.', 'Info');
        }
        return back();
    }

    /**
     * Toggle the "mark for shipping-charge update" flag on a district/area.
     */
    public function toggleChargeUpdate(Request $request)
    {
        $district = District::findOrFail($request->hidden_id);
        $district->charge_update_required = !$district->charge_update_required;
        $district->save();

        Toastr::success($district->charge_update_required
            ? '"' . $district->area_name . '" marked for charge update.'
            : 'Mark removed from "' . $district->area_name . '".', 'Success');
        return back();
    }

    public function store(Request $request)
    {
        $request->validate([
            'district'      => 'required|string|max:255',
            'area_name'     => 'required|string|max:255',
        ]);

        $maxAreaId = (int) District::max('area_id');
        District::create([
            'area_id'        => $maxAreaId + 1,
            'district'       => $request->district,
            'area_name'      => $request->area_name,
        ]);

        Toastr::success('District added successfully.', 'Success');
        return back();
    }

    public function edit($id)
    {
        $district = District::findOrFail($id);
        return view('backEnd.district.edit', compact('district'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'id'            => 'required|exists:districts,id',
            'district'      => 'required|string|max:255',
            'area_name'     => 'required|string|max:255',
        ]);

        $district = District::findOrFail($request->id);
        $district->update([
            'district'              => $request->district,
            'area_name'             => $request->area_name,
            'charge_update_required'=> $request->has('charge_update_required') ? 1 : 0,
        ]);

        Toastr::success('District updated successfully.', 'Success');
        return back();
    }

    public function destroy(Request $request)
    {
        $district = District::findOrFail($request->hidden_id);
        $district->delete();

        Toastr::success('District deleted successfully.', 'Success');
        return back();
    }
}
