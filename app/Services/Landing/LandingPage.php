<?php

namespace App\Services\Landing;

use App\Models\LandingTemplate;
use App\Models\Product;
use App\Models\ProductVariantPrice;
use App\Models\ShippingCharge;

/**
 * Reads a landing page's content for the views. Single elements fall back to the template's
 * default when nothing is stored. List rows come only from what is stored, so a row the owner
 * removed does not come back from the defaults.
 *
 * In editable mode (the admin editor's frame) the page also carries the attributes the editor
 * script needs, and the blank list rows that "Add" reveals.
 */
final class LandingPage
{
    private readonly array $content;

    private readonly array $defaults;

    private readonly array $sections;

    public function __construct(private readonly LandingTemplate $landing, private readonly bool $editable = false)
    {
        $this->content = is_array($landing->content) ? $landing->content : [];
        $this->defaults = TemplateRegistry::defaultContent($landing->template_key);
        $this->sections = TemplateRegistry::schema($landing->template_key)['sections'];
    }

    /**
     * Everything the storefront page and the editor frame need, for one landing page.
     *
     * @return array<string, mixed>
     */
    public static function viewData(LandingTemplate $landing, bool $editable = false): array
    {
        $product = Product::with('image')->where('id', $landing->product_id)->firstOrFail();

        $now = now();
        $open = $editable || LandingPricing::isOpen($landing, $now);

        return [
            'page' => new self($landing, $editable),
            'landing' => $landing,
            'product' => $product,
            'variants' => ProductVariantPrice::with(['color', 'size'])->where('product_id', $product->id)->get(),
            'areas' => ShippingCharge::where('status', '1')->orderBy('amount')->get(),
            'open' => $open,
        ];
    }

    public function landing(): LandingTemplate
    {
        return $this->landing;
    }

    public function isEditable(): bool
    {
        return $this->editable;
    }

    public function text(string $id): string
    {
        return $this->stored($id) ?? (string) ($this->defaults['elements'][$id]['value'] ?? '');
    }

    /**
     * Whether a field should be drawn. In the editor every field is shown, so it can be filled.
     * On the live page an empty field is hidden.
     */
    public function show(string $id): bool
    {
        return $this->editable || $this->text($id) !== '';
    }

    /**
     * Whether a section has anything to show on the live page: a text field, an image, or a list row.
     * A section with nothing in it is left out of the page.
     */
    public function sectionHasContent(string $section): bool
    {
        if ($this->editable) {
            return true;
        }

        foreach (array_keys($this->sections[$section]['elements'] ?? []) as $id) {
            if ($this->text($id) !== '') {
                return true;
            }
        }

        foreach (array_keys($this->sections[$section]['lists'] ?? []) as $list) {
            if ($this->items($section, $list) !== []) {
                return true;
            }
        }

        return false;
    }

    /** Rich text, sanitised again on the way out. */
    public function rich(string $id): string
    {
        return ContentValidator::sanitizeRich($this->text($id));
    }

    /** Public URL of a media-library image, or '' when none is set. */
    public function image(string $id): string
    {
        $path = $this->text($id);

        return $path === '' ? '' : asset($path);
    }

    public function enabled(string $section): bool
    {
        return (bool) ($this->content['sections'][$section]['enabled'] ?? $this->defaults['sections'][$section]['enabled'] ?? true);
    }

    public function colour(string $key): string
    {
        return (string) ($this->content['theme'][$key] ?? $this->defaults['theme'][$key] ?? '#000000');
    }

    /** Editor hook for an element. Empty outside editable mode. */
    public function attr(string $id, string $type = 'text'): string
    {
        if (! $this->editable) {
            return '';
        }

        $editable = $type === 'image' ? '' : ' contenteditable="true" spellcheck="false"';

        return ' data-el="' . e($id) . '" data-type="' . e($type) . '"' . $editable;
    }

    /** Editor hook for a whole section wrapper, so a switch can dim it. */
    public function sectionAttr(string $section): string
    {
        return ' data-section="' . e($section) . '"' . ($this->enabled($section) ? '' : ' data-off="1"');
    }

    /**
     * A star rating. On the live page it is plain stars. In the editor it is five stars to click,
     * which set the rating field (1 to 5).
     */
    public function stars(string $id): string
    {
        $value = max(0, min(5, (int) $this->text($id)));

        if (! $this->editable) {
            return $value > 0 ? '<span class="fx-stars" aria-label="' . $value . ' / 5">' . str_repeat('★', $value) . '</span>' : '';
        }

        $buttons = '';
        for ($n = 1; $n <= 5; $n++) {
            $buttons .= '<button type="button" class="lp-star' . ($n <= $value ? ' is-on' : '') . '" data-value="' . $n . '" aria-label="' . $n . '">★</button>';
        }

        return '<span class="lp-star-edit" data-el="' . e($id) . '" data-type="rating">' . $buttons . '</span>';
    }

    /** Buttons the editor adds to a list: "add item" under the list. */
    public function addButton(string $section, string $list = 'item'): string
    {
        if (! $this->editable) {
            return '';
        }

        return '<button type="button" class="lp-row-add" data-list="' . e("{$section}.{$list}") . '">+ '
            . e(__('Add item')) . '</button>';
    }

    /** The grid that holds a list's rows, so the editor can find its blank rows. */
    public function listAttr(string $section, string $list = 'item'): string
    {
        return $this->editable ? ' data-row-list="' . e("{$section}.{$list}") . '"' : '';
    }

    /** Row class and the blank-row class for the editor. */
    public function rowClass(array $row): string
    {
        if (! $this->editable) {
            return '';
        }

        return $row['_filled'] ? ' lp-row' : ' lp-row lp-blank';
    }

    /** The remove button for a row, shown only in the editor. */
    public function rowRemove(string $section, string $list, array $row): string
    {
        if (! $this->editable || ! $row['_filled']) {
            return '';
        }

        return '<button type="button" class="lp-row-remove" data-row="' . e("{$section}.{$list}.{$row['_n']}")
            . '" aria-label="' . e(__('Remove')) . '">×</button>';
    }

    /**
     * Numbered list rows (section.list.N.field) in order. Outside the editor only rows with a value
     * are returned. The editor also gets every empty row, marked with _filled = false.
     *
     * @return array<int, array<string, mixed>>
     */
    public function items(string $section, string $list = 'item'): array
    {
        $def = $this->sections[$section]['lists'][$list] ?? null;
        if ($def === null) {
            return [];
        }

        $rows = [];
        for ($n = 1; $n <= $def['max_items']; $n++) {
            $row = [];
            $filled = false;

            foreach ($def['fields'] as $field => $spec) {
                $value = $this->stored("{$section}.{$list}.{$n}.{$field}") ?? '';
                if ($spec['type'] === 'rich') {
                    $value = ContentValidator::sanitizeRich($value);
                }
                $row[$field] = $value;
                $filled = $filled || $value !== '';
            }

            $row['_n'] = $n;
            $row['_filled'] = $filled;

            if ($filled || $this->editable) {
                $rows[] = $row;
            }
        }

        return $rows;
    }

    private function stored(string $id): ?string
    {
        $value = $this->content['elements'][$id]['value'] ?? null;

        return is_string($value) ? trim($value) : null;
    }
}
