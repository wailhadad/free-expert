<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Add user membership expired template
        DB::table('mail_templates')->insert([
            'mail_type' => 'user_membership_expired',
            'mail_subject' => 'Your Membership Has Expired',
            'mail_body' => '<p>Hi {username},</p>
<p>We are sorry to inform you that your membership for package "{package_title}" has expired on {website_title}.</p>
<p><strong>Package:</strong> {package_title}<br>
<strong>Expired Date:</strong> {expire_date}</p>
<p>To continue accessing premium features and services, please renew your membership by visiting your dashboard.</p>
<p>{login_link}</p>
<p>If you have any questions or need assistance, please contact our support team.</p>
<p>Best Regards,<br>{website_title}</p>'
        ]);

        // Add user membership expiry reminder template
        DB::table('mail_templates')->insert([
            'mail_type' => 'user_membership_expiry_reminder',
            'mail_subject' => 'Your Membership Will Expire Soon',
            'mail_body' => '<p>Hi {username},</p>
<p>This is a friendly reminder that your membership for package "{package_title}" will expire on {last_day_of_membership} on {website_title}.</p>
<p><strong>Package:</strong> {package_title}<br>
<strong>Expiry Date:</strong> {last_day_of_membership}</p>
<p>To avoid any interruption in your services, please renew your membership before the expiry date.</p>
<p>{login_link}</p>
<p>Thank you for choosing {website_title}!</p>
<p>Best Regards,<br>{website_title}</p>'
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('mail_templates')->where('mail_type', 'user_membership_expired')->delete();
        DB::table('mail_templates')->where('mail_type', 'user_membership_expiry_reminder')->delete();
    }
}; 