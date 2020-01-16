<?php

namespace OlaHub\UserPortal\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use OlaHub\UserPortal\Models\LikedItems;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;

class OlaHubLikesController extends BaseController {

    protected $requestData;
    protected $requestFilter;
    private $wishListModel;
    protected $userAgent;

    public function __construct(Request $request) {
        $return = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::getRequest($request);
        $this->requestData = $return['requestData'];
        $this->requestFilter = $return['requestFilter'];
        $this->userAgent = $request->header('uniquenum') ? $request->header('uniquenum') : $request->header('user-agent');
    }

    public function getList() {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Likes", 'function_name' => "getList"]);
       
        $wishistModel = (new LikedItems)->newQuery();
        $wishistModel->where('user_id', app('session')->get('tempID'));
        $wishist = $wishistModel->get();

        if ($wishist->count() < 1) {
            throw new NotAcceptableHttpException(404);
        }
        $return = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseCollection($wishist, '\OlaHub\UserPortal\ResponseHandlers\LikedItemsResponseHandler');
        $return['status'] = true;
        $return['code'] = 200;
        $log->setLogSessionData(['response' => $return]);
        $log->saveLogSessionData();
        return response($return, 200);
    }

    public function newLikedItemsUser() {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Likes", 'function_name' => "newLikedItemsUser"]);
       
        //$validator = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::validateData(LikedItems::$columnsMaping, $this->requestData);
        if(isset($this->requestData['itemID']) && !$this->requestData['itemID']){
            $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'NoData', 'code' => 204]]);
             $log->saveLogSessionData();
            return response(['status' => false, 'msg' => 'NoData', 'code' => 204], 200);
        }
            $item = \OlaHub\UserPortal\Models\CatalogItem::where('id', $this->requestData['itemID'])->first();
            if ($item) {
                $post = (new \OlaHub\UserPortal\Helpers\ItemHelper)->createItemPost($item->item_slug);
                
                if(in_array(app('session')->get('tempID'), $post->likes)){
                    $log->setLogSessionData(['response' => ['status' => FALSE, 'msg' => 'likedProductBefore', 'code' => 204]]);
                    $log->saveLogSessionData();
                    return response(['status' => FALSE, 'msg' => 'likedProductBefore', 'code' => 204], 200);
                }
                
                $post->push('likes', app('session')->get('tempID'), true);
                $return = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseItem($item, '\OlaHub\UserPortal\ResponseHandlers\LikedItemsResponseHandler');
                $return['status'] = true;
                $return['code'] = 200;
                $log->setLogSessionData(['response' => $return]);
                $log->saveLogSessionData();
                return response($return, 200);
            }
            $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'NoData', 'code' => 204]]);
                    $log->saveLogSessionData();
        return response(['status' => false, 'msg' => 'NoData', 'code' => 204], 200);
    }

    public function removeLikedItemsUser() {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Likes", 'function_name' => "removeLikedItemsUser"]);
       
        //$validator = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::validateData(LikedItems::$columnsMaping, $this->requestData);
        
        if(isset($this->requestData['itemID']) && !$this->requestData['itemID']){
            $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'NoData', 'code' => 204]]);
                $log->saveLogSessionData();
            return response(['status' => false, 'msg' => 'NoData', 'code' => 204], 200);
        }
            $item = \OlaHub\UserPortal\Models\CatalogItem::where('id', $this->requestData['itemID'])->first();
            if ($item) {
                
                $exist = (new \OlaHub\UserPortal\Helpers\ItemHelper)->createItemPost($item->item_slug, false);
                if(!$exist){
                    $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'NoData', 'code' => 204]]);
                $log->saveLogSessionData();
                    return response(['status' => false, 'msg' => 'NoData', 'code' => 204], 200);
                }
                
                $post = (new \OlaHub\UserPortal\Helpers\ItemHelper)->createItemPost($item->item_slug);
                
                if(!in_array(app('session')->get('tempID'), $post->likes)){
                    $log->setLogSessionData(['response' => ['status' => FALSE, 'msg' => 'notLikedProductsList', 'code' => 204]]);
                   $log->saveLogSessionData();
                    return response(['status' => FALSE, 'msg' => 'notLikedProductsList', 'code' => 204], 200);
                }
                
                $post->pull('likes', app('session')->get('tempID'), true);
                $log->setLogSessionData(['response' => ['status' => TRUE, 'msg' => 'unlikeProductNow', 'code' => 200]]);
                $log->saveLogSessionData();
                return response(['status' => TRUE, 'msg' => 'unlikeProductNow', 'code' => 200], 200);
                
                
            }
            $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'NoData', 'code' => 204]]);
                $log->saveLogSessionData();
        return response(['status' => false, 'msg' => 'NoData', 'code' => 204], 200);
    }

    private function wishListFilter() {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Likes", 'function_name' => "wishListFilter"]);
       
        $filters = [];
        foreach ($this->requestData as $input => $value) {
            $filters[\OlaHub\UserPortal\Helpers\CommonHelper::getColumnName(LikedItems::$columnsMaping, $input)] = $value;
        }
        unset($value, $input);
        return LikedItems::where('user_id', app('session')->get('tempID'))->where($filters)->first();
    }

    private function wishListAdd() {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Likes", 'function_name' => "wishListAdd"]);
       
        $this->wishListModel = new LikedItems;
        foreach ($this->requestData as $input => $value) {
            $this->wishListModel->{\OlaHub\UserPortal\Helpers\CommonHelper::getColumnName(LikedItems::$columnsMaping, $input)} = $value;
        }
        unset($value, $input);
        $this->wishListModel->user_id = app('session')->get('tempID');
    }

}
