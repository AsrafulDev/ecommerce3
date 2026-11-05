{{-- Luxury template: the shared head and body, with its own fonts and stylesheet. Section order is fixed here;
     each section can be switched off from the landing's content, and the checkout is always shown. --}}
@include('frontEnd.layouts.landing._head', ['fontsUrl' => 'https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;700&family=Hind+Siliguri:wght@400;600;700&display=swap'])
@include('frontEnd.layouts.landing._body', ['sections' => [
    'nav' => 'frontEnd.layouts.landing.luxury.sections.nav',
    'hero' => 'frontEnd.layouts.landing.sections.hero',
    'problem' => 'frontEnd.layouts.landing.sections.problem',
    'solution' => 'frontEnd.layouts.landing.sections.solution',
    'features' => 'frontEnd.layouts.landing.sections.features',
    'gallery' => 'frontEnd.layouts.landing.luxury.sections.gallery',
    'reviews' => 'frontEnd.layouts.landing.sections.reviews',
    'faq' => 'frontEnd.layouts.landing.sections.faq',
]])
