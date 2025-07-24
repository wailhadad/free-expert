<?php

namespace App\Jobs;

use App\Http\Helpers\MegaMailer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UserMembershipExpiredMail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $user;
    public $bs;
    public $membership;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($user, $bs, $membership)
    {
        $this->user = $user;
        $this->bs = $bs;
        $this->membership = $membership;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $mailer = new MegaMailer();
        
        $data = [
            'toMail' => $this->user->email ?: $this->user->email_address,
            'templateType' => 'user_membership_expired',
            'username' => $this->user->username,
            'package_title' => $this->membership->package->title ?? 'Unknown Package',
            'expire_date' => $this->membership->expire_date,
            'login_link' => '<a href="https://free-expert.com/user/dashboard">Go To Dashboard</a>',
            'website_title' => $this->bs->website_title,
        ];
        
        $mailer->mailFromAdmin($data);
    }
} 