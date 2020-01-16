<?php

namespace OlaHub\UserPortal\Middlewares;

use Closure;

class LogMiddleware {

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request 
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next) {
        
        $agent = $request->headers->get('user-agent');
        $browserData = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::getUserBrowserAndOS($agent);
        $browserAndOs = explode('-', $browserData);
        $time = date("H:i:s");
        $date = date("Y-m-d");
        $link = $request->fullUrl();
        $ipData = $request->ip();
        
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['Time' => $time, 'Date' => $date, 'Link' => $link, 'Browser' => $browserAndOs[1], 'OS' => $browserAndOs[0], 'IP' => $ipData ]);
        
        return $next($request);
    }

}
