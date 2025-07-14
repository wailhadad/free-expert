<?php

namespace App\Http\Controllers\BackEnd;

use App\Http\Controllers\Controller;
use App\Models\BasicSettings\Basic;
use App\Models\Language;
use App\Models\User;
use App\Models\UserMembership;
use App\Models\UserPackage;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Barryvdh\DomPDF\Facade\Pdf;

class UserMembershipController extends Controller
{
    public function index(Request $request)
    {
        if (session()->has('lang')) {
            $currentLang = Language::where('code', session()->get('lang'))->first();
        } else {
            $currentLang = Language::where('is_default', 1)->first();
        }

        $search = $request->search;
        $status = $request->status;
        
        $data['bex'] = $currentLang->basic_extended;
        $data['memberships'] = UserMembership::with(['user', 'package'])
            ->when($search, function ($query, $search) {
                return $query->whereHas('user', function ($q) use ($search) {
                    $q->where('username', 'like', '%' . $search . '%')
                      ->orWhere('email_address', 'like', '%' . $search . '%');
                });
            })
            ->when($status !== null, function ($query) use ($status) {
                return $query->where('status', $status);
            })
            ->orderBy('created_at', 'DESC')
            ->paginate(10);

        return view('backend.user-memberships.index', $data);
    }

    public function approve($id)
    {
        $membership = UserMembership::findOrFail($id);
        
        if ($membership->status != '0') {
            Session::flash('error', 'Membership is not pending for approval');
            return back();
        }

        $bs = Basic::first();
        $package = UserPackage::find($membership->package_id);

        // Calculate start and expire dates
        $startDate = Carbon::now();
        $expireDate = null;

        if ($package->term == 'monthly') {
            $expireDate = $startDate->copy()->addMonth();
        } elseif ($package->term == 'yearly') {
            $expireDate = $startDate->copy()->addYear();
        } elseif ($package->term == 'lifetime') {
            $expireDate = Carbon::maxValue();
        }

        $membership->update([
            'status' => '1',
            'start_date' => $startDate->format('Y-m-d'),
            'expire_date' => $expireDate->format('Y-m-d'),
        ]);

        // Detect if this is an extension (user already has an active membership for this package)
        $isExtension = UserMembership::where('user_id', $membership->user_id)
            ->where('package_id', $membership->package_id)
            ->where('status', '1')
            ->where('id', '!=', $membership->id)
            ->exists();
        $mailTemplate = $isExtension ? 'customer_membership_extend' : 'customer_membership_invoice';
        $mailSubject = $isExtension
            ? __('Your Package Extension Invoice from ') . $bs->website_title
            : __('Your Package Purchase Invoice from ') . $bs->website_title;

        // Generate PDF invoice
        $invoiceName = 'user-membership-' . $membership->id . '-' . ($isExtension ? 'extend-' : '') . time() . '.pdf';
        $directory = public_path('assets/file/invoices/user-memberships/');
        if (!file_exists($directory)) {
            mkdir($directory, 0775, true);
        }
        $fileLocation = $directory . $invoiceName;
        $data = [
            'membership' => $membership,
            'user' => $membership->user,
            'package' => $package,
            'bs' => $bs,
        ];
        
        try {
            Pdf::loadView('frontend.user.packages.invoice', $data)->save($fileLocation);
            $membership->invoice = $invoiceName;
            $membership->save();

            \Log::info('UserMembership: Invoice generated', [
                'invoice_name' => $invoiceName,
                'file_location' => $fileLocation,
                'file_exists' => file_exists($fileLocation)
            ]);
        } catch (\Exception $e) {
            \Log::error('UserMembership: PDF generation failed', [
                'error' => $e->getMessage(),
                'invoice_name' => $invoiceName,
                'file_location' => $fileLocation
            ]);
            Session::flash('error', 'Invoice generation failed: ' . $e->getMessage());
            return back();
        }

        // Send invoice email to user
        $mailer = new \App\Http\Helpers\MegaMailer();
        $mailData = [
            'toMail' => $membership->user->email ?: $membership->user->email_address,
            'username' => $membership->user->username,
            'package_title' => $package->title,
            'package_price' => $membership->currency_symbol . number_format($membership->price, 2),
            'activation_date' => $membership->start_date,
            'expire_date' => $membership->expire_date,
            'membership_invoice' => $invoiceName,
            'membership_invoice_path' => 'user-memberships',
            'website_title' => $bs->website_title,
            'templateType' => $mailTemplate,
            'mail_subject' => $mailSubject,
        ];

        \Log::info('UserMembership: Sending email with data', [
            'mail_data' => $mailData,
            'invoice_path' => public_path('assets/file/invoices/user-memberships/' . $invoiceName)
        ]);

        try {
            $mailer->mailFromAdmin($mailData);
            \Log::info('UserMembership: Email sent successfully');
            
            // Check if file still exists after email
            $finalPath = public_path('assets/file/invoices/user-memberships/' . $invoiceName);
            \Log::info('UserMembership: File check after email', [
                'file_exists_after_email' => file_exists($finalPath),
                'file_path' => $finalPath
            ]);
        } catch (\Exception $e) {
            \Log::error('UserMembership: Email sending failed', [
                'error' => $e->getMessage(),
                'mail_data' => $mailData
            ]);
            Session::flash('error', 'Email sending failed: ' . $e->getMessage());
            return back();
        }

        // Send real-time notification to user about membership approval
        $notificationService = new \App\Services\NotificationService();
        $notificationService->sendRealTime($membership->user, [
            'type' => 'user_package_approved',
            'title' => 'Your Package Payment Approved',
            'message' => 'Your payment for the package "' . $package->title . '" has been approved by admin.',
            'url' => route('user.packages.subscription_log'),
            'icon' => 'fas fa-check-circle',
            'extra' => [
                'membership_id' => $membership->id,
                'package_id' => $package->id,
                'package_title' => $package->title,
                'price' => $membership->price,
                'start_date' => $membership->start_date,
                'expire_date' => $membership->expire_date
            ]
        ]);

        Session::flash('success', 'User membership approved successfully!');
        return back();
    }

    public function reject($id)
    {
        $membership = UserMembership::findOrFail($id);
        
        if ($membership->status != '0') {
            Session::flash('error', 'Membership is not pending for approval');
            return back();
        }

        $membership->update(['status' => '2']);

        Session::flash('success', 'User membership rejected successfully!');
        return back();
    }

    public function details($id)
    {
        $membership = UserMembership::with(['user', 'package'])->findOrFail($id);
        $data['membership'] = $membership;
        $data['user'] = $membership->user;
        $data['package'] = $membership->package;
        
        return view('backend.user-memberships.details', $data);
    }

    public function delete($id)
    {
        $membership = UserMembership::findOrFail($id);
        
        if ($membership->status == '1') {
            Session::flash('error', 'Cannot delete active membership');
            return back();
        }

        $membership->delete();
        Session::flash('success', 'User membership deleted successfully!');
        return back();
    }

    public function updateStatus(Request $request, $id)
    {
        $membership = UserMembership::with(['user', 'package'])->findOrFail($id);
        $oldStatus = $membership->status;
        $newStatus = $request->input('status');
        $bs = Basic::first();
        $package = $membership->package;
        $user = $membership->user;

        // Only act if status actually changes
        if ($oldStatus != $newStatus) {
            if ($newStatus == '2') { // Rejected
                $membership->status = '2';
                $membership->save();
                // Send rejection email
                $mailer = new \App\Http\Helpers\MegaMailer();
                $mailData = [
                    'toMail' => $user->email ?: $user->email_address,
                    'username' => $user->username,
                    'package_title' => $package->title,
                    'website_title' => $bs->website_title,
                    'templateType' => 'customer_membership_reject',
                    'mail_subject' => __('Your Package Purchase was Rejected by ') . $bs->website_title,
                ];
                $mailer->mailFromAdmin($mailData);
                
                // Send real-time notification to user about membership rejection
                $notificationService = new \App\Services\NotificationService();
                $notificationService->sendRealTime($user, [
                    'type' => 'user_package_rejected',
                    'title' => 'Your Package Payment Rejected',
                    'message' => 'Your payment for the package "' . $package->title . '" has been rejected by admin.',
                    'url' => route('user.packages.subscription_log'),
                    'icon' => 'fas fa-times-circle',
                    'extra' => [
                        'membership_id' => $membership->id,
                        'package_id' => $package->id,
                        'package_title' => $package->title,
                        'price' => $membership->price
                    ]
                ]);
                
                Session::flash('success', 'Membership rejected, user notified.');
            } elseif ($newStatus == '1') { // Accepted
                // Set start/expire dates if not set
                $startDate = Carbon::now();
                $expireDate = null;
                if ($package->term == 'monthly') {
                    $expireDate = $startDate->copy()->addMonth();
                } elseif ($package->term == 'yearly') {
                    $expireDate = $startDate->copy()->addYear();
                } elseif ($package->term == 'lifetime') {
                    $expireDate = Carbon::maxValue();
                }
                $membership->status = '1';
                $membership->start_date = $startDate->format('Y-m-d');
                $membership->expire_date = $expireDate->format('Y-m-d');
                // Generate invoice if not present
                if (!$membership->invoice) {
                    $invoiceName = 'user-membership-' . $membership->id . '-' . time() . '.pdf';
                    $directory = public_path('assets/file/invoices/user-memberships/');
                    if (!file_exists($directory)) {
                        mkdir($directory, 0775, true);
                    }
                    $fileLocation = $directory . $invoiceName;
                    $data = [
                        'membership' => $membership,
                        'user' => $user,
                        'package' => $package,
                        'bs' => $bs,
                    ];
                    
                    try {
                        Pdf::loadView('frontend.user.packages.invoice', $data)->save($fileLocation);
                        $membership->invoice = $invoiceName;
                        
                        \Log::info('UserMembership updateStatus: Invoice generated', [
                            'invoice_name' => $invoiceName,
                            'file_location' => $fileLocation,
                            'file_exists' => file_exists($fileLocation)
                        ]);
                    } catch (\Exception $e) {
                        \Log::error('UserMembership updateStatus: PDF generation failed', [
                            'error' => $e->getMessage(),
                            'invoice_name' => $invoiceName,
                            'file_location' => $fileLocation
                        ]);
                        Session::flash('error', 'Invoice generation failed: ' . $e->getMessage());
                        return back();
                    }
                }
                $membership->save();
                // Send validation email with invoice
                $mailer = new \App\Http\Helpers\MegaMailer();
                $mailData = [
                    'toMail' => $user->email ?: $user->email_address,
                    'username' => $user->username,
                    'package_title' => $package->title,
                    'package_price' => $membership->currency_symbol . number_format($membership->price, 2),
                    'activation_date' => $membership->start_date,
                    'expire_date' => $membership->expire_date,
                    'membership_invoice' => $membership->invoice,
                    'membership_invoice_path' => 'user-memberships',
                    'website_title' => $bs->website_title,
                    'templateType' => 'customer_membership_invoice',
                    'mail_subject' => __('Your Package Purchase Invoice from ') . $bs->website_title,
                ];

                \Log::info('UserMembership updateStatus: Sending email with data', [
                    'mail_data' => $mailData,
                    'invoice_path' => public_path('assets/file/invoices/user-memberships/' . $membership->invoice)
                ]);

                try {
                    $mailer->mailFromAdmin($mailData);
                    \Log::info('UserMembership updateStatus: Email sent successfully');
                    
                    // Check if file still exists after email
                    $finalPath = public_path('assets/file/invoices/user-memberships/' . $membership->invoice);
                    \Log::info('UserMembership updateStatus: File check after email', [
                        'file_exists_after_email' => file_exists($finalPath),
                        'file_path' => $finalPath
                    ]);
                } catch (\Exception $e) {
                    \Log::error('UserMembership updateStatus: Email sending failed', [
                        'error' => $e->getMessage(),
                        'mail_data' => $mailData
                    ]);
                    Session::flash('error', 'Email sending failed: ' . $e->getMessage());
                    return back();
                }
                
                // Send real-time notification to user about membership approval
                $notificationService = new \App\Services\NotificationService();
                $notificationService->sendRealTime($user, [
                    'type' => 'user_package_approved',
                    'title' => 'Your Package Payment Approved',
                    'message' => 'Your payment for the package "' . $package->title . '" has been approved by admin.',
                    'url' => route('user.packages.subscription_log'),
                    'icon' => 'fas fa-check-circle',
                    'extra' => [
                        'membership_id' => $membership->id,
                        'package_id' => $package->id,
                        'package_title' => $package->title,
                        'price' => $membership->price,
                        'start_date' => $membership->start_date,
                        'expire_date' => $membership->expire_date
                    ]
                ]);
                
                Session::flash('success', 'Membership accepted, user notified and invoice sent.');
            } else { // Pending
                $membership->status = '0';
                $membership->save();
                Session::flash('success', 'Membership set to pending.');
            }
        } else {
            Session::flash('info', 'No status change detected.');
        }
        return back();
    }
} 