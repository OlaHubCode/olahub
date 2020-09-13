<?php

namespace OlaHub\Exceptions;

use Exception;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Access\AuthorizationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Laravel\Lumen\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Log;

class Handler extends ExceptionHandler {

    /**
     * A list of the exception types that should not be reported.
     *
     * @var array
     */
    protected $dontReport = [
        AuthorizationException::class,
        HttpException::class,
        ModelNotFoundException::class,
        ValidationException::class,
    ];

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param  \Exception  $e
     * @return void
     */
    public function report(Exception $e) {
        parent::report($e);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $e
     * @return \Illuminate\Http\Response
     */
    public function render($request, Exception $e) {

        if ($e instanceof ModelNotFoundException) {
            Log::info('error: No data found');
            return response()->json((['status' => false, 'msg' => 'NoData', 'code' => 204]), 200);
        }

        if ($e instanceof AuthorizationException) {
            Log::info('error: Insufficient privileges to perform this action');
            return response()->json((['status' => false, 'msg' => 'InsufficientPrivileges', 'code' => 401]), 200);
        }

        if ($e instanceof MethodNotAllowedHttpException) {
            Log::info('error: Method Not Allowed');
            return response()->json((['status' => false, 'msg' => 'MethodNotAllowed', 'code' => 500]), 200);
        }

        if ($e instanceof NotFoundHttpException) {
            Log::info('error: The requested resource was not found');
            return response()->json((['status' => false, 'msg' => 'requestedResourceNotFound', 'code' => 500]), 200);
        }

        if ($e instanceof UnauthorizedHttpException) {
            return response()->json((['status' => false, 'msg' => 'authorityAccessThisPage', 'code' => 401]), 200);
        }

        if ($e instanceof NotAcceptableHttpException) {
            return response()->json((['status' => false, 'msg' => 'NoData', 'code' => 204]), 200);
        }

        if ($e instanceof BadRequestHttpException) {
            // return response()->json((['status' => false, 'msg' => 'occuredDuringSendNotification', 'code' => 500]), 200);
        }

        if (env('APP_ENV') == 'local') {
            return parent::render($request, $e);
        } else {
            // return response()->json((['status' => false, 'msg' => 'pleaseTryAgain', 'code' => 500]), 200);
        }
    }

}
