<?php

namespace OlaHub\UserPortal\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use OlaHub\UserPortal\Models\CelebrationModel;
use OlaHub\UserPortal\Models\CelebrationParticipantsModel;

class GiftController extends BaseController
{

    protected $requestData;
    protected $requestFilter;
    private $celebration;
    protected $userAgent;

    public function __construct(Request $request)
    {
        $return = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::getRequest($request);
        $this->requestData = $return['requestData'];
        $this->requestFilter = $return['requestFilter'];
        $this->userAgent = $request->header('uniquenum') ? $request->header('uniquenum') : $request->header('user-agent');
    }
    public function newGift($itemType = "store", $type = "default"){
        $log = new \OlaHub\UserPortal\Helpers\Logs();
        $userData = app('session')->get('tempData');
        $log->saveLog($userData->id, $this->requestData, 'newGift');
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['module_name' => "Registry", 'function_name' => "newGift"]);



    }


}
