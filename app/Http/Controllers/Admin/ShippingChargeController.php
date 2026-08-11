<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ShippingCharge;
use App\Models\District;
use Toastr;

class ShippingChargeController extends Controller
{    
    function __construct()
    {
        $this->middleware('permission:shipping-list|shipping-create|shipping-edit|shipping-delete', ['only' => ['index', 'store']]);
        $this->middleware('permission:shipping-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:shipping-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:shipping-delete', ['only' => ['destroy']]);
    }

    public function index(Request $request)
    {
        $show_data = ShippingCharge::with('districts')->orderBy('id', 'ASC')->get();
        return view('backEnd.shippingcharge.index', compact('show_data'));
    }

    public function create()
    {
        $districts = District::orderBy('district')->orderBy('area_name')->get()
            ->groupBy('district')->sortKeys();
        return view('backEnd.shippingcharge.create', compact('districts'));
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'required',
            'amount' => 'required|numeric|min:0',
            'status' => 'nullable',
        ]);        

        $input = [
            'name'   => $request->name,
            'amount' => $request->amount,
            'status' => $request->status ? 1 : 0,
        ];
        $charge = ShippingCharge::create($input);

        // Optional: attach multiple districts/areas
        $this->syncDistricts($charge, $request->district_ids);

        Toastr::success('Success', 'Data insert successfully');
        return redirect()->route('shippingcharges.index');
    }

    public function edit($id)
    {
        $edit_data = ShippingCharge::with('districts')->find($id);
        $districts = District::orderBy('district')->orderBy('area_name')->get()
            ->groupBy('district')->sortKeys();
        $selectedDistrictIds = $edit_data ? $edit_data->districts->pluck('id')->all() : [];
        return view('backEnd.shippingcharge.edit', compact('edit_data', 'districts', 'selectedDistrictIds'));
    }

    public function update(Request $request)
    {
        $this->validate($request, [
            'name' => 'required',
            'amount' => 'required|numeric|min:0',
            'status' => 'nullable',
        ]);
        $update_data = ShippingCharge::find($request->id);

        $input = [
            'name'   => $request->name,
            'amount' => $request->amount,
            'status' => $request->status ? 1 : 0,
        ];
        $update_data->update($input);

        // Optional: attach multiple districts/areas
        $this->syncDistricts($update_data, $request->district_ids);

        Toastr::success('Success', 'Data update successfully');
        return redirect()->route('shippingcharges.index');
    }

    /**
     * Sync the selected district/area ids onto the charge (empty => detach all).
     */
    protected function syncDistricts(ShippingCharge $charge, $districtIds)
    {
        $ids = is_array($districtIds) ? array_map('intval', $districtIds) : [];
        $charge->districts()->sync($ids);
    }

    public function inactive(Request $request)
    {
        $inactive = ShippingCharge::find($request->hidden_id);
        $inactive->status = 0;
        $inactive->save();
        Toastr::success('Success', 'Data inactive successfully');
        return redirect()->back();
    }

    public function active(Request $request)
    {
        $active = ShippingCharge::find($request->hidden_id);
        $active->status = 1;
        $active->save();
        Toastr::success('Success', 'Data active successfully');
        return redirect()->back();
    }

    public function destroy(Request $request)
    {
        $delete_data = ShippingCharge::find($request->hidden_id);
        $delete_data->delete();
        Toastr::success('Success', 'Data delete successfully');
        return redirect()->back();
    }
}
