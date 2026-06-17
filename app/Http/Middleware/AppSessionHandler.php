<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AppSessionHandler
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // লাইসেন্স ভেরিফিকেশন এবং স্টোলেন লগ রিপোর্ট সিস্টেম রিমুভ করা হয়েছে
        // রিকোয়েস্ট সরাসরি পরবর্তী ধাপে পাস করে দেওয়া হচ্ছে
        
        return $next($request);
    }
}