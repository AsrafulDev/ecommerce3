{{-- Fashion template (ELAN design): cream page, rose accents, arch photos, a dark band and an offer card. --}}
@include('frontEnd.layouts.landing._head', ['fontsUrl' => 'https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,500;0,700;1,500&family=Hind+Siliguri:wght@400;500;600;700&display=swap'])
@include('frontEnd.layouts.landing._body', ['sections' => [
    'nav' => 'frontEnd.layouts.landing.fashion.sections.nav',
    'hero' => 'frontEnd.layouts.landing.fashion.sections.hero',
    'benefits' => 'frontEnd.layouts.landing.fashion.sections.benefits',
    'details' => 'frontEnd.layouts.landing.fashion.sections.details',
    'care' => 'frontEnd.layouts.landing.fashion.sections.care',
    'craft' => 'frontEnd.layouts.landing.fashion.sections.craft',
    'gallery' => 'frontEnd.layouts.landing.fashion.sections.gallery',
    'reviews' => 'frontEnd.layouts.landing.fashion.sections.reviews',
    'offer' => 'frontEnd.layouts.landing.fashion.sections.offer',
    'policy' => 'frontEnd.layouts.landing.fashion.sections.policy',
    'faq' => 'frontEnd.layouts.landing.fashion.sections.faq',
    'final' => 'frontEnd.layouts.landing.fashion.sections.final',
]])
