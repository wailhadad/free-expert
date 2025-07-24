<?php

namespace App\Jobs;

use App\Http\Helpers\MegaMailer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UserMembershipReminderMail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $user;
    public $bs;
    public $expire_date;
    public $membership;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($user, $bs, $expire_date, $membership)
    {
        $this->user = $user;
        $this->bs = $bs;
        $this->expire_date = $expire_date;
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
            'toName' => $this->user->username,
            'username' => $this->user->username,
            'package_title' => $this->membership->package->title ?? 'Unknown Package',
            'last_day_of_membership' => $this->expire_date,
            'login_link' => '<a href="https://free-expert.com/user/login">Login</a>',
            'website_title' => $this->bs->website_title,
            'templateType' => 'user_membership_expiry_reminder'
        ];

        $mailer->mailFromAdmin($data);
    }
} 