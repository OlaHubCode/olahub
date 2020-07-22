<?php

namespace OlaHub\UserPortal\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use OlaHub\UserPortal\Models\RegistryGiftModel;
use OlaHub\UserPortal\Models\RegistryModel;

class RegistryGiftController extends BaseController
{

    protected $requestData;
    protected $requestFilter;
    private $gift;
    protected $userAgent;

    public function __construct(Request $request)
    {
        $return = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::getRequest($request);
        $this->requestData = $return['requestData'];
        $this->requestFilter = $return['requestFilter'];
        $this->userAgent = $request->header('uniquenum') ? $request->header('uniquenum') : $request->header('user-agent');
    }
    public function newGift()
    {
        $log = new \OlaHub\UserPortal\Helpers\Logs();
        $userData = app('session')->get('tempData');
        $log->saveLog($userData->id, $this->requestData, 'newGift');
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['module_name' => "Registry", 'function_name' => "newGift"]);

        $validator = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::validateData(RegistryGiftModel::$columnsMaping, (array) $this->requestData);

        if (isset($validator['status']) && !$validator['status']) {
            (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['response' => ['status' => false, 'msg' => 'someData', 'code' => 406, 'errorData' => $validator['data']]]);
            (new \OlaHub\UserPortal\Helpers\LogHelper)->saveLogSessionData();
            return response(['status' => false, 'msg' => 'someData', 'code' => 406, 'errorData' => $validator['data']], 200);
        }

        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Start add item"]);

        if (RegistryModel::validateRegistryId($this->requestData)) {
            $registry = RegistryModel::where('id', $this->requestData['registryId'])->first();

            if ($registry->user_id != app('session')->get('tempID')) {
                (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['response' => ['status' => false, 'msg' => 'NotAuthorizedToAddGift', 'code' => 400]]);
                (new \OlaHub\UserPortal\Helpers\LogHelper)->saveLogSessionData();

                return response(['status' => false, 'msg' => 'NotAuthorizedToAddGift', 'code' => 400], 200);
            } elseif ($registry->status != 1) {
                (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['response' => ['status' => false, 'msg' => 'NotAllowedToAddGift', 'code' => 400]]);
                (new \OlaHub\UserPortal\Helpers\LogHelper)->saveLogSessionData();

                return response(['status' => false, 'msg' => 'NotAllowedToAddGift', 'code' => 400], 200);
            }
            $itemType = $this->requestData['registryItemType'];
            $country = $registry->country_id;
            $this->gift = new RegistryGiftModel;
            foreach ($this->requestData as $input => $value) {
                if (isset(RegistryGiftModel::$columnsMaping[$input])) {
                    $this->gift->{\OlaHub\UserPortal\Helpers\CommonHelper::getColumnName(RegistryGiftModel::$columnsMaping, $input)} = $value;
                }
            }

            switch ($itemType) {
                case "store":
                    $item = \OlaHub\UserPortal\Models\CatalogItem::withoutGlobalScope("country")->whereHas('merchant', function ($q) use ($country) {
                        $q->withoutGlobalScope("country");
                        $q->country_id = $country;
                    })->find($this->requestData['registryItem']);
                    if ($item) {

                        $this->gift->unit_price = \OlaHub\UserPortal\Models\CatalogItem::checkPrice($item, TRUE);
                        $this->gift->total_price = (float) $this->gift->unit_price * $this->gift->quantity;
                    }
                    break;
                case "designer":
                    $item = \OlaHub\UserPortal\Models\DesignerItems::where("id", $this->requestData['registryItem'])->first();
                    if ($item) {

                        $this->gift->unit_price = \OlaHub\UserPortal\Models\DesignerItems::checkPrice($item, true);
                        $this->gift->total_price = (float) $this->gift->unit_price * $this->gift->quantity;
                    }
                    break;
            }

            $this->gift->created_by = app('session')->get('tempID');
            $saved = $this->gift->save();

            if ($saved) {

                $return = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseItem($this->gift, '\OlaHub\UserPortal\ResponseHandlers\RegistryGiftResponseHandler');
                $return['status'] = TRUE;
                $return['code'] = 200;
                (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['response' => $return]);
                (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_endData" => "End add Gift"]);
                (new \OlaHub\UserPortal\Helpers\LogHelper)->saveLogSessionData();
                return response($return, 200);
            }
        }
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['response' => ['status' => false, 'msg' => 'NoData', 'code' => 204]]);
        (new \OlaHub\UserPortal\Helpers\LogHelper)->saveLogSessionData();

        return response(['status' => false, 'msg' => 'NoData', 'code' => 204], 200);
    }

    public function removeRegistryItem()
    {
        $log = new \OlaHub\UserPortal\Helpers\Logs();

        $userData = app('session')->get('tempData');
        $log->saveLog($userData->id, $this->requestData, ' removeRegistryItem');

        (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['module_name' => "Registry", 'function_name' => "removeRegistryItem"]);
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Delete registry item"]);

        $this->gift = RegistryGiftModel::where('id', $this->requestData['registryGiftId'])->where('created_by', app('session')->get('tempID'))->first();
        if ($this->gift) {
            $registry = RegistryModel::where('user_id', $this->gift->created_by)->where('id', $this->gift->registry_id)->first();
            if ($registry && $this->gift->status == 1) {
                $this->gift->delete();

                (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['response' => ['status' => true, 'msg' => 'RegistryGiftDeleted', 'code' => 200]]);
                (new \OlaHub\UserPortal\Helpers\LogHelper)->saveLogSessionData();

                return response(['status' => true, 'msg' => 'RegistryGiftDeleted', 'code' => 200], 200);
            }
            (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['response' => ['status' => false, 'msg' => 'NotAllowDeleteGift', 'code' => 400]]);
            (new \OlaHub\UserPortal\Helpers\LogHelper)->saveLogSessionData();
            return response(['status' => false, 'msg' => 'NotAllowDeleteGift', 'code' => 400], 200);
        }
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['response' => ['status' => false, 'msg' => 'NoData', 'code' => 204]]);
        (new \OlaHub\UserPortal\Helpers\LogHelper)->saveLogSessionData();
        return response(['status' => false, 'msg' => 'NoData', 'code' => 204], 200);
    }

    public function ListRegistryGifts()
    {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Registry", 'function_name' => "ListRegistryGifts"]);

        if (isset($this->requestData['registryId']) && $this->requestData['registryId'] > 0) {
            $items = RegistryGiftModel::where('registry_id', $this->requestData['registryId'])->get();
            foreach ($items as $key => $item) {
                $nitem = \OlaHub\UserPortal\Models\CatalogItem::withoutGlobalScope('country')->where('id', $item->item_id)->first();
                $qty = $nitem->quantityData()->first();
                if (!$qty->quantity && $item->status < 3) {
                    $item->delete();
                    unset($items[$key]);
                }
            }
            $return = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseCollection($items, '\OlaHub\UserPortal\ResponseHandlers\RegistryGiftResponseHandler');
            $return['status'] = TRUE;
            $return['code'] = 200;
            $log->setLogSessionData(['response' => $return]);
            $log->saveLogSessionData();
            return response($return, 200);
        }
        $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'NoData', 'code' => 204]]);
        $log->saveLogSessionData();
        return response(['status' => false, 'msg' => 'NoData', 'code' => 204], 200);
    }
}
