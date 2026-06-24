{{-- Latest Blogs Section --}}
@if(isset($blogs) && $blogs->count() > 0)
<section class="homeproduct blog-home-section">
    <div class="container">
        <div class="row">
            <div class="col-sm-12">
                <div class="sec_title">
                    <h3 class="section-title-header">
                        <span class="section-title-name">Latest Blogs</span>
                        <a href="{{ route('blogs') }}" class="view_more_btn">View All</a>
                    </h3>
                </div>
            </div>
        </div>
        <div class="row">
            @foreach($blogs as $blog)
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="blog-home-card">
                    <div class="blog-home-img">
                        <a href="{{ route('blog.details', $blog->slug) }}">
                            @if($blog->image)
                                <img src="{{ url('public/'.$blog->image) }}"
                                     alt="{{ $blog->title }}"
                                     loading="lazy"
                                     width="100%"
                                     height="220">
                            @else
                                <img src="{{ url('public/no-image.png') }}"
                                     alt="No Image"
                                     loading="lazy"
                                     width="100%"
                                     height="220">
                            @endif
                        </a>
                    </div>
                    <div class="blog-home-content">
                        <div class="blog-home-meta">
                            {{ $blog->created_at->format('d M Y') }}
                            | {{ $blog->views }}
                        </div>
                        <h5 class="blog-home-title">
                            <a href="{{ route('blog.details', $blog->slug) }}">
                                {{ Str::limit($blog->title, 55) }}
                            </a>
                        </h5>
                        <p>{{ Str::limit($blog->short_description, 110) }}</p>
                        <a href="{{ route('blog.details', $blog->slug) }}" class="read-more-link">
                            Read More →
                        </a>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>
@endif
