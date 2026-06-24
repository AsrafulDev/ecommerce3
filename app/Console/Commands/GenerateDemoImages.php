<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class GenerateDemoImages extends Command
{
    protected $signature = 'demo:generate-images';
    protected $description = 'Generate placeholder demo images';

    public function handle()
    {
        $dir = public_path('public/demo');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $images = [
            // Sliders (1200x400)
            ['slider-1.jpg', 1200, 400, '#0d6efd', '#ffffff', 'Big Sale!'],
            ['slider-2.jpg', 1200, 400, '#198754', '#ffffff', 'New Arrivals'],
            ['slider-3.jpg', 1200, 400, '#dc3545', '#ffffff', 'Flash Sale'],
            // Bottom ads (728x90)
            ['bottom-ad-1.jpg', 728, 90, '#6f42c1', '#ffffff', 'Special Offer'],
            // Footer ads
            ['footer-ad-1.jpg', 728, 90, '#fd7e14', '#ffffff', 'Free Shipping'],
            // Campaign
            ['campaign-1.jpg', 1200, 400, '#20c997', '#ffffff', 'Campaign'],
            // Reviews (200x200)
            ['review-1.jpg', 200, 200, '#e83e8c', '#ffffff', '★'],
            ['review-2.jpg', 200, 200, '#6f42c1', '#ffffff', '★★'],
            ['review-3.jpg', 200, 200, '#0d6efd', '#ffffff', '★★★'],
            // Home ads
            ['home-ad-1.jpg', 600, 400, '#dc3545', '#ffffff', 'Sale!'],
            ['home-ad-2.jpg', 600, 400, '#198754', '#ffffff', 'New!'],
            // Category images (400x400)
            ['cat-electronics.jpg', 400, 400, '#0d6efd', '#ffffff', 'Electronics'],
            ['cat-fashion.jpg', 400, 400, '#e83e8c', '#ffffff', 'Fashion'],
            ['cat-home.jpg', 400, 400, '#20c997', '#ffffff', 'Home'],
            ['cat-beauty.jpg', 400, 400, '#fd7e14', '#ffffff', 'Beauty'],
            ['cat-sports.jpg', 400, 400, '#198754', '#ffffff', 'Sports'],
            ['cat-books.jpg', 400, 400, '#6f42c1', '#ffffff', 'Books'],
            ['cat-baby.jpg', 400, 400, '#e83e8c', '#ffffff', 'Baby'],
            ['cat-auto.jpg', 400, 400, '#dc3545', '#ffffff', 'Auto'],
            // Brand images (200x100)
            ['brand-samsung.jpg', 200, 100, '#1428A0', '#ffffff', 'SAMSUNG'],
            ['brand-apple.jpg', 200, 100, '#555555', '#ffffff', 'Apple'],
            ['brand-sony.jpg', 200, 100, '#000000', '#ffffff', 'SONY'],
            ['brand-lg.jpg', 200, 100, '#A50034', '#ffffff', 'LG'],
            ['brand-hp.jpg', 200, 100, '#0096D6', '#ffffff', 'HP'],
            ['brand-dell.jpg', 200, 100, '#007DB8', '#ffffff', 'Dell'],
            ['brand-nike.jpg', 200, 100, '#000000', '#ffffff', 'NIKE'],
            ['brand-adidas.jpg', 200, 100, '#000000', '#ffffff', 'adidas'],
            ['brand-zara.jpg', 200, 100, '#0F0F0F', '#ffffff', 'ZARA'],
            ['brand-h&m.jpg', 200, 100, '#E50010', '#ffffff', 'H&M'],
        ];

        $count = 0;
        foreach ($images as $img) {
            $path = $dir . '/' . $img[0];
            if (File::exists($path)) continue;

            $this->generatePlaceholder($path, $img[1], $img[2], $img[3], $img[4], $img[5]);
            $count++;
        }

        // Product images (600x600)
        for ($i = 1; $i <= 10; $i++) {
            $path = $dir . '/product-' . $i . '.jpg';
            if (!File::exists($path)) {
                $hue = ($i * 37) % 360;
                $this->generatePlaceholder($path, 600, 600, $this->hslToHex($hue, 60, 50), '#ffffff', 'Product ' . $i);
                $count++;
            }
        }

        // Blog images (800x400)
        $blogs = [
            ['blog-top-10-smartphones-of-2026.jpg', '#0d6efd', 'Smartphones'],
            ['blog-summer-fashion-trends-2026.jpg', '#e83e8c', 'Fashion'],
            ['blog-how-to-choose-the-perfect-laptop.jpg', '#198754', 'Laptop Guide'],
        ];
        foreach ($blogs as $b) {
            $path = $dir . '/' . $b[0];
            if (!File::exists($path)) {
                $this->generatePlaceholder($path, 800, 400, $b[1], '#ffffff', $b[2]);
                $count++;
            }
        }

        $this->info("Generated {$count} placeholder images in public/demo/");
    }

    private function generatePlaceholder($path, $width, $height, $bgColor, $textColor, $text)
    {
        $img = imagecreatetruecolor($width, $height);

        // Parse hex colors
        list($r, $g, $b) = sscanf($bgColor, '#%02x%02x%02x');
        $bg = imagecolorallocate($img, $r, $g, $b);

        list($tr, $tg, $tb) = sscanf($textColor, '#%02x%02x%02x');
        $tc = imagecolorallocate($img, $tr, $tg, $tb);

        imagefill($img, 0, 0, $bg);

        // Center text
        $fontSize = max(12, min(40, $width / 15));
        $angle = 0;
        $fontFile = __DIR__ . '/../../../storage/fonts/arial.ttf';

        if (file_exists($fontFile)) {
            $bbox = imagettfbbox($fontSize, $angle, $fontFile, $text);
            $x = ($width - ($bbox[2] - $bbox[0])) / 2;
            $y = ($height - ($bbox[1] - $bbox[7])) / 2;
            imagettftext($img, $fontSize, $angle, $x, $y, $tc, $fontFile, $text);
        } else {
            // Fallback: center text using built-in font
            $fontSize = 5;
            $textWidth = strlen($text) * imagefontwidth($fontSize);
            $textHeight = imagefontheight($fontSize);
            $x = ($width - $textWidth) / 2;
            $y = ($height - $textHeight) / 2;
            imagestring($img, $fontSize, $x, $y, $text, $tc);
        }

        imagejpeg($img, $path, 85);
        imagedestroy($img);
    }

    private function hslToHex($h, $s, $l)
    {
        $h /= 360;
        $s /= 100;
        $l /= 100;
        $r = $l;
        $g = $l;
        $b = $l;
        $v = ($l <= 0.5) ? ($l * (1 + $s)) : ($l + $s - $l * $s);
        if ($v > 0) {
            $m = $l + $l - $v;
            $sv = ($v - $m) / $v;
            $h *= 6;
            $sextant = floor($h);
            $fract = $h - $sextant;
            $vsf = $v * $sv * $fract;
            $mid1 = $m + $vsf;
            $mid2 = $v - $vsf;
            switch ($sextant) {
                case 0: $r = $v; $g = $mid1; $b = $m; break;
                case 1: $r = $mid2; $g = $v; $b = $m; break;
                case 2: $r = $m; $g = $v; $b = $mid1; break;
                case 3: $r = $m; $g = $mid2; $b = $v; break;
                case 4: $r = $mid1; $g = $m; $b = $v; break;
                case 5: $r = $v; $g = $m; $b = $mid2; break;
            }
        }
        return sprintf('#%02x%02x%02x', round($r * 255), round($g * 255), round($b * 255));
    }
}
