<?php

namespace App\Http\Helpers;

use App\Models\BasicSettings\Basic;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Mail;
use Illuminate\Mail\Message;
use Illuminate\Support\Str;
use Throwable;

class BasicMailer
{
  public static function sendMail($data)
  {
    // Validate required data
    if (empty($data['recipient']) || empty($data['subject']) || empty($data['body'])) {
      \Log::warning('BasicMailer: Missing required email data', [
        'recipient' => $data['recipient'] ?? 'empty',
        'subject' => $data['subject'] ?? 'empty',
        'has_body' => !empty($data['body'])
      ]);
      return;
    }

    // Log email validation but don't block sending
    if (!filter_var($data['recipient'], FILTER_VALIDATE_EMAIL)) {
      \Log::warning('BasicMailer: Invalid email address format, but attempting to send anyway', ['recipient' => $data['recipient']]);
    }

    // get the website title & mail's smtp information from db
    $info = Basic::select('website_title', 'smtp_status', 'smtp_host', 'smtp_port', 'encryption', 'smtp_username', 'smtp_password', 'from_mail', 'from_name')
      ->first();

    // if smtp status == 1, then set some value for smtp
    if ($info->smtp_status == 1) {
      \Log::info('BasicMailer: SMTP is enabled, attempting to send email', [
        'host' => $info->smtp_host,
        'port' => $info->smtp_port,
        'from_mail' => $info->from_mail,
        'recipient' => $data['recipient']
      ]);
      $smtp = [
        'transport' => 'smtp',
        'host' => $info->smtp_host,
        'port' => $info->smtp_port,
        'encryption' => $info->encryption,
        'username' => $info->smtp_username,
        'password' => $info->smtp_password,
        'timeout' => null,
        'auth_mode' => null,
      ];
      Config::set('mail.mailers.smtp', $smtp);

      // add other informations and send the mail
      try {
        Mail::send([], [], function (Message $message) use ($data, $info) {
          $fromMail = $info->from_mail;
          $fromName = $info->from_name;
          $message->to($data['recipient'])
            ->subject($data['subject'])
            ->from($fromMail, $fromName)
            ->html($data['body'], 'text/html');
          if (array_key_exists('invoice', $data)) {
            $message->attach($data['invoice']);
          }
        });

        if (array_key_exists('sessionMessage', $data)) {
          Session::flash('success', $data['sessionMessage']);
        }
        
        \Log::info('BasicMailer: Email sent successfully', [
          'recipient' => $data['recipient'],
          'subject' => $data['subject']
        ]);
      } catch (Throwable $e) {
        \Log::error('BasicMailer: Email sending failed', [
          'error' => $e->getMessage(),
          'recipient' => $data['recipient'],
          'subject' => $data['subject']
        ]);
        Session::flash('warning', 'Mail could not be sent. Mailer Error: ' . Str::limit($e->getMessage(), 120));
      }
    } else {
      \Log::warning('BasicMailer: SMTP is disabled, cannot send email', [
        'smtp_status' => $info->smtp_status,
        'recipient' => $data['recipient'] ?? 'not set'
      ]);
    }
    return;
  }
}
