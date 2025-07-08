<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class Handler extends ExceptionHandler
{
  /**
   * A list of the exception types that are not reported.
   *
   * @var array
   */
  protected $dontReport = [
    //
  ];

  /**
   * A list of the inputs that are never flashed for validation exceptions.
   *
   * @var array
   */
  protected $dontFlash = [
    'current_password',
    'password',
    'password_confirmation',
  ];

  /**
   * Register the exception handling callbacks for the application.
   *
   * @return void
   */
  public function register()
  {
    $this->reportable(function (Throwable $e) {
      //
    });
  }

  /**
   * Convert an authentication exception into an unauthenticated response.
   *
   * @param  \Illuminate\Http\Request  $request
   * @param  \Illuminate\Auth\AuthenticationException  $exception
   * @return \Symfony\Component\HttpFoundation\Response
   */
  protected function unauthenticated($request, \Illuminate\Auth\AuthenticationException $exception)
  {
    if ($request->expectsJson() || $request->is('api/*') || $request->ajax()) {
      return response()->json(['message' => $exception->getMessage()], 401);
    }
    $guard = data_get($exception->guards(), 0);
    switch ($guard) {
      case 'seller':
        $login = 'seller.login';
        break;
      case 'admin':
        $login = 'admin.login';
        break;
      default:
        $login = 'login';
        break;
    }
    // If the route does not exist, fallback to home
    try {
      return redirect()->guest(route($login));
    } catch (\Exception $e) {
      return redirect('/');
    }
  }
}
