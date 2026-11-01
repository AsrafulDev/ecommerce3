{{-- Grocery & organic template (Matir Ghran design): cream and green, a wood-pressed oil product page. --}}
@include('frontEnd.layouts.landing._head', ['fontsUrl' => 'https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&display=swap'])
@include('frontEnd.layouts.landing._body', ['sections' => [
    'nav' => 'frontEnd.layouts.landing.grocery.sections.nav',
    'hero' => 'frontEnd.layouts.landing.grocery.sections.hero',
    'benefits' => 'frontEnd.layouts.landing.grocery.sections.benefits',
    'details' => 'frontEnd.layouts.landing.grocery.sections.details',
    'concerns' => 'frontEnd.layouts.landing.grocery.sections.concerns',
    'gallery' => 'frontEnd.layouts.landing.grocery.sections.gallery',
    'trust' => 'frontEnd.layouts.landing.grocery.sections.trust',
    'reviews' => 'frontEnd.layouts.landing.grocery.sections.reviews',
    'offer' => 'frontEnd.layouts.landing.grocery.sections.offer',
    'policy' => 'frontEnd.layouts.landing.grocery.sections.policy',
    'faq' => 'frontEnd.layouts.landing.grocery.sections.faq',
    'final' => 'frontEnd.layouts.landing.grocery.sections.final',
]])
