<?php

namespace OlaHub\UserPortal\Middlewares;

use Closure;

class KillVarsMiddleware {

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request 
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next) {
        gc_enable();
        $response = $next($request);
        gc_collect_cycles();
//        $vars = array_keys(get_defined_vars());
//        foreach ($vars as $var) {
//            if ($var != 'response') {
//                unset($$var);
//            }
//        }
//        unset($vars, $var);
        if (!method_exists($response, 'render')) {
            return $response;
        }

        app('session')->flush();
        return $response->render();
    }

}
