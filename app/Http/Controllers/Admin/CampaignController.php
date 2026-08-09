<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\CampaignReview;
use App\Models\Campaign;
use Image;
use Toastr;
use Str;
use File;
use Illuminate\Support\Facades\DB;

class CampaignController extends Controller
{
    public function index(Request $request)
    {
        $show_data = Campaign::orderBy('id','DESC')->get();
        return view('backEnd.campaign.index',compact('show_data'));
    }
    public function create()
    {
        $products = Product::where(['status'=>1])
            ->with('image')
            ->select('id','name','new_price','old_price','status')
            ->get();

        // Lightweight product payload used by the live preview pane
        $preview_products = $products->map(function($p) {
            return [
                'id'        => $p->id,
                'name'      => $p->name,
                'new_price' => $p->new_price,
                'old_price' => $p->old_price,
                'image'     => $p->image ? asset($p->image->image) : null,
            ];
        })->values();

        $default_sections = array_fill_keys(array_keys(\App\Models\Campaign::SECTIONS), true);
        $section_config   = $this->defaultSectionConfig();
        $default_labels   = \App\Models\Campaign::LABELS;
        $features         = [];
        $problem          = [];
        $solution         = [];
        $benefits         = [];
        $trust            = [];
        $faq              = [];
        $cta              = [];
        $reviews          = [];

        return view('backEnd.campaign.create', compact('products', 'preview_products', 'default_sections', 'section_config', 'default_labels', 'features', 'problem', 'solution', 'benefits', 'trust', 'faq', 'cta', 'reviews'));
    }

    /**
     * A pristine section config (all visible, default labels/positions, empty fields).
     */
    protected function defaultSectionConfig(): array
    {
        $out = [];
        foreach (\App\Models\Campaign::SECTIONS as $key => $label) {
            $d = \App\Models\Campaign::SECTION_DEFAULTS[$key] ?? ['label' => $label, 'position' => count($out) + 1];
            $out[$key] = [
                'label'    => $d['label'],
                'position' => $d['position'],
                'visible'  => true,
                'title'    => '',
                'text'     => '',
                'feature'  => '',
            ];
        }
        return $out;
    }
    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'required',
            'short_description' => 'nullable',
            'description' => 'nullable',
            'banner' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'image_one' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'image_two' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'image_three' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'product_id' => 'required|array|min:1|exists:products,id',
            'image.*' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'review' => 'required',
            'deadline' => 'nullable|date|after:now', // Ensure deadline is a future date
            'top_title_1' => 'nullable|string|max:255',
            'top_title_2' => 'nullable|string|max:255',
            'heading_1' => 'nullable|string|max:255',
            'feature_1' => 'nullable|string|max:255',
            'feature_2' => 'nullable|string|max:255',
            'heading_2' => 'nullable|string|max:255',
            'heading_3' => 'nullable|string|max:255',
            'heading_4' => 'nullable|string|max:255',
            'note' => 'nullable|string|max:255',
            'billing_details' => 'nullable|string|max:255',
        
        ]);
    
        // Prepare the input data
        $input = $request->except('image', 'product_id');
        $input['status'] = true; // Set status to true if not checked
        $input['sections'] = $this->buildSections($request);
        $input['labels']   = $this->buildLabels($request);
        $input['features'] = $this->buildFeatures($request);
        $input['problem']  = $this->buildProblem($request);
        $input['solution'] = $this->buildSolution($request);
        $input['benefits'] = $this->buildBenefits($request);
        $input['trust']    = $this->buildTrust($request);
        $input['faq']      = $this->buildFaq($request);
        $input['cta']      = $this->buildCta($request);
        $input['reviews']  = $this->buildReviews($request);
    
        // Handle the first selected product ID
        $firstProductId = $request->product_id[0];
        $input['product_id'] = $firstProductId;
    
        // Handle Banner Image
        if ($request->hasFile('banner')) {
            $banner = $request->file('banner');
            $bannerName = time() . '-' . strtolower(preg_replace('/\s+/', '-', $banner->getClientOriginalName()));
            $uploadPath = 'public/uploads/campaign/';
            $bannerUrl = $uploadPath . $bannerName;
            $banner->move($uploadPath, $bannerName);
            $input['banner'] = $bannerUrl;
        }
    
        // Handle Image One
        if ($request->hasFile('image_one')) {
            $image_one = $request->file('image_one');
            $name1 = time() . '-' . strtolower(preg_replace('/\s+/', '-', $image_one->getClientOriginalName()));
            $name1 = preg_replace('"\.(jpg|jpeg|png|webp)$"', '.webp', $name1);
            $uploadPath1 = 'public/uploads/campaign/';
            $imageUrl1 = $uploadPath1 . $name1;
    
            $img1 = Image::make($image_one->getRealPath());
            $img1->encode('webp', 90);
            $img1->save($imageUrl1);
            $input['image_one'] = $imageUrl1;
        }
    
        // Handle Image Two
        if ($request->hasFile('image_two')) {
            $image_two = $request->file('image_two');
            $name2 = time() . '-' . strtolower(preg_replace('/\s+/', '-', $image_two->getClientOriginalName()));
            $name2 = preg_replace('"\.(jpg|jpeg|png|webp)$"', '.webp', $name2);
            $uploadPath2 = 'public/uploads/campaign/';
            $imageUrl2 = $uploadPath2 . $name2;
    
            $img2 = Image::make($image_two->getRealPath());
            $img2->encode('webp', 90);
            $img2->save($imageUrl2);
            $input['image_two'] = $imageUrl2;
        }
    
        // Handle Image Three
        if ($request->hasFile('image_three')) {
            $image_three = $request->file('image_three');
            $name3 = time() . '-' . strtolower(preg_replace('/\s+/', '-', $image_three->getClientOriginalName()));
            $name3 = preg_replace('"\.(jpg|jpeg|png|webp)$"', '.webp', $name3);
            $uploadPath3 = 'public/uploads/campaign/';
            $imageUrl3 = $uploadPath3 . $name3;
    
            $img3 = Image::make($image_three->getRealPath());
            $img3->encode('webp', 90);
            $img3->save($imageUrl3);
            $input['image_three'] = $imageUrl3;
        }
    
        // Create slug
        $input['slug'] = strtolower(Str::slug($request->name));
        $input['video'] = $this->getYouTubeVideoId($request->video);
    
        // Create a new campaign
        $campaign = Campaign::create($input);
        // Attach remaining selected products to the pivot table
        $remainingProductIds = array_slice($request->product_id, 1);
        if (!empty($remainingProductIds)) {
            $campaign->products()->attach($remainingProductIds);
        }
    
        // Handle additional images (review images)
        if ($request->hasFile('image')) {
            foreach ($request->file('image') as $image) {
                $name = time() . '-' . strtolower(preg_replace('/\s+/', '-', $image->getClientOriginalName()));
                $uploadPath = 'public/uploads/campaign/';
                $image->move($uploadPath, $name);
                $imageUrl = $uploadPath . $name;
    
                $pimage = new CampaignReview();
                $pimage->campaign_id = $campaign->id;
                $pimage->image = $imageUrl;
                $pimage->save();
            }
        }
    
        Toastr::success('Success', 'Campaign created successfully');
        return redirect()->route('campaign.index');
    }

    
    
    public function edit($id)
    {
        // Fetch the campaign with its related images and products
        $edit_data = Campaign::with('images')->findOrFail($id);

        // Additional products attached via the pivot table
        $select_products = $edit_data->products()->with('image')->get();

        // Products shown on the landing page = pivot products + the primary product
        // (mirrors FrontendController::campaign). Build the preview payload from them.
        $preview_products = collect();
        if ($edit_data->product_id) {
            $primary = Product::with('image')->find($edit_data->product_id);
            if ($primary) {
                $preview_products->push([
                    'id'        => $primary->id,
                    'name'      => $primary->name,
                    'new_price' => $primary->new_price,
                    'old_price' => $primary->old_price,
                    'image'     => $primary->image ? asset($primary->image->image) : null,
                ]);
            }
        }
        foreach ($select_products as $p) {
            if ($p->id == $edit_data->product_id) {
                continue; // already added as primary
            }
            $preview_products->push([
                'id'        => $p->id,
                'name'      => $p->name,
                'new_price' => $p->new_price,
                'old_price' => $p->old_price,
                'image'     => $p->image ? asset($p->image->image) : null,
            ]);
        }
        $preview_products = $preview_products->values();

        $default_sections = array_fill_keys(array_keys(\App\Models\Campaign::SECTIONS), true);
        $sections         = $edit_data->sections ?: $default_sections;
        $section_config   = $edit_data->sectionConfig();
        $default_labels   = \App\Models\Campaign::LABELS;
        $labels           = array_merge($default_labels, is_array($edit_data->labels) ? $edit_data->labels : []);

        // Features: stored JSON, or fall back to legacy feature_1/feature_2/heading_3/heading_4/image_three
        $features = is_array($edit_data->features) && !empty($edit_data->features)
            ? $edit_data->features
            : $this->legacyFeatures($edit_data);

        $problem  = is_array($edit_data->problem)  ? $edit_data->problem  : [];
        $solution = is_array($edit_data->solution) ? $edit_data->solution : [];
        $benefits = is_array($edit_data->benefits) ? $edit_data->benefits : [];
        $trust    = is_array($edit_data->trust)    ? $edit_data->trust    : [];
        $faq      = is_array($edit_data->faq)      ? $edit_data->faq      : [];
        $cta      = is_array($edit_data->cta)      ? $edit_data->cta      : [];
        $reviews  = is_array($edit_data->reviews)  ? $edit_data->reviews  : [];

        // Fetch all available products
        $products = Product::where('status', 1)->select('id', 'name', 'status')->get();

        return view('backEnd.campaign.edit', compact('edit_data', 'products', 'select_products', 'preview_products', 'sections', 'default_sections', 'section_config', 'default_labels', 'labels', 'features', 'problem', 'solution', 'benefits', 'trust', 'faq', 'cta', 'reviews'));
    }

    /**
     * Build the Features grid from the legacy top-level columns
     * (feature_1, feature_2, heading_3, heading_4, image_three) when no JSON features exist yet.
     */
    protected function legacyFeatures($campaign): array
    {
        $out = [];
        $card = trim((string) ($campaign->labels['features_card'] ?? 'Feature'));
        if ($campaign->feature_1) {
            $out[] = ['icon' => '✨', 'image' => '', 'title' => $card, 'text' => $campaign->feature_1];
        }
        if ($campaign->feature_2) {
            $out[] = ['icon' => '✓', 'image' => '', 'title' => $card, 'text' => $campaign->feature_2];
        }
        if ($campaign->heading_3) {
            $out[] = ['icon' => '', 'image' => '', 'title' => $campaign->heading_3, 'text' => ''];
        }
        if ($campaign->heading_4) {
            $out[] = ['icon' => '', 'image' => '', 'title' => $campaign->heading_4, 'text' => ''];
        }
        if ($campaign->image_three) {
            $out[] = ['icon' => '', 'image' => $campaign->image_three, 'title' => '', 'text' => ''];
        }
        return $out;
    }

    
    public function update(Request $request)
    { 
         $this->validate($request, [
            'name' => 'required',
            'short_description' => 'nullable',
            'description' => 'nullable',
            'banner' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'image_one' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'image_two' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'image_three' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'product_id' => 'required|array|min:1|exists:products,id',
            'image.*' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'review' => 'required',
            'deadline' => 'nullable|date',
            'top_title_1' => 'nullable|string|max:255',
            'top_title_2' => 'nullable|string|max:255',
            'heading_1' => 'nullable|string|max:255',
            'feature_1' => 'nullable|string|max:255',
            'feature_2' => 'nullable|string|max:255',
            'heading_2' => 'nullable|string|max:255',
            'heading_3' => 'nullable|string|max:255',
            'heading_4' => 'nullable|string|max:255',
            'note' => 'nullable|string|max:255',
            'billing_details' => 'nullable|string|max:255',
        ]);
        // image one
        $update_data = Campaign::find($request->hidden_id);
        $input = $request->except('hidden_id','product_ids','files','image');
        $input['status'] = $request->has('status') ? 1 : 0;
        $input['video'] = $this->getYouTubeVideoId($request->video);
        $input['product_id'] = $request->product_id[0];
        $input['sections'] = $this->buildSections($request);
        $input['labels']   = $this->buildLabels($request);
        $input['features'] = $this->buildFeatures($request);
        $input['problem']  = $this->buildProblem($request);
        $input['solution'] = $this->buildSolution($request);
        $input['benefits'] = $this->buildBenefits($request);
        $input['trust']    = $this->buildTrust($request);
        $input['faq']      = $this->buildFaq($request);
        $input['cta']      = $this->buildCta($request);
        $input['reviews']  = $this->buildReviews($request);
        
          // Handle Banner Image
        if ($request->hasFile('banner')) {
            $banner = $request->file('banner');
            $bannerName = time() . '-' . $banner->getClientOriginalName();
            $bannerName = strtolower(preg_replace('/\s+/', '-', $bannerName));
            $uploadPath = 'public/uploads/campaign/';
            $bannerUrl = $uploadPath . $bannerName;
            $banner->move($uploadPath, $bannerName);
            $input['banner'] = $bannerUrl;
            File::delete($update_data->banner);
        } else {
            $input['banner'] = $update_data->banner;
        }
        $image_one = $request->file('image_one');
        if($image_one){
            // image with intervention 
            $image_one = $request->file('image_one');
            $name1 =  time().'-'.$image_one->getClientOriginalName();
            $name1 = preg_replace('"\.(jpg|jpeg|png|webp)$"', '.webp', $name1);
            $name1 = strtolower(preg_replace('/\s+/', '-', $name1));
            $uploadpath1 = 'public/uploads/campaign/';
            $imageUrl1 = $uploadpath1.$name1; 
            $img1 = Image::make($image_one->getRealPath());
            $img1->encode('webp', 90);
            $width1 = '';
            $height1 = '';
            $img1->height() > $img1->width() ? $width1=null : $height1=null;
            $img1->resize($width1, $height1, function ($constraint) {
                $constraint->aspectRatio();
            });
            $img1->save($imageUrl1);
            $input['image_one'] = $imageUrl1;
            File::delete($update_data->image_one);
        }else{
            $input['image_one'] = $update_data->image_one;
        }
        // image two
        $image_two = $request->file('image_two');
        if($image_two){
            // image with intervention 
            $image_two = $request->file('image_two');
            $name2 =  time().'-'.$image_two->getClientOriginalName();
            $name2 = preg_replace('"\.(jpg|jpeg|png|webp)$"', '.webp',$name2);
            $name2 = strtolower(preg_replace('/\s+/', '-', $name2));
            $uploadpath2 = 'public/uploads/campaign/';
            $imageUrl2 = $uploadpath2.$name2; 
            $img2=Image::make($image_two->getRealPath());
            $img2->encode('webp', 90);
            $width2 = '';
            $height2 = '';
            $img2->height() > $img2->width() ? $width2=null : $height2=null;
            $img2->resize($width2, $height2, function ($constraint) {
                $constraint->aspectRatio();
            });
            $img2->save($imageUrl2);
            $input['image_two'] = $imageUrl2;
            File::delete($update_data->image_two);
        }else{
            $input['image_two'] = $update_data->image_two;
        }
        // image three
        $image_three = $request->file('image_three');
        if($image_three){
            // image with intervention 
            $image_three = $request->file('image_three');
            $name3 =  time().'-'.$image_three->getClientOriginalName();
            $name3 = preg_replace('"\.(jpg|jpeg|png|webp)$"', '.webp',$name3);
            $name3 = strtolower(preg_replace('/\s+/', '-', $name3));
            $uploadpath3 = 'public/uploads/campaign/';
            $imageUrl3 = $uploadpath3.$name3; 
            $img3 = Image::make($image_three->getRealPath());
            $img3->encode('webp', 90);
            $width3 = '';
            $height3 = '';
            $img3->height() > $img3->width() ? $width3=null : $height3=null;
            $img3->resize($width3, $height3, function ($constraint) {
                $constraint->aspectRatio();
            });
            $img3->save($imageUrl3);
            $input['image_three'] = $imageUrl3;
            File::delete($update_data->image_three);
        }else{
            $input['image_three'] = $update_data->image_three;
        }
        // image four
        // Keep a stable slug: only regenerate when the name actually changed.
        $input['slug'] = (trim((string) $update_data->name) === trim((string) $request->name))
            ? ($update_data->slug ?: strtolower(Str::slug($request->name)))
            : strtolower(Str::slug($request->name));
        $input['video'] = $this->getYouTubeVideoId($request->video);
        $update_data = Campaign::find($request->hidden_id);
        $update_data->update($input);
        
        // Sync remaining selected products to the pivot table
        $remainingProductIds = array_slice($request->product_id, 1);
        $update_data->products()->sync($remainingProductIds);

        $images = $request->file('image');  
        if($images){
            foreach ($images as $key => $image) {
                $name =  time().'-'.$image->getClientOriginalName();
                $name = strtolower(preg_replace('/\s+/', '-', $name));
                $uploadPath = 'public/uploads/campaign/';
                $image->move($uploadPath,$name);
                $imageUrl =$uploadPath.$name;

                $pimage             = new CampaignReview();
                $pimage->campaign_id = $update_data->id;
                $pimage->image      = $imageUrl;
                $pimage->save();
            }
        }

        Toastr::success('Success','Data update successfully');
        return redirect()->route('campaign.index');
    }
 
    public function inactive(Request $request)
    {
        $inactive = Campaign::find($request->hidden_id);
        $inactive->status = 0;
        $inactive->save();
        Toastr::success('Success','Data inactive successfully');
        return redirect()->back();
    }
    public function active(Request $request)
    {
        $active = Campaign::find($request->hidden_id);
        $active->status = 1;
        $active->save();
        Toastr::success('Success','Data active successfully');
        return redirect()->back();
    }
    public function destroy(Request $request)
    {
       
        $delete_data = Campaign::find($request->hidden_id);
        $delete_data->delete();
        
        $campaign = Product::whereNotNull('campaign_id')->get();
        foreach($campaign as $key=>$value){
            $product = Product::find($value->id);
            $product->campaign_id = null;
            $product->save();
        }
        Toastr::success('Success','Data delete successfully');
        return redirect()->back();
    }
    public function imgdestroy(Request $request)
    { 
        $delete_data = CampaignReview::find($request->id);
        File::delete($delete_data->image);
        $delete_data->delete();
        Toastr::success('Success','Data delete successfully');
        return redirect()->back();
    } 
    /**
     * Build the per-section config from the submitted form.
     * Each section: label, position, visible, title, text, feature.
     */
    protected function buildSections(Request $request): array
    {
        $in = $request->input('sections', []);
        $out = [];
        foreach (array_keys(\App\Models\Campaign::SECTIONS) as $key) {
            $s = is_array($in[$key] ?? null) ? $in[$key] : [];
            $out[$key] = [
                'label'    => trim((string) ($s['label'] ?? \App\Models\Campaign::SECTION_DEFAULTS[$key]['label'] ?? $key)),
                'position' => (int) ($s['position'] ?? \App\Models\Campaign::SECTION_DEFAULTS[$key]['position'] ?? 1),
                'visible'  => isset($s['visible']) ? (bool) $s['visible'] : false,
                'title'    => (string) ($s['title'] ?? ''),
                'text'     => (string) ($s['text'] ?? ''),
                'feature'  => (string) ($s['feature'] ?? ''),
            ];
        }
        return $out;
    }

    /**
     * Build the dynamic heading/label map from the submitted form.
     * Only keys present in the form are stored; missing keys => '' (hidden).
     */
    protected function buildLabels(Request $request): array
    {
        $in = $request->input('labels', []);
        $out = [];
        foreach (array_keys(\App\Models\Campaign::LABELS) as $key) {
            $out[$key] = trim((string) ($in[$key] ?? ''));
        }
        return $out;
    }

    /**
     * Build the Features grid from the submitted loop rows.
     * Each row: icon, image (optional upload), title, text.
     */
    protected function buildFeatures(Request $request): array
    {
        $rows = $request->input('features', []);
        $files = $request->file('features', []);
        $out = [];

        if (!is_array($rows)) {
            return $out;
        }

        foreach ($rows as $i => $row) {
            if (!is_array($row)) {
                continue;
            }

            // Handle optional image upload for this feature row
            $imagePath = trim((string) ($row['image_old'] ?? ''));
            $upload = $files[$i]['image'] ?? null;
            if ($upload && $upload->isValid()) {
                $name = time() . '-' . strtolower(preg_replace('/\s+/', '-', $upload->getClientOriginalName()));
                $uploadPath = 'public/uploads/campaign/';
                $upload->move($uploadPath, $name);
                $imagePath = $uploadPath . $name;
            }

            $icon  = trim((string) ($row['icon'] ?? ''));
            $title = trim((string) ($row['title'] ?? ''));
            $text  = trim((string) ($row['text'] ?? ''));

            if ($icon === '' && $imagePath === '' && $title === '' && $text === '') {
                continue; // skip empty rows
            }

            $out[] = [
                'icon'  => $icon,
                'image' => $imagePath,
                'title' => $title,
                'text'  => $text,
            ];
        }

        return $out;
    }

    /**
     * Build a generic loop of text rows (problem/solution/benefits/trust/faq/cta).
     * Each row is `key[i][field]`; rows with all-empty fields are dropped.
     */
    protected function buildLoop(Request $request, string $key, array $fields): array
    {
        $rows = $request->input($key, []);
        $out = [];
        if (!is_array($rows)) {
            return $out;
        }
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $normalized = [];
            $hasValue = false;
            foreach ($fields as $f) {
                $v = trim((string) ($row[$f] ?? ''));
                $normalized[$f] = $v;
                if ($v !== '') { $hasValue = true; }
            }
            if ($hasValue) {
                $out[] = $normalized;
            }
        }
        return $out;
    }

    /**
     * Build the Problem (pain cards) loop. Fields: num, title, text.
     */
    protected function buildProblem(Request $request): array
    {
        return $this->buildLoop($request, 'problem', ['num', 'title', 'text']);
    }

    /**
     * Build the Solution benefits loop. Fields: icon, title, text.
     */
    protected function buildSolution(Request $request): array
    {
        return $this->buildLoop($request, 'solution', ['icon', 'title', 'text']);
    }

    /**
     * Build the Benefits loop. Fields: icon, title, text.
     */
    protected function buildBenefits(Request $request): array
    {
        return $this->buildLoop($request, 'benefits', ['icon', 'title', 'text']);
    }

    /**
     * Build the Trust badges loop. Fields: icon, text.
     */
    protected function buildTrust(Request $request): array
    {
        return $this->buildLoop($request, 'trust', ['icon', 'text']);
    }

    /**
     * Build the FAQ loop. Fields: q, a.
     */
    protected function buildFaq(Request $request): array
    {
        return $this->buildLoop($request, 'faq', ['q', 'a']);
    }

    /**
     * Build the CTA loop. Fields: icon, title, text.
     */
    protected function buildCta(Request $request): array
    {
        return $this->buildLoop($request, 'cta', ['icon', 'title', 'text']);
    }

    /**
     * Build the Customer Reviews loop. Fields: name, text, rating.
     */
    protected function buildReviews(Request $request): array
    {
        return $this->buildLoop($request, 'reviews', ['name', 'text', 'rating']);
    }

    public function getYouTubeVideoId($input)
    {
        // Check if the input is a valid YouTube video ID (11 characters long)
        if (preg_match('/^[a-zA-Z0-9_-]{11}$/', $input)) {
            return $input; // Return the ID directly if it's valid
        }
    
        // Regular expression to match YouTube video URLs
        $pattern = '/(?:https?:\/\/)?(?:www\.)?(?:youtube\.com\/(?:[^\/\n\s]+\/\S+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([a-zA-Z0-9_-]{11})/';
        
        // Execute the regex pattern
        preg_match($pattern, $input, $matches);
        
        // Check if a match was found and return the video ID or null
        return isset($matches[1]) ? $matches[1] : null;
    }

}
