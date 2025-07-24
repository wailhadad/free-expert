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
        Schema::table('basic_settings', function (Blueprint $table) {
            $table->integer('grace_period_minutes')->default(2)->after('expiration_reminder');
        });

        // Add grace period countdown alert template
        DB::table('mail_templates')->insert([
            'mail_type' => 'grace_period_countdown_alert',
            'mail_subject' => 'Your Membership is in Grace Period',
            'mail_body' => '<p>Hi {username},</p>
<p>Your membership for package "{package_title}" has entered the grace period on {website_title}.</p>
<p><strong>Package:</strong> {package_title}<br>
<strong>Grace Period Ends:</strong> {grace_period_until}<br>
<strong>Time Remaining:</strong> {time_remaining}</p>
<p>Please renew your membership before the grace period ends to avoid losing access to premium features.</p>
<p>{login_link}</p>
<p>If you have any questions or need assistance, please contact our support team.</p>
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
        Schema::table('basic_settings', function (Blueprint $table) {
            $table->dropColumn('grace_period_minutes');
        });

        DB::table('mail_templates')->where('mail_type', 'grace_period_countdown_alert')->delete();
    }
}; 