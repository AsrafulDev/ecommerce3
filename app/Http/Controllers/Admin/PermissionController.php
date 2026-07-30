<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Toastr;
use DB;

class PermissionController extends Controller
{
    /**
     * All known permission strings used across admin controllers.
     * These are the canonical set — keep sorted/groups for readability.
     */
    const ALL_PERMISSIONS = [
        // Banner
        'banner-list', 'banner-create', 'banner-edit', 'banner-delete',
        // Banner Category
        'banner-category-list', 'banner-category-create', 'banner-category-edit', 'banner-category-delete',
        // Category
        'category-list', 'category-create', 'category-edit', 'category-delete',
        // Child Category
        'childcategory-list', 'childcategory-create', 'childcategory-edit', 'childcategory-delete',
        // Color
        'color-list', 'color-create', 'color-edit', 'color-delete',
        // Contact
        'contact-list', 'contact-create', 'contact-edit', 'contact-delete',
        // Contact Message
        'contact-message-list', 'contact-message-edit', 'contact-message-delete',
        // Layout
        'layout-list', 'layout-create', 'layout-edit', 'layout-delete',
        // Page
        'page-list', 'page-create', 'page-edit', 'page-delete',
        // Permission
        'permission-list', 'permission-create', 'permission-edit', 'permission-delete',
        // Product
        'product-list', 'product-create', 'product-edit', 'product-delete',
        // Review (commented in controller but may be activated)
        'review-list', 'review-create', 'review-edit', 'review-delete',
        // Role
        'role-list', 'role-create', 'role-edit', 'role-delete',
        // Setting
        'setting-list', 'setting-create', 'setting-edit', 'setting-delete',
        // Shipping
        'shipping-list', 'shipping-create', 'shipping-edit', 'shipping-delete',
        // Size (commented in controller but may be activated)
        'size-list', 'size-create', 'size-edit', 'size-delete',
        // Social
        'social-list', 'social-create', 'social-edit', 'social-delete',
        // Sub Category
        'subcategory-list', 'subcategory-create', 'subcategory-edit', 'subcategory-delete',
        // Theme
        'theme-list', 'theme-create', 'theme-edit', 'theme-delete',
    ];

    function __construct()
    {
         $this->middleware('permission:permission-list|permission-create|permission-edit|permission-delete', ['only' => ['index','store']]);
         $this->middleware('permission:permission-create', ['only' => ['create','store']]);
         $this->middleware('permission:permission-edit', ['only' => ['edit','update']]);
         $this->middleware('permission:permission-delete', ['only' => ['destroy']]);
    }
    
    public function index(Request $request)
    {
        // ✅ Show only admin guard permissions in admin panel
        $show_data = Permission::where('guard_name', 'admin')->orderBy('name','ASC')->get();
        $totalActive = count(self::ALL_PERMISSIONS);
        $totalExisting = $show_data->count();
        return view('backEnd.permissions.index', compact('show_data', 'totalActive', 'totalExisting'));
    }
    
    public function create()
    {
        return view('backEnd.permissions.create');
    }
    
    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'required|unique:permissions,name,NULL,id,guard_name,admin',
        ]);
        $input = $request->all();
        $input['guard_name'] = 'admin';
        Permission::create($input);
        
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        
        Toastr::success('Success', 'Data store successfully');
        return redirect()->route('permissions.index');
    }
    
    public function edit($id)
    {
        $edit_data = Permission::find($id);
        return view('backEnd.permissions.edit', compact('edit_data'));
    }
    
    public function update(Request $request)
    {
        $this->validate($request, [
            'name' => 'required|unique:permissions,name,'.$request->hidden_id.',id,guard_name,admin',
        ]);
        $input = $request->except('hidden_id');
        $input['guard_name'] = 'admin';
        $update_data = Permission::find($request->hidden_id);
        if (!$update_data) {
            Toastr::error('Error', 'Record not found');
            return redirect()->back();
        }
        $update_data->update($input);
        
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        
        Toastr::success('Success', 'Data update successfully');
        return redirect()->route('permissions.index');
    }

    public function destroy(Request $request)
    {
        $delete_data = Permission::find($request->hidden_id);
        if (!$delete_data) {
            Toastr::error('Error', 'Record not found');
            return redirect()->back();
        }
        $delete_data->delete();
        Toastr::success('Success', 'Data delete successfully');
        return redirect()->back();
    }

    /**
     * Sync all known permissions — create any that are missing.
     */
    public function syncPermissions()
    {
        $created = 0;
        $skipped = 0;

        foreach (self::ALL_PERMISSIONS as $name) {
            $exists = Permission::where('name', $name)
                ->where('guard_name', 'admin')
                ->exists();

            if (!$exists) {
                Permission::create([
                    'name'       => $name,
                    'guard_name' => 'admin',
                ]);
                $created++;
            } else {
                $skipped++;
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Toastr::success(
            "{$created} new permissions added, {$skipped} already exist. Total: " . count(self::ALL_PERMISSIONS),
            'Permissions Synced'
        );

        return redirect()->route('permissions.index');
    }
}
