<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LandingTemplate;
use App\Models\Product;
use App\Services\Landing\ContentValidator;
use App\Services\Landing\InvalidLandingContent;
use App\Services\Landing\LandingPage;
use App\Services\Landing\LandingPricing;
use App\Services\Landing\LandingThemes;
use App\Services\Landing\TemplateRegistry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Toastr;

/**
 * Admin side of the landing pages: the list with the create popup, the editor, and the
 * JSON endpoints that save content and settings.
 *
 * Ownership: a user with landing-admin sees and edits every landing page. Anyone else only
 * the pages they created. The template of a page is never changed here (plan D7).
 */
class LandingManageController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:landing-list', ['only' => ['index']]);
        $this->middleware('permission:landing-create', ['only' => ['store', 'duplicate']]);
        $this->middleware('permission:landing-edit', ['only' => ['edit', 'frame', 'save', 'settings', 'destroy', 'changeProduct', 'updateDetails']]);
    }

    public function index()
    {
        $query = LandingTemplate::with(['product:id,name', 'creator:id,name'])->orderByDesc('id');
        if (! Gate::allows('landing-admin')) {
            $query->where('created_by', auth('admin')->id());
        }

        return view('backEnd.landing.index', [
            'landings' => $query->get(),
            'templates' => collect(TemplateRegistry::keys())->mapWithKeys(fn ($key) => [$key => TemplateRegistry::label($key)]),
            'products' => Product::where('status', 1)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:191',
            'template_key' => ['required', Rule::in(TemplateRegistry::keys())],
            'product_id' => ['required', Rule::exists('products', 'id')->where('status', 1)],
        ], [
            'name.required' => 'Enter a title for the landing page.',
            'template_key.in' => 'Choose a template.',
            'product_id.exists' => 'Choose an active product.',
        ]);

        $landing = LandingTemplate::create([
            'name' => $data['name'],
            'slug' => LandingTemplate::uniqueSlug($data['name']),
            'template_key' => $data['template_key'],
            'product_id' => $data['product_id'],
            'created_by' => auth('admin')->id(),
            'content' => ContentValidator::clean(
                TemplateRegistry::defaultContent($data['template_key']),
                $data['template_key']
            ),
            'status' => false,
        ]);

        Toastr::success('Landing page created. Edit it below, then switch it on.', 'Success');

        return redirect()->route('landing.manage.edit', $landing->id);
    }

    public function edit(int $id)
    {
        $landing = $this->owned($id);

        return view('backEnd.landing.editor', [
            'landing' => $landing,
            'sections' => TemplateRegistry::schema($landing->template_key)['sections'],
            'theme' => array_merge(TemplateRegistry::defaultContent($landing->template_key)['theme'], $landing->content['theme'] ?? []),
            'themePresets' => LandingThemes::all(),
            'enabled' => collect($landing->content['sections'] ?? [])->map(fn ($s) => (bool) ($s['enabled'] ?? true)),
            'frameUrl' => route('landing.manage.frame', ['id' => $landing->id, 'editable' => 1]),
            'previewUrl' => route('landing.manage.frame', ['id' => $landing->id, 'editable' => 0]),
            'saveUrl' => route('landing.manage.save', $landing->id),
            'settingsUrl' => route('landing.manage.settings', $landing->id),
            'liveUrl' => url('/landing/' . $landing->slug),
        ]);
    }

    /** The landing page itself, shown inside the editor frame (editable) or as a read-only preview. */
    public function frame(Request $request, int $id)
    {
        $landing = $this->owned($id);
        $editable = $request->boolean('editable', true);

        return view('frontEnd.layouts.landing.' . $landing->template_key . '.page', LandingPage::viewData($landing, $editable));
    }

    /** Save changed elements, sections and colours. The whole result is validated before it is stored. */
    public function save(Request $request, int $id)
    {
        $landing = $this->owned($id);

        $request->validate([
            'elements' => 'nullable|array',
            'sections' => 'nullable|array',
            'theme' => 'nullable|array',
        ]);

        // Stored content may hold ids from an earlier version of the template; they are dropped, not refused.
        $stored = ContentValidator::prune(
            is_array($landing->content) ? $landing->content : TemplateRegistry::defaultContent($landing->template_key),
            $landing->template_key
        );

        $merged = [
            'theme' => array_merge($stored['theme'] ?? [], $request->input('theme', [])),
            'sections' => array_merge($stored['sections'] ?? [], $request->input('sections', [])),
            'elements' => array_merge($stored['elements'] ?? [], $request->input('elements', [])),
        ];

        try {
            $clean = ContentValidator::clean($merged, $landing->template_key);
        } catch (InvalidLandingContent $e) {
            return response()->json(['ok' => false, 'errors' => $e->errors], 422);
        }

        $landing->update(['content' => $clean]);

        return response()->json(['ok' => true, 'saved_at' => now()->toIso8601String()]);
    }

    /** Status, validity, extra discount and the landing's coupon. */
    public function settings(Request $request, int $id)
    {
        $landing = $this->owned($id);

        $data = $request->validate([
            'status' => 'nullable|boolean',
            'valid_from' => 'nullable|date',
            'valid_until' => 'nullable|date|after_or_equal:valid_from',
            'extra_discount_type' => ['nullable', Rule::in(['percent', 'fixed'])],
            'extra_discount_value' => 'nullable|numeric|min:0|max:1000000',
            'coupon_type' => ['nullable', Rule::in(['percent', 'fixed'])],
            'coupon_value' => 'nullable|numeric|min:0|max:1000000',
        ]);

        if (($data['extra_discount_type'] ?? null) === 'percent' && (float) ($data['extra_discount_value'] ?? 0) > 100) {
            return response()->json(['ok' => false, 'errors' => ['A percent discount cannot be more than 100.']], 422);
        }

        $landing->update([
            'status' => (bool) ($data['status'] ?? false),
            'valid_from' => $data['valid_from'] ?? null,
            'valid_until' => $data['valid_until'] ?? null,
        ]);

        // Extra discount is planned, not yet in the editor. Only touch it when the request sends it.
        if (array_key_exists('extra_discount_type', $data)) {
            $landing->update([
                'extra_discount_type' => $data['extra_discount_type'],
                'extra_discount_value' => $data['extra_discount_type'] ? ($data['extra_discount_value'] ?? 0) : null,
            ]);
        }

        if (! empty($data['coupon_type']) && isset($data['coupon_value'])) {
            $coupon = $landing->coupon_id ? \App\Models\Coupon::find($landing->coupon_id) : null;
            if ($coupon) {
                $coupon->update(['type' => $data['coupon_type'], 'value' => $data['coupon_value']]);
            } else {
                $coupon = LandingPricing::issueCoupon($landing->fresh(), $data['coupon_type'], (float) $data['coupon_value']);
            }
            // The coupon follows the landing's validity window.
            $coupon->update([
                'valid_from' => $landing->valid_from?->toDateString(),
                'valid_to' => $landing->valid_until?->toDateString(),
            ]);
        }

        return response()->json(['ok' => true, 'status' => $landing->fresh()->status]);
    }

    /**
     * Delete a landing page. Its coupon is switched off first, so the code cannot be used in the shop
     * afterwards; orders that already used it keep their record.
     */
    public function destroy(int $id)
    {
        $landing = $this->owned($id);
        $name = $landing->name;

        if ($landing->coupon_id) {
            \App\Models\Coupon::whereKey($landing->coupon_id)->update(['status' => 0]);
        }
        $landing->delete();

        Toastr::success(__('Landing page deleted: :name', ['name' => $name]), 'Success');

        return redirect()->route('landing.manage.index');
    }

    /**
     * A copy of a landing page: same template, content and product, with its own name and link. The copy is a draft.
     * The coupon and the validity window are not copied, so the copy starts with no code and no dates.
     */
    public function duplicate(int $id)
    {
        $source = $this->owned($id);
        $name = Str::limit($source->name.' (copy)', 191, '');

        $copy = LandingTemplate::create([
            'name' => $name,
            'slug' => LandingTemplate::uniqueSlug($name),
            'template_key' => $source->template_key,
            'product_id' => $source->product_id,
            'created_by' => auth('admin')->id(),
            'content' => $source->content,
            'status' => false,
            'extra_discount_type' => $source->extra_discount_type,
            'extra_discount_value' => $source->extra_discount_value,
        ]);

        Toastr::success(__('Copied. The copy is a draft: check it, then switch it on.'), 'Success');

        return redirect()->route('landing.manage.edit', $copy->id);
    }

    /** Points the landing page at another product. The template, content and link stay as they are. */
    public function changeProduct(Request $request, int $id)
    {
        $landing = $this->owned($id);

        $data = $request->validateWithBag('product', [
            'product_id' => ['required', Rule::exists('products', 'id')->where('status', 1)],
        ], [
            'product_id.required' => 'Choose a product.',
            'product_id.exists' => 'Choose an active product.',
        ]);

        $landing->update(['product_id' => $data['product_id']]);

        Toastr::success(__('Product changed for :name.', ['name' => $landing->name]), 'Success');

        return redirect()->route('landing.manage.index');
    }

    /**
     * The title and the link name. The template is never read here: a page keeps the template it was made with.
     * Changing the link name stops the old address working, so the page is shared again under the new one.
     */
    public function updateDetails(Request $request, int $id)
    {
        $landing = $this->owned($id);

        $request->merge(['slug' => Str::slug((string) $request->input('slug'))]);

        $data = $request->validateWithBag('details', [
            'name' => 'required|string|max:191',
            'slug' => ['required', 'string', 'max:191', Rule::unique('landing_templates', 'slug')->ignore($landing->id)],
        ], [
            'name.required' => 'Enter a title for the landing page.',
            'slug.required' => 'Enter a link name. It becomes part of the page address.',
            'slug.unique' => 'Another landing page already uses this link name.',
        ]);

        $landing->update(['name' => $data['name'], 'slug' => $data['slug']]);

        Toastr::success(__('Saved. The page address is now /landing/:slug.', ['slug' => $landing->slug]), 'Success');

        return redirect()->route('landing.manage.index');
    }

    /** A landing page the user may manage: its own, or any for landing-admin. */
    private function owned(int $id): LandingTemplate
    {
        $landing = LandingTemplate::findOrFail($id);

        if (! Gate::allows('landing-admin') && (int) $landing->created_by !== (int) auth('admin')->id()) {
            abort(403, 'This landing page belongs to another user.');
        }

        return $landing;
    }
}
