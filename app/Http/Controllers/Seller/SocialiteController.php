<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Seller;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class SocialiteController extends Controller
{
    public function googleLogin()
    {
        // Set session variable to indicate this is a seller login
        Session::put('google_auth_type', 'seller');
        return Socialite::driver('google')->redirect();
    }

    public function googleAuthentication()
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();
            
            // First check if seller exists with this google_id
            $seller = Seller::where('google_id', $googleUser->getId())->first();
            
            if ($seller) {
                // Update seller's email if it has changed in Google
                if ($seller->email !== $googleUser->getEmail()) {
                    // Check if the new email is already used by another seller
                    $existingSeller = Seller::where('email', $googleUser->getEmail())
                        ->where('id', '!=', $seller->id)
                        ->first();
                    
                    if ($existingSeller) {
                        return redirect()->route('seller.login')->with('error', 
                            'This Google account email is already associated with another seller account. ' .
                            'Please use your original email to login or contact support.');
                    }
                    
                    $seller->email = $googleUser->getEmail();
                    $seller->recipient_mail = $googleUser->getEmail();
                    $seller->save();
                }

                Auth::guard('seller')->login($seller);
                return redirect()->route('seller.dashboard')->with('success', 'Successfully logged in with Google.');
            }

            // Check if seller exists with this email
            $seller = Seller::where('email', $googleUser->getEmail())->first();
            
            if ($seller) {
                // Seller exists with this email but not linked to Google
                //if ($seller->status == 0) {
                //    return redirect()->route('seller.login')->with('error', 'Your account is inactive. Please contact support.');
                //}

                // Link the Google account
                $seller->google_id = $googleUser->getId();
                $seller->save();

                Auth::guard('seller')->login($seller);
                return redirect()->route('seller.dashboard')->with('success', 'Your account has been linked with Google successfully.');
            }

            // Create new seller if no existing seller found
            $sellerData = Seller::create([
                'email' => $googleUser->getEmail(),
                'username' => $googleUser->getName(),
                'photo' => $googleUser->getAvatar(),
                'google_id' => $googleUser->getId(),
                'status' => 0,
                'password' => bcrypt(Str::random(16)),
                'email_verified_at' => now(),
                'show_email_addresss' => 1,
                'show_phone_number' => 1,
                'show_contact_form' => 1,
                'recipient_mail' => $googleUser->getEmail(),
            ]);

            $path = public_path('assets/admin/img/seller-photo/');
            if (!file_exists($path)) {
                mkdir($path, 0755, true);
            }

            if($sellerData){
                $avatarUrl = $googleUser->getAvatar();
                if($avatarUrl){
                    $fileContents = file_get_contents($avatarUrl);
                    $avatarName = $googleUser->getId() . '.jpg';
                    file_put_contents($path . $avatarName, $fileContents);
                    $sellerData->photo = $avatarName;
                    $sellerData->save();
                }
                Auth::guard('seller')->login($sellerData);
                return redirect()->route('seller.dashboard')->with('success', 'Successfully registered and logged in with Google.');
            }

            return redirect()->route('seller.login')->with('error', 'Google authentication failed. Please try again.');

        } catch (Exception $e) {
            return redirect()->route('seller.login')->with('error', 'Google authentication failed: ' . $e->getMessage());
        }
    }
}
