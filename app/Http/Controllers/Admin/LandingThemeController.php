<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LandingTheme;
use App\Services\Landing\LandingThemes;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Toastr;

/**
 * The landing page theme manager, under the Landing Page menu. Built-in themes are read-only; they can be copied into
 * a custom theme, which can then be changed or deleted. A landing page keeps the colours it was saved with, so changing
 * or deleting a theme here does not change any page.
 */
class LandingThemeController extends Controller
{
    private const COLOUR_KEYS = ['primary', 'accent', 'background', 'text'];

    public function __construct()
    {
        $this->middleware('permission:landing-list', ['only' => ['index']]);
        $this->middleware('permission:landing-edit', ['only' => ['create', 'store', 'edit', 'update', 'copy', 'destroy']]);
    }

    public function index()
    {
        return view('backEnd.landing.themes.index', ['themes' => LandingThemes::all()]);
    }

    public function create()
    {
        return view('backEnd.landing.themes.form', [
            'theme' => null,
            'colours' => LandingThemes::presets()['gold']['colours'],
            'action' => route('landing.themes.store'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        LandingTheme::create($data + ['created_by' => auth('admin')->id()]);

        Toastr::success(__('Theme saved.'), 'Success');

        return redirect()->route('landing.themes.index');
    }

    public function edit(int $id)
    {
        $theme = LandingTheme::findOrFail($id);

        return view('backEnd.landing.themes.form', [
            'theme' => $theme,
            'colours' => $theme->colours(),
            'action' => route('landing.themes.update', $theme->id),
        ]);
    }

    public function update(Request $request, int $id)
    {
        $theme = LandingTheme::findOrFail($id);
        $theme->update($this->validated($request, $theme->id));

        Toastr::success(__('Theme saved.'), 'Success');

        return redirect()->route('landing.themes.index');
    }

    /** A new custom theme copied from a built-in or a custom one. The copy can then be changed. */
    public function copy(Request $request)
    {
        $data = $request->validate(['source' => ['required', 'string', 'max:60']]);
        $source = $this->source($data['source']);

        $copy = LandingTheme::create([
            'name' => $this->uniqueName(Str::limit($source['name'].' (copy)', 100, '')),
            'primary' => $source['colours']['primary'],
            'accent' => $source['colours']['accent'],
            'background' => $source['colours']['background'],
            'text' => $source['colours']['text'],
            'created_by' => auth('admin')->id(),
        ]);

        Toastr::success(__('Copied. Change the colours and save.'), 'Success');

        return redirect()->route('landing.themes.edit', $copy->id);
    }

    public function destroy(int $id)
    {
        $theme = LandingTheme::findOrFail($id);
        $name = $theme->name;
        $theme->delete();

        Toastr::success(__('Theme deleted: :name', ['name' => $name]), 'Success');

        return redirect()->route('landing.themes.index');
    }

    /** The name and colours of a theme named by its key: 'builtin-gold' or 'custom-3'. */
    private function source(string $key): array
    {
        if (str_starts_with($key, 'builtin-')) {
            $preset = LandingThemes::presets()[substr($key, strlen('builtin-'))] ?? abort(404);

            return ['name' => $preset['label'], 'colours' => $preset['colours']];
        }

        if (preg_match('/^custom-(\d+)$/', $key, $match)) {
            $theme = LandingTheme::findOrFail((int) $match[1]);

            return ['name' => $theme->name, 'colours' => $theme->colours()];
        }

        abort(404);
    }

    /** The name is unique: a copy of a theme that already has a copy gets the next number. */
    private function uniqueName(string $base): string
    {
        $name = $base;
        for ($n = 2; LandingTheme::where('name', $name)->exists(); $n++) {
            $name = Str::limit($base, 96, '').' '.$n;
        }

        return $name;
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:100', Rule::unique('landing_themes', 'name')->ignore($ignoreId)],
        ];
        $messages = [
            'name.required' => 'Enter a name for the theme.',
            'name.unique' => 'Another theme already has this name.',
        ];
        foreach (self::COLOUR_KEYS as $key) {
            $rules[$key] = ['required', 'regex:/^#[0-9a-fA-F]{6}$/'];
            $messages[$key.'.regex'] = 'Each colour must be a hex value such as #4f46e5.';
            $messages[$key.'.required'] = 'Choose every colour.';
        }

        $data = $request->validate($rules, $messages);
        foreach (self::COLOUR_KEYS as $key) {
            $data[$key] = strtolower($data[$key]);
        }

        return $data;
    }
}
