@extends('backEnd.layouts.master')
@section('title', 'VAT Settings')

@section('content')
<section class="sms-breadcrumb mb-20">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="breadcrumb-banner d-flex flex-wrap justify-content-between align-items-center">
                    <h1>VAT Settings</h1>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="container-fluid">
    <div class="row">
        <div class="col-lg-8">
            <div class="white-box">
                <p class="text-muted">
                    VAT is calculated only when it is switched on AND you have chosen whether prices include VAT.
                    Changing either affects lines created after the change; existing sales and purchases keep the VAT they were recorded with.
                </p>

                <form action="{{ route('admin.vat.update') }}" method="POST">
                    @csrf

                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="vat_enabled" name="vat_enabled" value="1"
                               @checked(old('vat_enabled', $settings->vat_enabled))>
                        <label class="form-check-label" for="vat_enabled"><strong>Calculate VAT</strong></label>
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><strong>Do the selling prices include VAT?</strong></label>
                        @php $inclusive = old('vat_prices_include_vat', $settings->vat_prices_include_vat); @endphp
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="vat_prices_include_vat" id="incl_yes" value="1"
                                   @checked((string) $inclusive === '1')>
                            <label class="form-check-label" for="incl_yes">Yes, VAT is already in the price</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="vat_prices_include_vat" id="incl_no" value="0"
                                   @checked((string) $inclusive === '0')>
                            <label class="form-check-label" for="incl_no">No, VAT is added on top of the price</label>
                        </div>
                        <small class="text-muted">Not decided yet? Leave both unticked. VAT stays off.</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><strong>How often do you file the VAT return?</strong></label>
                        @php $frequency = old('vat_filing_frequency', $settings->vat_filing_frequency); @endphp
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="vat_filing_frequency" id="freq_monthly" value="monthly"
                                   @checked($frequency === 'monthly')>
                            <label class="form-check-label" for="freq_monthly">Monthly</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="vat_filing_frequency" id="freq_quarterly" value="quarterly"
                                   @checked($frequency === 'quarterly')>
                            <label class="form-check-label" for="freq_quarterly">Quarterly</label>
                        </div>
                        <small class="text-muted">Not decided yet? Leave both unticked. The return stays not final until you choose.</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="vat_bin"><strong>VAT registration number (BIN)</strong></label>
                        <input type="text" id="vat_bin" name="vat_bin" class="form-control @error('vat_bin') is-invalid @enderror"
                               value="{{ old('vat_bin', $settings->vat_bin) }}" maxlength="12" placeholder="12 digits">
                        @error('vat_bin')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="vat_business_name"><strong>Registered business name</strong></label>
                        <input type="text" id="vat_business_name" name="vat_business_name" class="form-control @error('vat_business_name') is-invalid @enderror"
                               value="{{ old('vat_business_name', $settings->vat_business_name) }}">
                        @error('vat_business_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="vat_business_address"><strong>Registered address</strong></label>
                        <textarea id="vat_business_address" name="vat_business_address" rows="2" class="form-control @error('vat_business_address') is-invalid @enderror">{{ old('vat_business_address', $settings->vat_business_address) }}</textarea>
                        @error('vat_business_address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <button type="submit" class="primary-btn fix-gr-bg">Save VAT settings</button>
                </form>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="white-box">
                <h4>Product VAT rates</h4>
                <p class="mb-1"><strong>{{ $productsWithRate }}</strong> products have a VAT rate.</p>
                <p class="mb-3"><strong>{{ $productsWithoutRate }}</strong> products have no rate. Their sales and purchases carry no VAT.</p>
                <a href="{{ route('inhouse.products.index') }}" class="btn btn-sm btn-outline-secondary">Open products</a>
            </div>
        </div>
    </div>
</div>
@endsection
