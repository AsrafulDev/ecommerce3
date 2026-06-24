<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\HomepageLayout;
use App\Models\HomepageSection;
use App\Models\HomepageLayoutSection;
use App\Models\GeneralSetting;
use Illuminate\Support\Facades\Storage;
use Toastr;

class LayoutController extends Controller
{
    function __construct()
    {
        $this->middleware('permission:layout-list|layout-create|layout-edit|layout-delete', ['only' => ['index', 'store']]);
        $this->middleware('permission:layout-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:layout-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:layout-delete', ['only' => ['destroy']]);
    }

    public function index()
    {
        $layouts = HomepageLayout::withCount('sections')->orderBy('is_default', 'desc')->orderBy('name')->get();
        $activeLayout = HomepageLayout::where('is_active', true)->first();
        return view('backEnd.layout.index', compact('layouts', 'activeLayout'));
    }

    public function create()
    {
        return view('backEnd.layout.edit', ['edit_data' => null]);
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'required|max:100',
        ]);

        $input = $request->all();
        $input['created_by'] = auth()->guard('admin')->id();
        $input['is_active'] = $request->boolean('is_active');
        $input['is_default'] = $request->boolean('is_default');

        if ($input['is_default']) {
            HomepageLayout::where('is_default', true)->update(['is_default' => false]);
        }

        // If first layout, make it active
        if (HomepageLayout::count() === 0) {
            $input['is_active'] = true;
            $input['is_default'] = true;
        }

        HomepageLayout::create($input);

        Toastr::success('Layout created successfully!', 'Success');
        return redirect()->route('layouts.index');
    }

    public function edit($id)
    {
        $edit_data = HomepageLayout::findOrFail($id);
        return view('backEnd.layout.edit', compact('edit_data'));
    }

    public function update(Request $request)
    {
        $this->validate($request, [
            'name' => 'required|max:100',
        ]);

        $layout = HomepageLayout::findOrFail($request->id);
        $input = $request->all();
        $input['is_active'] = $request->boolean('is_active');
        $input['is_default'] = $request->boolean('is_default');

        if ($input['is_default']) {
            HomepageLayout::where('id', '!=', $layout->id)->update(['is_default' => false]);
        }

        $layout->update($input);

        Toastr::success('Layout updated successfully!', 'Success');
        return redirect()->route('layouts.index');
    }

    /**
     * Builder interface for a specific layout
     */
    public function builder($id)
    {
        $layout = HomepageLayout::with(['sections' => function ($q) {
            $q->orderBy('sort_order');
        }, 'sections.section'])->findOrFail($id);

        $availableSections = HomepageSection::where('is_active', true)->orderBy('default_order')->get();

        return view('backEnd.layout.builder', compact('layout', 'availableSections'));
    }

    /**
     * AJAX: Add a section to the layout
     */
    public function addSection(Request $request)
    {
        $request->validate([
            'layout_id' => 'required|exists:homepage_layouts,id',
            'section_id' => 'required|exists:homepage_sections,id',
        ]);

        $maxOrder = HomepageLayoutSection::where('layout_id', $request->layout_id)->max('sort_order') ?? 0;

        $layoutSection = HomepageLayoutSection::create([
            'layout_id' => $request->layout_id,
            'section_id' => $request->section_id,
            'sort_order' => $maxOrder + 1,
            'is_visible' => true,
            'columns_config' => 'col-sm-12',
        ]);

        $layoutSection->load('section');

        return response()->json([
            'success' => true,
            'section' => $layoutSection,
            'html' => view('backEnd.layout.partials.section-item', ['ls' => $layoutSection])->render(),
        ]);
    }

    /**
     * AJAX: Reorder sections (drag-and-drop)
     */
    public function reorderSections(Request $request)
    {
        $request->validate([
            'layout_id' => 'required|exists:homepage_layouts,id',
            'sections' => 'required|array',
            'sections.*.id' => 'required|exists:homepage_layout_sections,id',
            'sections.*.sort_order' => 'required|integer|min:1',
        ]);

        foreach ($request->sections as $item) {
            HomepageLayoutSection::where('id', $item['id'])
                ->where('layout_id', $request->layout_id)
                ->update(['sort_order' => $item['sort_order']]);
        }

        return response()->json(['success' => true]);
    }

    /**
     * AJAX: Toggle section visibility
     */
    public function toggleSection(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:homepage_layout_sections,id',
            'is_visible' => 'required|boolean',
        ]);

        $ls = HomepageLayoutSection::findOrFail($request->id);
        $ls->is_visible = $request->is_visible;
        $ls->save();

        return response()->json(['success' => true, 'is_visible' => $ls->is_visible]);
    }

    /**
     * AJAX: Update section settings
     */
    public function updateSectionSettings(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:homepage_layout_sections,id',
        ]);

        $ls = HomepageLayoutSection::findOrFail($request->id);
        $ls->columns_config = $request->columns_config ?? $ls->columns_config;
        $ls->extra_settings = $request->extra_settings ?? $ls->extra_settings;

        // Handle breakpoints (responsive visibility)
        $breakpoints = [];
        $breakpoints['desktop'] = $request->boolean('visible_desktop', true);
        $breakpoints['tablet'] = $request->boolean('visible_tablet', true);
        $breakpoints['mobile'] = $request->boolean('visible_mobile', true);
        $ls->breakpoints = $breakpoints;

        $ls->save();

        Toastr::success('Section settings updated!', 'Success');
        return redirect()->back();
    }

    /**
     * AJAX: Remove a section from layout
     */
    public function removeSection(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:homepage_layout_sections,id',
        ]);

        HomepageLayoutSection::destroy($request->id);

        return response()->json(['success' => true]);
    }

    /**
     * Apply a layout (set as active)
     */
    public function apply($id)
    {
        $layout = HomepageLayout::findOrFail($id);

        HomepageLayout::where('id', '!=', $id)->update(['is_active' => false]);
        $layout->is_active = true;
        $layout->save();

        // Also update general_settings
        $setting = GeneralSetting::first();
        if ($setting) {
            $setting->active_layout_id = $layout->id;
            $setting->save();
        }

        Toastr::success("Layout '{$layout->name}' applied successfully!", 'Success');
        return redirect()->route('layouts.index');
    }

    public function destroy(Request $request)
    {
        $layout = HomepageLayout::findOrFail($request->hidden_id);

        if ($layout->is_default) {
            Toastr::error('Cannot delete the default layout!', 'Error');
            return redirect()->back();
        }

        GeneralSetting::where('active_layout_id', $layout->id)->update(['active_layout_id' => null]);
        $layout->delete();

        Toastr::success('Layout deleted successfully!', 'Success');
        return redirect()->route('layouts.index');
    }

    public function inactive(Request $request)
    {
        $layout = HomepageLayout::findOrFail($request->hidden_id);
        $layout->is_active = false;
        $layout->save();
        Toastr::success('Layout deactivated!', 'Success');
        return redirect()->back();
    }

    public function active(Request $request)
    {
        $layout = HomepageLayout::findOrFail($request->hidden_id);
        $layout->is_active = true;
        $layout->save();
        Toastr::success('Layout activated!', 'Success');
        return redirect()->back();
    }

    /**
     * Render a single homepage section in isolation for screenshot capture.
     */
    public function previewSection($slug)
    {
        $section = HomepageSection::where('slug', $slug)->firstOrFail();
        
        // Load the same homepage data the frontend uses
        $data = $this->getHomepagePreviewData();
        
        // Add the section for rendering
        $data['section'] = $section;
        
        return view('backEnd.layout.section-preview', $data);
    }

    /**
     * AJAX: Capture screenshot - receives base64 image, saves it, updates DB.
     */
    public function captureScreenshot(Request $request)
    {
        $request->validate([
            'section_id' => 'required|exists:homepage_sections,id',
            'image_data' => 'required|string', // base64 PNG data
        ]);

        $section = HomepageSection::findOrFail($request->section_id);

        // Decode base64 image
        $imageData = $request->image_data;
        if (preg_match('/^data:image\/(\w+);base64,/', $imageData, $matches)) {
            $imageData = substr($imageData, strpos($imageData, ',') + 1);
        }
        $imageData = base64_decode($imageData);
        if ($imageData === false) {
            return response()->json(['success' => false, 'message' => 'Invalid image data']);
        }

        // Save image to storage
        $filename = 'section-' . $section->slug . '-' . time() . '.png';
        $path = 'uploads/section-previews/' . $filename;
        Storage::disk('public')->put($path, $imageData);

        // Update the section record
        $section->preview_image = 'storage/' . $path;
        $section->save();

        return response()->json([
            'success' => true,
            'image_url' => asset($section->preview_image),
            'message' => 'Screenshot captured!',
        ]);
    }

    /**
     * Get homepage data for section preview rendering.
     */
    protected function getHomepagePreviewData()
    {
        $generalsetting = GeneralSetting::where('status', 1)->limit(1)->first();

        $menucategories = \App\Models\Category::where('status', 1)
            ->where('parent_id', 0)
            ->select('id', 'name', 'slug', 'image')
            ->with(['subcategories.childcategories'])
            ->orderBy('id', 'ASC')
            ->get();

        $sliders = \App\Models\Banner::where(['status' => 1, 'category_id' => 1])
            ->select('id', 'image', 'link')
            ->get();

        $brands = \App\Models\Brand::where('status', 1)
            ->select('id', 'name', 'slug', 'image')
            ->limit(12)
            ->get();

        $blogs = \App\Models\Blog::where('status', 1)
            ->latest()
            ->limit(3)
            ->get();

        $campaognads = \App\Models\Banner::where(['status' => 1, 'category_id' => 7])
            ->select('id', 'image', 'link')
            ->limit(1)
            ->get();

        $sliderbottomads = \App\Models\Banner::where(['status' => 1, 'category_id' => 5])
            ->select('id', 'image', 'link')
            ->limit(3)
            ->get();

        $footertopads = \App\Models\Banner::where(['status' => 1, 'category_id' => 6])
            ->select('id', 'image', 'link')
            ->limit(3)
            ->get();

        $reviews = \App\Models\Banner::where(['status' => 1, 'category_id' => 8])
            ->select('id', 'image', 'link')
            ->limit(3)
            ->get();

        $flas_sales = \App\Models\Product::where(['status' => 1, 'approval_status' => 'approved', 'flashsale' => 1])
            ->orderBy('id', 'DESC')
            ->select('id', 'name', 'slug', 'new_price', 'old_price', 'stock')
            ->with(['prosizes', 'procolors', 'image', 'reviews'])
            ->limit(12)
            ->get();

        $hotdeal_top = \App\Models\Product::where(['status' => 1, 'approval_status' => 'approved', 'topsale' => 1])
            ->orderBy('id', 'DESC')
            ->select('id', 'name', 'slug', 'new_price', 'old_price', 'stock')
            ->with(['prosizes', 'procolors', 'image', 'reviews'])
            ->limit(12)
            ->get();

        // Compute active flags
        $isHotDealActive = false;
        $isFlashSaleActive = false;
        if ($generalsetting) {
            $hotDealEndDate = $generalsetting->hot_deal_end_date . 'T23:59:59';
            $flashSaleEndDate = $generalsetting->flash_sale_end_date . 'T23:59:59';
            $isHotDealActive = $hotDealEndDate && \Carbon\Carbon::parse($hotDealEndDate)->isFuture();
            $isFlashSaleActive = $flashSaleEndDate && \Carbon\Carbon::parse($flashSaleEndDate)->isFuture();
        }

        return compact(
            'generalsetting',
            'menucategories',
            'sliders',
            'brands',
            'blogs',
            'campaognads',
            'sliderbottomads',
            'footertopads',
            'reviews',
            'flas_sales',
            'hotdeal_top',
            'isHotDealActive',
            'isFlashSaleActive'
        );
    }
}
