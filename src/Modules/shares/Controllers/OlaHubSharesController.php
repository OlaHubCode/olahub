<?php

namespace OlaHub\UserPortal\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use OlaHub\UserPortal\Models\SharedItems;

class OlaHubSharesController extends BaseController
{

    protected $requestData;
    protected $requestFilter;
    private $wishListModel;
    protected $userAgent;

    public function __construct(Request $request)
    {
        $return = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::getRequest($request);
        $this->requestData = $return['requestData'];
        $this->requestFilter = $return['requestFilter'];
        $this->userAgent = $request->header('uniquenum') ? $request->header('uniquenum') : $request->header('user-agent');
    }

    public function newSharedItemsUser()
    {
          
        $log = new \OlaHub\UserPortal\Helpers\Logs();
        $userData = app('session')->get('tempData');
        $log->setLogSessionData(['module_name' => "Likes", 'function_name' => "newSharedItemsUser"]);

        if (isset($this->requestData['itemID']) && !$this->requestData['itemID']) {
            $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'NoData', 'code' => 204]]);
            $log->saveLogSessionData();
            return response(['status' => false, 'msg' => 'NoData', 'code' => 204], 200);
        }
        $shared = SharedItems::where('item_id', $this->requestData['itemID'])
            ->where('item_type', $this->requestData['itemType'])
            ->where('user_id', app('session')->get('tempID'))->first();
        if (!$shared) {
            $like = new SharedItems;
            $like->item_id = $this->requestData['itemID'];
            $like->item_type = isset($this->requestData['itemType']) ? $this->requestData['itemType'] : 'store';
            $like->save();
        }

        $log->setLogSessionData(['response' => ['status' => TRUE, 'msg' => 'newSharedItemsUser', 'code' => 200]]);
        $log->saveLogSessionData();
        $log->saveLog($userData->id, $this->requestData, 'Share_Item');

        return response(['status' => TRUE, 'code' => 200], 200);
    }

    public function removeSharedItemsUser()
    {
        $log = new \OlaHub\UserPortal\Helpers\Logs();
        $userData = app('session')->get('tempData');
        $log->setLogSessionData(['module_name' => "Likes", 'function_name' => "removeSharedItemsUser"]);

        if (isset($this->requestData['itemID']) && !$this->requestData['itemID']) {
            $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'NoData', 'code' => 204]]);
            $log->saveLogSessionData();
            return response(['status' => false, 'msg' => 'NoData', 'code' => 204], 200);
        }
        SharedItems::where('item_id', $this->requestData['itemID'])
            ->where('item_type', $this->requestData['itemType'])
            ->where('user_id', app('session')->get('tempID'))->delete();
        $log->setLogSessionData(['response' => ['status' => TRUE, 'msg' => 'unlikeProductNow', 'code' => 200]]);
        $log->saveLogSessionData();
        $log->saveLog($userData->id, $this->requestData, 'Remove_shared_Item');

        return response(['status' => TRUE, 'code' => 200], 200);
    }
}
