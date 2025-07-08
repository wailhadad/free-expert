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
      $websiteInfo = Basic::First();
      $file_name = uniqid($key) . ".pdf";
      
      // Use the new folder structure
      $directory = public_path('assets/file/invoices/' . $folder . '/');
      @mkdir($directory, 0775, true);

      $pdf = Pdf::loadView('pdf.membership', compact('request','websiteInfo','member', 'password', 'amount', 'payment_method', 'phone', 'base_currency_symbol_position', 'base_currency_symbol', 'base_currency_text', 'order_id', 'package_title', 'membership'))
        ->setPaper('a4', 'portrait')
        ->save($directory . $file_name);

      return $file_name;
  }
}
