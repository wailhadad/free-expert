<?php

namespace App\Http\Controllers\BackEnd;

use App\Http\Controllers\Controller;
use App\Http\Helpers\BasicMailer;
use App\Models\BasicSettings\Basic;
use App\Models\BasicSettings\MailTemplate;
use App\Models\Seller;
use App\Models\Transaction;
use App\Models\Withdraw;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class WithdrawController extends Controller
{
    //index
    public function index()
    {
        $search = request()->input('search');

        $information['collection'] = Withdraw::with('method')
            ->when($search, function ($query, $keyword) {
                return $query->where('withdraws.withdraw_id', 'like', '%' . $keyword . '%');
            })
            ->orderBy('id', 'desc')->paginate(10);
        $information['currencyInfo'] = $this->getCurrencyInfo();
        return view('backend.withdraw.history.index', $information);
    }
    //delete
    public function delete(Request $request)
    {
        $delete = Withdraw::where('id', $request->id)->first();
        $delete->delete();
        return redirect()->back()->with('success', 'Withdraw Request Deleted Successfully!');
    }
    //approve
    public function approve($id)
    {
        $withdraw = Withdraw::where('id', $id)->first();
        $seller = $withdraw->seller()->first();
        $method = $withdraw->method()->select('name')->first();

        // Prepare notification data
        $notificationData = [
            'withdraw_id' => $withdraw->withdraw_id,
            'seller_id' => $withdraw->seller_id,
            'seller_name' => $seller->username,
            'amount' => $withdraw->amount,
            'payable_amount' => $withdraw->payable_amount,
            'total_charge' => $withdraw->total_charge,
            'method_name' => $method->name,
            'status' => 'approved',
            'approved_at' => now(),
        ];

        // Notify seller about withdrawal approval
        $seller->notify(new \App\Notifications\WithdrawalNotification([
            'title' => 'Withdrawal Approved',
            'message' => "Your withdrawal request #{$withdraw->withdraw_id} has been approved. Amount: {$withdraw->amount} - Payable: {$withdraw->payable_amount}",
            'url' => route('seller.withdraw.index'),
            'icon' => 'fas fa-check-circle',
            'extra' => $notificationData,
        ]));

        // Notify all admins about withdrawal approval
        $admins = \App\Models\Admin::all();
        foreach ($admins as $admin) {
            $admin->notify(new \App\Notifications\WithdrawalNotification([
                'title' => 'Withdrawal Approved',
                'message' => "Withdrawal request #{$withdraw->withdraw_id} from {$seller->username} has been approved. Amount: {$withdraw->amount}",
                'url' => route('admin.withdraw.withdraw_request'),
                'icon' => 'fas fa-check-circle',
                'extra' => $notificationData,
            ]));
        }

        //mail sending
        // get the website title & mail's smtp information from db
        $info = Basic::select('website_title', 'smtp_status', 'smtp_host', 'smtp_port', 'encryption', 'smtp_username', 'smtp_password', 'from_mail', 'from_name', 'base_currency_symbol_position', 'base_currency_symbol')
            ->first();

        //preparing mail info
        // get the mail template info from db
        $mailTemplate = MailTemplate::query()->where('mail_type', '=', 'withdraw_approve')->first();
        $mailData['subject'] = $mailTemplate->mail_subject;
        $mailBody = $mailTemplate->mail_body;

        // get the website title info from db
        $website_info = Basic::select('website_title')->first();

        // preparing dynamic data
        $sellerName = $seller->username;
        $sellerEmail = $seller->email;
        $seller_amount = $seller->amount;
        $withdraw_amount = $withdraw->amount;
        $total_charge = $withdraw->total_charge;
        $payable_amount = $withdraw->payable_amount;

        $websiteTitle = $website_info->website_title;

        // replacing with actual data
        $mailBody = str_replace('{seller_username}', $sellerName, $mailBody);
        $mailBody = str_replace('{withdraw_id}', $withdraw->withdraw_id, $mailBody);

        $mailBody = str_replace('{current_balance}', $info->base_currency_symbol . $seller_amount, $mailBody);
        $mailBody = str_replace('{withdraw_amount}', $info->base_currency_symbol . $withdraw_amount, $mailBody);
        $mailBody = str_replace('{charge}', $info->base_currency_symbol . $total_charge, $mailBody);
        $mailBody = str_replace('{payable_amount}', $info->base_currency_symbol . $payable_amount, $mailBody);

        $mailBody = str_replace('{withdraw_method}', $method->name, $mailBody);
        $mailBody = str_replace('{website_title}', $websiteTitle, $mailBody);

        $mailData['body'] = $mailBody;

        $mailData['recipient'] = $sellerEmail;
        //preparing mail info end

        // initialize a new mail
        $sendMail = new BasicMailer();
        $sendMail->sendMail($mailData);
        //mail sending end

        //update transcation 
        $transaction = Transaction::where([['order_id', $withdraw->id], ['transcation_type', 2]])->first();
        $transaction->update([
            'payment_status' => 'completed'
        ]);
        $withdraw->status = 1;
        $withdraw->save();
        Session::flash('success', 'Approved withdraw request successfully.');
        return redirect()->back();
    }
    //decline
    public function decline($id)
    {
        $withdraw = Withdraw::where('id', $id)->first();
        $seller = $withdraw->seller()->first();
        $method = $withdraw->method()->select('name')->first();

        // Prepare notification data
        $notificationData = [
            'withdraw_id' => $withdraw->withdraw_id,
            'seller_id' => $withdraw->seller_id,
            'seller_name' => $seller->username,
            'amount' => $withdraw->amount,
            'payable_amount' => $withdraw->payable_amount,
            'total_charge' => $withdraw->total_charge,
            'method_name' => $method->name,
            'status' => 'declined',
            'declined_at' => now(),
        ];

        // Notify seller about withdrawal decline
        $seller->notify(new \App\Notifications\WithdrawalNotification([
            'title' => 'Withdrawal Declined',
            'message' => "Your withdrawal request #{$withdraw->withdraw_id} has been declined. Amount: {$withdraw->amount} has been returned to your balance.",
            'url' => route('seller.withdraw.index'),
            'icon' => 'fas fa-times-circle',
            'extra' => $notificationData,
        ]));

        // Notify all admins about withdrawal decline
        $admins = \App\Models\Admin::all();
        foreach ($admins as $admin) {
            $admin->notify(new \App\Notifications\WithdrawalNotification([
                'title' => 'Withdrawal Declined',
                'message' => "Withdrawal request #{$withdraw->withdraw_id} from {$seller->username} has been declined. Amount: {$withdraw->amount}",
                'url' => route('admin.withdraw.withdraw_request'),
                'icon' => 'fas fa-times-circle',
                'extra' => $notificationData,
            ]));
        }

        //mail sending
        // get the website title & mail's smtp information from db
        $info = Basic::select('website_title', 'smtp_status', 'smtp_host', 'smtp_port', 'encryption', 'smtp_username', 'smtp_password', 'from_mail', 'from_name', 'base_currency_symbol_position', 'base_currency_symbol')
            ->first();

        //preparing mail info
        // get the mail template info from db
        $mailTemplate = MailTemplate::query()->where('mail_type', '=', 'withdraw_rejected')->first();
        $mailData['subject'] = $mailTemplate->mail_subject;
        $mailBody = $mailTemplate->mail_body;

        // get the website title info from db
        $website_info = Basic::select('website_title')->first();

        // preparing dynamic data
        $sellerName = $seller->username;
        $sellerEmail = $seller->email;
        $seller_amount = $seller->amount + $withdraw->amount;

        $websiteTitle = $website_info->website_title;

        // replacing with actual data
        $mailBody = str_replace('{seller_username}', $sellerName, $mailBody);
        $mailBody = str_replace('{withdraw_id}', $withdraw->withdraw_id, $mailBody);

        $mailBody = str_replace('{current_balance}', $info->base_currency_symbol . $seller_amount, $mailBody);
        $mailBody = str_replace('{website_title}', $websiteTitle, $mailBody);

        $mailData['body'] = $mailBody;

        $mailData['recipient'] = $sellerEmail;
        //preparing mail info end

        $sendMail = new BasicMailer();
        $sendMail->sendMail($mailData);
        $seller = Seller::where('id', $withdraw->seller_id)->first();
        $seller->amount = ($seller->amount + ($withdraw->amount));
        $seller->save();
        Session::flash('success', 'Withdraw request decline & balance return to seller account successfully!');
        //mail sending end

        //update transcation 
        $transaction = Transaction::where([['order_id', $withdraw->id], ['transcation_type', 2]])->first();
        $transaction->update([
            'payment_status' => 'declined'
        ]);

        $withdraw->status = 2;
        $withdraw->save();


        return redirect()->back();
    }
}
