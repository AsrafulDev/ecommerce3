<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\SingleLayout as Layout;
use App\Services\SingleLayout\SingleLayout;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Toastr;

/**
 * The Single Layout manager: saved product detail page designs. One is active, and visitors see it on every product page.
 * The editor shows the real product page in a frame and applies changes to it as they are made; nothing is saved until Save.
 */
class SingleLayoutController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:theme-list', ['only' => ['index', 'edit']]);
        $this->middleware('permission:theme-edit', ['only' => ['store', 'update', 'activate', 'duplicate', 'destroy']]);
    }

    public function index()
    {
        SingleLayout::ensureDefault();

        return view('backEnd.single-layout.index', [
            'layouts' => Layout::orderByDesc('is_active')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('single_layouts', 'name')],
        ], ['name.required' => 'Enter a name for the layout.', 'name.unique' => 'Another layout already has this name.']);

        $layout = Layout::create([
            'name' => $data['name'],
            'settings' => SingleLayout::defaults(),
            'created_by' => auth('admin')->id(),
        ]);

        Toastr::success(__('Layout created. Design it, then save.'), 'Success');

        return redirect()->route('single-layout.edit', $layout->id);
    }

    public function edit(Request $request, int $id)
    {
        $layout = Layout::findOrFail($id);
        $products = Product::where('status', 1)->where('approval_status', 'approved')->orderBy('name')->get(['id', 'name', 'slug']);

        return view('backEnd.single-layout.edit', [
            'layout' => $layout,
            'settings' => array_replace_recursive(SingleLayout::defaults(), $layout->settings),
            'products' => $products,
            'product' => $products->firstWhere('slug', $request->query('product')) ?? $products->first(),
            'sectionLabels' => SingleLayout::SECTION_LABELS,
            'colourLabels' => SingleLayout::COLOUR_LABELS,
            'themePresets' => SingleLayout::themePresets(),
            'customButtonMax' => SingleLayout::CUSTOM_BUTTON_MAX,
        ]);
    }

    public function update(Request $request, int $id)
    {
        $layout = Layout::findOrFail($id);

        $rules = [
            'name' => ['required', 'string', 'max:100', Rule::unique('single_layouts', 'name')->ignore($layout->id)],
            'layout.gallery' => ['required', Rule::in(['left', 'right'])],
            'shapes.button' => ['required', Rule::in(SingleLayout::SHAPES)],
            'shapes.badge' => ['required', Rule::in(SingleLayout::SHAPES)],
            'extra_text' => ['nullable', 'string', 'max:'.SingleLayout::EXTRA_TEXT_MAX],
            'custom_buttons' => ['nullable', 'array', 'max:'.SingleLayout::CUSTOM_BUTTON_MAX],
            'custom_buttons.*.label' => ['nullable', 'string', 'max:40'],
            'custom_buttons.*.link' => ['nullable', 'string', 'max:255'],
            'custom_buttons.*.background' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'custom_buttons.*.hover_background' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'custom_buttons.*.text' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'custom_buttons.*.hover_text' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ];
        $messages = [
            'name.required' => 'Enter a name for the layout.',
            'name.unique' => 'Another layout already has this name.',
            'extra_text.max' => 'The extra text can be at most '.SingleLayout::EXTRA_TEXT_MAX.' characters.',
            'custom_buttons.max' => 'A layout can have at most '.SingleLayout::CUSTOM_BUTTON_MAX.' custom buttons.',
        ];
        foreach (SingleLayout::COLOUR_KEYS as $key) {
            $rules['colours.'.$key] = ['required', 'regex:/^#[0-9a-fA-F]{6}$/'];
            $messages['colours.'.$key.'.regex'] = 'Each colour must be a hex value such as #4f46e5.';
        }
        $data = $request->validate($rules, $messages);
        $this->checkCustomButtonLinks($data['custom_buttons'] ?? []);

        // An unticked switch is not sent, so each section is read as on or off from the request.
        $settings = SingleLayout::fromInput([
            'colours' => $data['colours'],
            'shapes' => $data['shapes'],
            'sections' => array_combine(SingleLayout::SECTION_KEYS, array_map(
                fn (string $key) => $request->boolean('sections.'.$key),
                SingleLayout::SECTION_KEYS
            )),
            'layout' => ['gallery' => $data['layout']['gallery'], 'sticky_info' => $request->boolean('layout.sticky_info')],
            'extra_text' => $data['extra_text'] ?? '',
            'custom_buttons' => $data['custom_buttons'] ?? [],
        ]);

        $layout->update(['name' => $data['name'], 'settings' => $settings]);
        SingleLayout::forgetActive();

        Toastr::success(__('Layout saved.'), 'Success');

        return redirect()->route('single-layout.edit', $layout->id);
    }

    public function activate(int $id)
    {
        $layout = Layout::findOrFail($id);
        Layout::query()->update(['is_active' => false]);
        $layout->update(['is_active' => true]);
        SingleLayout::forgetActive();

        Toastr::success(__('Active layout: :name', ['name' => $layout->name]), 'Success');

        return redirect()->route('single-layout.index');
    }

    public function duplicate(int $id)
    {
        $source = Layout::findOrFail($id);
        $name = Str::limit($source->name.' (copy)', 100, '');
        for ($n = 2; Layout::where('name', $name)->exists(); $n++) {
            $name = Str::limit($source->name.' (copy)', 96, '').' '.$n;
        }

        $copy = Layout::create(['name' => $name, 'settings' => $source->settings, 'created_by' => auth('admin')->id()]);

        Toastr::success(__('Copied. The copy is not active until you activate it.'), 'Success');

        return redirect()->route('single-layout.edit', $copy->id);
    }

    /** A custom button with a text needs a link that opens a web address, a phone number or an email address. */
    private function checkCustomButtonLinks(array $buttons): void
    {
        $errors = [];
        foreach ($buttons as $index => $button) {
            if (trim((string) ($button['label'] ?? '')) === '') {
                continue;
            }
            $link = trim((string) ($button['link'] ?? ''));
            if ($link === '') {
                $errors["custom_buttons.$index.link"] = __('Enter the link for each custom button.');
            } elseif (! preg_match(SingleLayout::LINK_PATTERN, $link)) {
                $errors["custom_buttons.$index.link"] = __('A custom button link must start with https://, tel: or mailto:.');
            }
        }

        if ($errors) {
            throw ValidationException::withMessages($errors);
        }
    }

    public function destroy(int $id)
    {
        $layout = Layout::findOrFail($id);
        if ($layout->is_active) {
            Toastr::error(__('The active layout cannot be deleted. Activate another layout first.'), 'Not deleted');

            return back();
        }

        $name = $layout->name;
        $layout->delete();

        Toastr::success(__('Layout deleted: :name', ['name' => $name]), 'Success');

        return redirect()->route('single-layout.index');
    }
}
