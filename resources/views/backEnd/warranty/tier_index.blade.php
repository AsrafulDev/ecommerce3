@extends('backEnd.layouts.master')
@section('title', 'Warranty Tiers')
@section('content')
<div class="container-fluid">
    <h4 class="mb-3">🏷️ Product Warranty Tiers</h4>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Tiers</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($products as $product)
                        <tr>
                            <td>{{ $product->name }}</td>
                            <td>
                                @foreach($product->warrantyTiers as $tier)
                                    <span class="badge bg-{{ $tier->is_active ? 'success' : 'secondary' }} me-1">
                                        {{ $tier->tier_name }} ({{ $tier->warranty_days }}d @ {{ $tier->formatted_price }})
                                    </span>
                                @endforeach
                            </td>
                            <td>
                                <a href="{{ route('admin.warranty.tiers.edit', $product) }}" class="btn btn-sm btn-primary">Edit Tiers</a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $products->links() }}
        </div>
    </div>
</div>
@endsection
