@extends('backEnd.layouts.master')
@section('title', 'District Management')

@section('css')
<style>
    .district-card { border: none; box-shadow: 0 0 35px 0 rgba(154,161,171,.15); border-radius: 10px; }
    .district-table thead th { background: #f8f9fa; color: #555; font-weight: 600; font-size: 12px; text-transform: uppercase; letter-spacing: .5px; border-bottom: 2px solid #eef2f7; }
    .district-table tbody td { vertical-align: middle; font-size: 14px; }
    .district-badge { background: #eef4ff; color: #4e73df; border-radius: 30px; padding: 3px 12px; font-size: 11px; font-weight: 600; }
</style>
@endsection

@section('content')
<div class="container-fluid">
    <div class="row mb-3 mt-3">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h4 class="page-title mb-0" style="font-weight:700;color:#333;"><i class="fe-map-pin me-1"></i> {{ __('District Management') }}</h4>
            <div class="d-flex gap-2 align-items-center">
                <span class="text-muted">{{ $districtNames->count() }} districts · {{ $districts->count() }} areas</span>
                @if($markedCount > 0)
                    <span class="badge bg-warning text-dark">{{ $markedCount }} marked</span>
                @endif
                <form method="POST" action="{{ route('admin.district.sync') }}" onsubmit="return confirm('Restore missing default districts/areas? Existing records will be kept.')">
                    @csrf
                    <button type="submit" class="btn btn-info fw-bold px-3"><i class="fe-refresh-cw me-1"></i> {{ __('Sync Default') }}</button>
                </form>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card district-card mb-4">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="mb-0 fw-bold text-dark"><i class="fe-plus-circle me-2 text-primary"></i> {{ __('Add District / Area') }}</h6>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.district.store') }}" class="row g-2 align-items-end">
                        @csrf
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">{{ __('District') }} <span class="text-danger">*</span></label>
                            <input type="text" name="district" class="form-control" list="districtList" placeholder="e.g. Dhaka" required>
                            <datalist id="districtList">
                                @foreach($districtNames as $dn)
                                    <option value="{{ $dn }}">{{ $dn }}</option>
                                @endforeach
                            </datalist>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">{{ __('Area Name') }} <span class="text-danger">*</span></label>
                            <input type="text" name="area_name" class="form-control" placeholder="e.g. Mirpur" required>
                        </div>
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-primary w-100"><i class="fe-plus me-1"></i>{{ __('Add') }}</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card district-card">
                <div class="card-body">
                    @if($districts->isEmpty())
                        <div class="text-center py-5 text-muted">
                            <i data-feather="map-pin" style="width:44px;height:44px;color:#ccd6e0;"></i>
                            <p class="mt-2 mb-0">{{ __('No districts found. Click "Sync Default" to restore the 64 districts.') }}</p>
                        </div>
                    @else
                    <div class="table-responsive">
                        <table class="table table-hover district-table mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>{{ __('District') }}</th>
                                    <th>{{ __('Area') }}</th>
                                    <th>{{ __('Charge Update') }}</th>
                                    <th class="text-end">{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($districts as $key => $d)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td><span class="district-badge">{{ $d->district }}</span></td>
                                    <td>{{ $d->area_name }}</td>
                                    <td>
                                        @if($d->charge_update_required)
                                            <span class="badge bg-warning text-dark"><i class="fe-alert-triangle"></i> {{ __('Marked') }}</span>
                                        @else
                                            <span class="badge bg-soft-secondary text-secondary">{{ __('No') }}</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <form method="POST" action="{{ route('admin.district.toggle-charge') }}" class="d-inline" title="Mark for charge update">
                                            @csrf
                                            <input type="hidden" name="hidden_id" value="{{ $d->id }}">
                                            <button type="submit" class="btn btn-sm {{ $d->charge_update_required ? 'btn-warning' : 'btn-outline-warning' }}" title="Mark for shipping-charge update"><i class="fe-edit"></i></button>
                                        </form>
                                        <a href="{{ route('admin.district.edit', $d->id) }}" class="btn btn-sm btn-outline-primary" title="Edit"><i class="fe-edit-1"></i></a>
                                        <form method="POST" action="{{ route('admin.district.destroy') }}" class="d-inline" onsubmit="return confirm('Delete this district/area?')">
                                            @csrf
                                            <input type="hidden" name="hidden_id" value="{{ $d->id }}">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"><i class="fe-trash-2"></i></button>
                                        </form>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
    if (window.feather) { feather.replace(); }
</script>
@endsection
