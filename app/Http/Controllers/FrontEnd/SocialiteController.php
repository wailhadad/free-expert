<?php

namespace App\Http\Controllers\FrontEnd;

use App\Http\Controllers\Controller;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class SocialiteController extends Controller
{
    public function googleLogin()
    {
        return Socialite::driver('google')->redirect();
    }

    public function googleAuthentication()
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();
            
            // First check if user exists with this google_id
            $user = User::where('google_id', $googleUser->getId())->first();
            
            if ($user) {
                // Update user's email if it has changed in Google
                if ($user->email_address !== $googleUser->getEmail()) {
                    // Check if the new email is already used by another account
                    $existingUser = User::where('email_address', $googleUser->getEmail())
                        ->where('id', '!=', $user->id)
                        ->first();
                    
                    if ($existingUser) {
                        return redirect()->route('user.login')->with('error', 
                            'This Google account email is already associated with another account. ' .
                            'Please use your original email to login or contact support.');
                    }
                    
                    $user->email_address = $googleUser->getEmail();
                    $user->save();
                }

                Auth::login($user);
                return redirect()->route('user.dashboard')->with('success', 'Successfully logged in with Google.');
            }

            // Check if user exists with this email
            $user = User::where('email_address', $googleUser->getEmail())->first();
            
            if ($user) {
                // Link the Google account
                $user->google_id = $googleUser->getId();
                $user->save();

                Auth::login($user);
                return redirect()->route('user.dashboard')->with('success', 'Your account has been linked with Google successfully.');
            }

            // Create new user if no existing user found
            $userData = User::create([
                'email_address' => $googleUser->getEmail(),
                'first_name' => $googleUser->getName(),
                'username' => $googleUser->getName(),
                'image' => $googleUser->getAvatar(),
                'google_id' => $googleUser->getId(),
                'status' => 1,
                'password' => bcrypt(Str::random(16)),
                'email_verified_at' => now(),
            ]);

            $path = public_path('assets/img/users/');
            if(!$path){
                mkdir($path, 0755, true);
            }

            if($userData){
                $avatarUrl = $googleUser->getAvatar();
                if($avatarUrl){
                    $fileContents = file_get_contents($avatarUrl);
                    $avatarName = $googleUser->getId() . '.jpg';
                    file_put_contents($path . $avatarName, $fileContents);
                    $userData->image = $avatarName;
                    $userData->save();
                }
                Auth::login($userData);
                return redirect()->route('user.dashboard')->with('success', 'Successfully registered and logged in with Google.');
            }

            return redirect()->route('user.login')->with('error', 'Google authentication failed. Please try again.');

        } catch (Exception $e) {
            return redirect()->route('user.login')->with('error', 'Google authentication failed: ' . $e->getMessage());
        }
    }
}
