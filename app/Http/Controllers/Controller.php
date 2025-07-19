<?php
//
namespace App\Http\Controllers;

use App\Models\BasicSettings\Basic;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
  use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

  public function getCurrencyInfo()
  {
    $baseCurrencyInfo = Basic::select('base_currency_symbol', 'base_currency_symbol_position', 'base_currency_text', 'base_currency_text_position', 'base_currency_rate')
      ->firstOrFail();

    return $baseCurrencyInfo;
  }
  public function makeInvoice($request, $key, $member, $password, $amount, $payment_method, $phone, $base_currency_symbol_position, $base_currency_symbol, $base_currency_text, $order_id, $package_title, $membership, $folder = 'user-memberships')
  {
      // For seller memberships, use the Membership model's generateInvoice method
      if ($folder === 'seller-memberships' && $membership && method_exists($membership, 'generateInvoice')) {
          try {
              // Use extension method for extensions, regular method for new memberships
              if ($key === 'extend' && method_exists($membership, 'generateExtensionInvoice')) {
                  $file_name = $membership->generateExtensionInvoice();
              } else {
                  $file_name = $membership->generateInvoice();
              }
              \Log::info('Seller membership invoice generated using model method', [
                  'membership_id' => $membership->id,
                  'invoice_filename' => $file_name,
                  'folder' => $folder,
                  'type' => $key
              ]);
              return $file_name;
          } catch (\Exception $e) {
              \Log::error('Failed to generate seller membership invoice using model method', [
                  'membership_id' => $membership->id,
                  'error' => $e->getMessage()
              ]);
              // Fall back to old method if model method fails
          }
      }
      
      // Original method for user memberships or fallback
      $websiteInfo = Basic::First();
      $file_name = uniqid($key) . ".pdf";
      
      // Use the new folder structure
      $directory = public_path('assets/file/invoices/' . $folder . '/');
      @mkdir($directory, 0775, true);

      $pdf = Pdf::loadView('pdf.membership', compact('request','websiteInfo','member', 'password', 'amount', 'payment_method', 'phone', 'base_currency_symbol_position', 'base_currency_symbol', 'base_currency_text', 'order_id', 'package_title', 'membership'))
        ->setPaper('a4', 'portrait')
        ->save($directory . $file_name);

      // Save invoice filename to membership database record
      if ($membership && method_exists($membership, 'update')) {
          try {
              $membership->update(['invoice' => $file_name]);
              \Log::info('Invoice filename saved to database', [
                  'membership_id' => $membership->id,
                  'invoice_filename' => $file_name,
                  'folder' => $folder
              ]);
          } catch (\Exception $e) {
              \Log::error('Failed to save invoice filename to database', [
                  'membership_id' => $membership->id,
                  'error' => $e->getMessage()
              ]);
          }
      }

      return $file_name;
  }
}
