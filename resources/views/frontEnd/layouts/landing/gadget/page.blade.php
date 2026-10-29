{{-- Gadget template (VOLTA style): dark page, blue accents, a spec table and a comparison. --}}
@include('frontEnd.layouts.landing._head', ['fontsUrl' => 'https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Hind+Siliguri:wght@400;600;700&display=swap'])
@include('frontEnd.layouts.landing._body', ['sections' => [
    'hero' => 'frontEnd.layouts.landing.sections.hero',
    'benefits' => 'frontEnd.layouts.landing.sections.cards',
    'specs' => ['frontEnd.layouts.landing.sections.table', ['columns' => [
        'label' => ['label' => 'Specification'],
        'value' => ['label' => 'Detail'],
    ]]],
    'gallery' => 'frontEnd.layouts.landing.sections.gallery',
    'compare' => ['frontEnd.layouts.landing.sections.table', ['columns' => [
        'feature' => ['label' => 'Feature'],
        'ours' => ['id' => 'compare.col_ours'],
        'other' => ['id' => 'compare.col_other'],
    ]]],
    'reviews' => 'frontEnd.layouts.landing.sections.reviews',
    'policy' => 'frontEnd.layouts.landing.sections.cards',
    'faq' => 'frontEnd.layouts.landing.sections.faq',
    'final' => 'frontEnd.layouts.landing.sections.final',
]])
