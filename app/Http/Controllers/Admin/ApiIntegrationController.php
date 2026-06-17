<?php

namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PaymentGateway;
use App\Models\SmsGateway;
use App\Models\Courierapi;
use Toastr;
use File;
use Str;
use Image;
use DB;
use Illuminate\Support\Facades\Schema;

class ApiIntegrationController extends Controller
{
    
     
    public function pay_manage ()
    {
        $bkash = PaymentGateway::where('type','=','bkash')->first();
        $shurjopay = PaymentGateway::where('type','=','shurjopay')->first();
        $uddoktapay = PaymentGateway::where('type', 'uddoktapay')->first();
        $aamarpay = PaymentGateway::where('type', 'aamarpay')->first();
        return view('backEnd.apiintegration.pay_manage', compact('bkash', 'shurjopay', 'uddoktapay', 'aamarpay'));

    }
    
   public function pay_update(Request $request)
{
    $update_data = \App\Models\PaymentGateway::find($request->id);
    $input = $request->all();
    $input['status'] = $request->status ? 1 : 0;
    $update_data->update($input);

    // ✅ যদি গেটওয়ে টাইপ হয় UddoktaPay
    if ($update_data->type === 'uddoktapay') {
        $this->updateEnvFile('UDDOKTAPAY_API_KEY', $request->app_key);
        $this->updateEnvFile('UDDOKTAPAY_API_URL', $request->base_url);
    }

    \Toastr::success('Success', ucfirst($update_data->type) . ' settings updated successfully');
    return redirect()->back();
}

/**
 * 🔧 Helper function: Update or add key in .env file
 */
private function updateEnvFile($key, $value)
{
    $path = base_path('.env');

    if (file_exists($path)) {
        $oldValue = env($key);

        if (strpos(file_get_contents($path), $key) !== false) {
            // Replace old value
            file_put_contents($path, str_replace(
                $key . '=' . $oldValue,
                $key . '=' . $value,
                file_get_contents($path)
            ));
        } else {
            // Add new line if not exists
            file_put_contents($path, PHP_EOL . $key . '=' . $value, FILE_APPEND);
        }
    }
}

    
    public function sms_manage ()
    {  
        $sms = SmsGateway::first();
        return view('backEnd.apiintegration.sms_manage',compact('sms'));
    }
    
public function sms_update(Request $request)
{
    $update_data = SmsGateway::find($request->id);
    $input = $request->all();
    $input['status'] = $request->status?1:0;
    $input['order'] = $request->order?1:0;
    $input['forget_pass'] = $request->forget_pass?1:0;
    $input['password_g'] = $request->password_g?1:0;

    // DB Update
    $update_data->update($input);

    // ============================
    //  🔥 HERE: Save to .env file
    // ============================
    if ($request->filled('admin_phone_list')) {
        $this->updateEnvFile('ADMIN_PHONE_LIST', $request->admin_phone_list);
    }

    Toastr::success('Success','Data update successfully');
    return redirect()->back();
}

    
    public function courier_manage ()
    {
        $steadfast = Courierapi::where('type','=','steadfast')->first();
        $pathao = Courierapi::where('type','=','pathao')->first();
        $redx = Courierapi::where('type','=','redx')->first();
        
        // Create RedX entry if not exists
        if (!$redx) {
            $redx = Courierapi::create([
                'type' => 'redx',
                'url' => 'sandbox.redx.com.bd/v1.0.0-beta',
                'status' => 0,
            ]);
        }
        
        return view('backEnd.apiintegration.courier_manage',compact('steadfast','pathao','redx'));
    }
    
    public function courier_update (Request $request)
    {
      
        $update_data = Courierapi::find($request->id);
        $input = $request->all();
        $input['status'] = $request->status?1:0;
        
        // Only include webhook_url if column exists
        if (!Schema::hasColumn('courierapis', 'webhook_url')) {
            unset($input['webhook_url']);
        }
        
        // Pathao এর জন্য token auto-generate
        if($update_data->type == 'pathao' && !empty($input['client_id']) && !empty($input['client_secret'])){
            try {
                // Clean up URL
                $apiUrl = $input['url'] ?? 'https://api-hermes.pathao.com';
                $apiUrl = rtrim($apiUrl, '/');
                $apiUrl = preg_replace('#/aladdin/?$#', '', $apiUrl);
                
                // Get username and password
                $username = $input['username'] ?? null;
                $password = $input['password'] ?? null;
                
                $tokenResponse = $this->generatePathaoToken(
                    $input['client_id'], 
                    $input['client_secret'], 
                    $apiUrl,
                    $username,
                    $password
                );
                if($tokenResponse && isset($tokenResponse['access_token'])){
                    $input['token'] = $tokenResponse['access_token'];
                }
            } catch (\Exception $e) {
                // Token generate fail হলে error message
                Toastr::warning('Token generation failed: ' . $e->getMessage());
            }
        }
        
        // RedX এর জন্য URL format ঠিক করা (https:// যোগ করা)
        if($update_data->type == 'redx'){
            // Base URL format ঠিক করা
            if(!empty($input['url'])){
                $url = trim($input['url']);
                // Remove existing https:// if present to avoid duplication
                $url = preg_replace('/^https?:\/\//', '', $url);
                $url = rtrim($url, '/');
                // Add https:// prefix
                $input['url'] = 'https://' . $url;
                
                \Log::info('RedX URL Update', [
                    'original' => $input['url'] ?? 'not set',
                    'normalized' => $url,
                    'final' => $input['url']
                ]);
            }
            
            // Clean token - remove Bearer prefix if present, trim whitespace
            if(!empty($input['token'])){
                $token = trim($input['token']);
                $token = preg_replace('/^Bearer\s+/i', '', $token); // Remove Bearer prefix if exists
                $input['token'] = $token;
            }
            
            // Webhook URL format ঠিক করা (optional field)
            if(isset($input['webhook_url']) && !empty(trim($input['webhook_url']))){
                $webhookUrl = trim($input['webhook_url']);
                // URL validation - http:// বা https:// থাকতে হবে
                if (!preg_match('/^https?:\/\//', $webhookUrl)) {
                    // যদি http/https না থাকে, তাহলে config('app.url') থেকে base URL নিব
                    $baseUrl = rtrim(config('app.url'), '/');
                    $input['webhook_url'] = $baseUrl . '/' . ltrim($webhookUrl, '/');
                }
            } else {
                // Empty হলে null set করব
                $input['webhook_url'] = null;
            }
        }
        
        $update_data->update($input);
        
        Toastr::success('Success','Data update successfully');
        return redirect()->back();
    }
    
    /**
     * Generate Pathao Access Token
     * According to Pathao API Documentation: https://developer.pathao.com/
     * Uses OAuth 2.0 with grant_type: password
     */
    private function generatePathaoToken($clientId, $clientSecret, $baseUrl = 'https://api-hermes.pathao.com', $username = null, $password = null)
    {
        try {
            // Clean up URL - remove trailing slashes and /aladdin if present
            $baseUrl = rtrim($baseUrl, '/');
            $baseUrl = preg_replace('#/aladdin/?$#', '', $baseUrl);
            
            // Ensure we have the correct base URL
            if (!preg_match('#^https?://#', $baseUrl)) {
                $baseUrl = 'https://' . $baseUrl;
            }
            
            // Check if this is sandbox/test environment
            $isSandbox = (strpos($baseUrl, 'sandbox') !== false || strpos($baseUrl, 'courier-api-sandbox') !== false);
            
            // For sandbox, use test credentials if username/password not provided
            if ($isSandbox && empty($username)) {
                $username = 'test@pathao.com';
                $password = 'lovePathao';
            }
            
            // Validate required fields
            if (empty($username) || empty($password)) {
                throw new \Exception('Username and Password are required for Pathao token generation. For Sandbox: test@pathao.com / lovePathao');
            }
            
            \Log::info('Attempting Pathao token generation', [
                'base_url' => $baseUrl,
                'endpoint' => $baseUrl . '/aladdin/api/v1/issue-token',
                'has_client_id' => !empty($clientId),
                'has_client_secret' => !empty($clientSecret),
                'has_username' => !empty($username),
                'is_sandbox' => $isSandbox
            ]);
            
            // Pathao API requires JSON format with grant_type: password
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ])->post($baseUrl . '/aladdin/api/v1/issue-token', [
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'grant_type' => 'password',
                'username' => $username,
                'password' => $password
            ]);
            
            \Log::info('Pathao token API response', [
                'status' => $response->status(),
                'successful' => $response->successful(),
                'body' => substr($response->body(), 0, 500)
            ]);
            
            if ($response->successful()) {
                $data = $response->json();
                
                if (isset($data['access_token'])) {
                    \Log::info('Pathao token generated successfully', [
                        'token_type' => $data['token_type'] ?? 'N/A',
                        'expires_in' => $data['expires_in'] ?? 'N/A'
                    ]);
                    return $data;
                } else {
                    throw new \Exception('Access token not found in response: ' . json_encode($data));
                }
            } else {
                $errorBody = $response->json();
                $errorMessage = $errorBody['message'] ?? 'Token generation failed';
                
                \Log::error('Pathao token generation failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'error_message' => $errorMessage
                ]);
                
                throw new \Exception('Token generation failed: ' . $errorMessage);
            }
        } catch (\Exception $e) {
            \Log::error('Pathao token generation exception', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
    
    /**
     * Generate Pathao Token via AJAX
     */
    public function pathao_generate_token(Request $request)
    {
        try {
            \Log::info('Pathao token generation request received');
            
            $pathao = Courierapi::where('type', 'pathao')->first();
            
            if(!$pathao){
                \Log::error('Pathao configuration not found');
                return response()->json([
                    'status' => 'error',
                    'message' => 'Pathao configuration not found. Please configure Pathao first.'
                ], 400);
            }
            
            if(!$pathao->client_id || !$pathao->client_secret){
                \Log::error('Pathao Client ID or Secret missing', ['has_client_id' => !empty($pathao->client_id), 'has_secret' => !empty($pathao->client_secret)]);
                return response()->json([
                    'status' => 'error',
                    'message' => 'Client ID and Client Secret required. Please enter them in the form above.'
                ], 400);
            }
            
            // Clean up URL - remove trailing slashes and /aladdin if present
            $apiUrl = $pathao->url ?? 'https://api-hermes.pathao.com';
            $apiUrl = rtrim($apiUrl, '/');
            $apiUrl = preg_replace('#/aladdin/?$#', '', $apiUrl);
            
            // Get username and password
            $username = $pathao->username ?? null;
            $password = $pathao->password ?? null;
            
            \Log::info('Generating Pathao token', [
                'original_url' => $pathao->url, 
                'cleaned_url' => $apiUrl,
                'has_username' => !empty($username)
            ]);
            
            $tokenResponse = $this->generatePathaoToken(
                $pathao->client_id, 
                $pathao->client_secret, 
                $apiUrl,
                $username,
                $password
            );
            
            if($tokenResponse && isset($tokenResponse['access_token'])){
                $pathao->token = $tokenResponse['access_token'];
                
                // Calculate and save expiry time if expires_in is provided
                if(isset($tokenResponse['expires_in'])){
                    $expiresIn = (int) $tokenResponse['expires_in']; // seconds
                    $expiresAt = now()->addSeconds($expiresIn);
                    // Note: If you have token_expires_at column, uncomment below:
                    // $pathao->token_expires_at = $expiresAt;
                }
                
                $pathao->save();
                
                // Calculate expiry info for response
                $expiryInfo = '';
                if(isset($tokenResponse['expires_in'])){
                    $expiresIn = (int) $tokenResponse['expires_in'];
                    $days = floor($expiresIn / 86400);
                    $hours = floor(($expiresIn % 86400) / 3600);
                    $minutes = floor(($expiresIn % 3600) / 60);
                    
                    if($days > 0){
                        $expiryInfo = $days . ' দিন';
                    } elseif($hours > 0){
                        $expiryInfo = $hours . ' ঘন্টা';
                    } else {
                        $expiryInfo = $minutes . ' মিনিট';
                    }
                }
                
                \Log::info('Pathao token generated successfully', [
                    'expires_in' => $tokenResponse['expires_in'] ?? 'N/A',
                    'expiry_info' => $expiryInfo
                ]);
                
                return response()->json([
                    'status' => 'success',
                    'message' => 'Token generated successfully' . ($expiryInfo ? ' (Valid for ' . $expiryInfo . ')' : ''),
                    'token' => $tokenResponse['access_token'],
                    'expires_in' => $tokenResponse['expires_in'] ?? null,
                    'expiry_info' => $expiryInfo,
                    'expires_at' => isset($expiresAt) ? $expiresAt->format('Y-m-d H:i:s') : null
                ]);
            } else {
                \Log::error('Pathao token generation failed', ['response' => $tokenResponse]);
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to generate token. Please check your Client ID and Secret. Response: ' . json_encode($tokenResponse)
                ], 400);
            }
        } catch (\Exception $e) {
            $errorDetails = [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'request_url' => request()->fullUrl(),
                'request_method' => request()->method(),
                'request_data' => request()->all()
            ];
            
            \Log::error('Pathao token generation exception', $errorDetails);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Token generation failed: ' . $e->getMessage(),
                'error_details' => config('app.debug') ? $errorDetails : null
            ], 500);
        }
    }
    public function sms_custom_send_page()
{
    return view('backEnd.apiintegration.sms_custom_send');
}

public function sms_custom_send(Request $request)
{
    $request->validate([
        'phone' => 'required|string',
        'message' => 'required|string|max:500',
    ]);

    try {
        // ✅ তোমার গেটওয়ে ইনফো নিচ্ছি
        $sms_gateway = \App\Models\SmsGateway::where('status', 1)->first();
        if (!$sms_gateway) {
            Toastr::error('Failed', 'SMS Gateway not configured.');
            return back();
        }

        // ✅ ফোন নাম্বার পরিষ্কার
        $number = preg_replace('/[^0-9]/', '', $request->phone);
        $message = $request->message;

        // ✅ API Data প্রস্তুত করা
        $api_key = $sms_gateway->api_key;
        $senderid = $sms_gateway->senderid ?? $sms_gateway->serderid ?? '';
        $url = $sms_gateway->url;

        // ✅ Curl দিয়ে Send করা (official working method)
        $postData = [
            'api_key' => $api_key,
            'type' => 'text',
            'number' => $number,
            'senderid' => $senderid,
            'message' => $message,
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        // ✅ লগে রেসপন্স দেখা
        \Log::info("BulkSMSBD Manual Response: " . $response);

        // ✅ যদি কোনো Error থাকে
        if ($err) {
            Toastr::error('Error', 'cURL Error: ' . $err);
            return back();
        }

        // ✅ Response ডিকোড করে চেক করা
        if (stripos($response, 'SMS sent successfully') !== false || stripos($response, '202') !== false) {
            Toastr::success('Success', 'SMS sent successfully!');
        } else {
            Toastr::warning('Failed', 'API Response: ' . $response);
        }

        return back();

    } catch (\Exception $e) {
        \Log::error("Manual SMS Send Failed: " . $e->getMessage());
        Toastr::error('Failed', 'SMS sending failed: ' . $e->getMessage());
        return back();
    }
}


}