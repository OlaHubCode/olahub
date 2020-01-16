<?php

namespace OlaHub\UserPortal\Middlewares;

use Closure;

class RequestTypeMiddleware {

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request 
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next) {
        if (strtoupper($request->method()) == 'OPTIONS' || $request->header('x-requested-with') || preg_match("~\bzainCallBack\b~",\Illuminate\Support\Facades\URL::current()) || preg_match("~\b/images/\b~",\Illuminate\Support\Facades\URL::current()) || preg_match("~\b/videos/\b~",\Illuminate\Support\Facades\URL::current()) || preg_match("~\b/migrate/\b~",\Illuminate\Support\Facades\URL::current()) || preg_match("~\b/crons/\b~",\Illuminate\Support\Facades\URL::current())) {
            return $next($request);
        } 
        abort(404);
    }

}
