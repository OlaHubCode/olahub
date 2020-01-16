<?php

namespace OlaHub\UserPortal\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use OlaHub\UserPortal\Models\WishList;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;

class OlaHubWishListsController extends BaseController {

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
        $log->setLogSessionData(['module_name' => "WishLists", 'function_name' => "getList"]);
        
        $wishistModel = (new WishList)->newQuery();
        $wishistModel->where('user_id', app('session')->get('tempID'));
        $wishistModel->withoutGlobalScope("wishlistCountry");
        $wishist = $wishistModel->get();
        if ($wishist->count() < 1) {
            throw new NotAcceptableHttpException(404);
        }
        $return["data"] = (new WishList)->setWishlistData($wishist);
        $return['status'] = true;
        $return['code'] = 200;
        $log->setLogSessionData(['response' => $return]);
        $log->saveLogSessionData();
        return response($return, 200);
    }

    public function newWishListUser() {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "WishLists", 'function_name' => "newWishListUser"]);
        
        /* $validator = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::validateData(WishList::$columnsMaping, $this->requestData);
          if(isset($validator['status']) && !$validator['status']){
          return response(['status' => false, 'msg' => 'someData', 'code' => 406, 'errorData' => $validator['data']], 200);
          }
          $data = $this->wishListFilter();
          if ($data) {

          $logHelper = new \OlaHub\UserPortal\Helpers\LogHelper;
          $logHelper->setLog($this->requestData, 'Product already in Your wishlist', 'newWishListUser',$this->userAgent);
          return response(['status' => FALSE, 'msg' => 'alreadyInWishlist', 'code' => 500], 200);
          } else {
          $this->wishListAdd();
          $status = $this->wishListModel->save();
          if ($status) {
          $return = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseItem($this->wishListModel, '\OlaHub\UserPortal\ResponseHandlers\WishListsResponseHandler');
          $return['status'] = true;
          $return['code'] = 200;
          $item = $this->wishListModel->itemsMainData;
          $post = (new \OlaHub\UserPortal\Helpers\ItemHelper)->createItemPost($item->item_slug);
          $post->push('wishlists', app('session')->get('tempID'), true);
          }
          }
          $logHelper = new \OlaHub\UserPortal\Helpers\LogHelper;
          $logHelper->setLog($this->requestData, $return, 'newWishListUser',$this->userAgent);

          return response($return, 200); */


        $validator = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::validateData(WishList::$columnsMaping, $this->requestData);
        if (isset($validator['status']) && !$validator['status']) {
            $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'someData', 'code' => 406, 'errorData' => $validator['data']]]);
        $log->saveLogSessionData();
        
            return response(['status' => false, 'msg' => 'someData', 'code' => 406, 'errorData' => $validator['data']], 200);
        }

        if($this->requestData['itemType'] == 'designer'){

        }
        $data = WishList::withoutGlobalScope("wishlistCountry")->where('item_id', $this->requestData['itemID'])->where('user_id', app('session')->get('tempID'))->get();
        if ($data->count() > 0) {
            foreach ($data as $one) {
                $one->delete();
            }
        }


        if (isset($this->requestData['occasionValue']) && count($this->requestData['occasionValue']) > 0) {
            foreach ($this->requestData['occasionValue'] as $occassion) {
                $wishlist = new WishList;
                $wishlist->item_id = $this->requestData['itemID'];
                $wishlist->user_id = app('session')->get('tempID');
                $wishlist->occasion_id = $occassion;
                $wishlist->item_type = isset($this->requestData['itemType'])?$this->requestData['itemType']:'store';
                $wishlist->is_public = $this->requestData['wishlistType'];
                $wishlist->save();
            }
        } else {
            $wishlist = new WishList;
            $wishlist->item_id = $this->requestData['itemID'];
            $wishlist->user_id = app('session')->get('tempID');
            $wishlist->is_public = $this->requestData['wishlistType'];
            $wishlist->item_type = isset($this->requestData['itemType'])?$this->requestData['itemType']:'store';
            $wishlist->save();
        }
        $log->setLogSessionData(['response' => ['status' => true, 'msg' => 'You added item successfully', 'code' => 200]]);
        $log->saveLogSessionData();
        return response(['status' => true, 'msg' => 'You added item successfully', 'code' => 200], 200);
    }

    public function removeItemFromWishlist($itemID) {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "WishLists", 'function_name' => "removeItemFromWishlist"]);
        
        if ($itemID > 0) {
            WishList::withoutGlobalScope("wishlistCountry")->where("user_id", app('session')->get('tempID'))->where("item_id", $itemID)->delete();
            $log->setLogSessionData(['response' => ['status' => true, 'msg' => 'itemDeletedSuccessfully']]);
            $log->saveLogSessionData();
            return response(['status' => true, 'msg' => 'itemDeletedSuccessfully'], 200);
        }
        $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'invalidItem']]);
        $log->saveLogSessionData();
        return response(['status' => false, 'msg' => 'invalidItem'], 200);
    }

    public function removeWishListUser() {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "WishLists", 'function_name' => "removeWishListUser"]);
        
        $validator = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::validateData(WishList::$columnsMaping, $this->requestData);
        if (isset($validator['status']) && !$validator['status']) {
            $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'someData', 'code' => 406, 'errorData' => $validator['data']]]);
            $log->saveLogSessionData();

            return response(['status' => false, 'msg' => 'someData', 'code' => 406, 'errorData' => $validator['data']], 200);
        }
        $data = $this->wishListFilter();
        if ($data) {
            $data->delete();
            $item = $data->itemsMainData;
            $exist = (new \OlaHub\UserPortal\Helpers\ItemHelper)->createItemPost($item->item_slug, false);
            if (!$exist) {
                $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'someData', 'code' => 406, 'errorData' => []]]);
                $log->saveLogSessionData();
                return response(['status' => false, 'msg' => 'someData', 'code' => 406, 'errorData' => []], 200);
            }
            $post = (new \OlaHub\UserPortal\Helpers\ItemHelper)->createItemPost($item->item_slug);
            $post->pull('wishlists', app('session')->get('tempID'), true);
            $log->setLogSessionData(['response' => ['status' => TRUE, 'msg' => 'removedFromWishlistSuccessfully', 'code' => 200]]);
            $log->saveLogSessionData();
            return response(['status' => TRUE, 'msg' => 'removedFromWishlistSuccessfully', 'code' => 200], 200);
        } else {
            $log->setLogSessionData(['response' => ['status' => FALSE, 'msg' => 'notInWishlist', 'code' => 204]]);
            $log->saveLogSessionData();
        
            return response(['status' => FALSE, 'msg' => 'notInWishlist', 'code' => 204], 200);
        }
    }

    public function getWishlistOccasions() {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "WishLists", 'function_name' => "getWishlistOccasions"]);
        
        $occassionsCountry = \OlaHub\UserPortal\Models\ManyToMany\occasionCountries::where('country_id', app('session')->get('def_country')->id)->get();
        if ($occassionsCountry->count() < 1) {
            $log->setLogSessionData(['response' => ['status' => FALSE, 'msg' => 'notInWishlist', 'code' => 204]]);
            $log->saveLogSessionData();
            return response(['status' => false, 'msg' => 'NoData', 'code' => 204], 200);
        }

        $selectedOccasions = [];
        $wishlistType = 1;
        if (isset($this->requestData['itemId']) && $this->requestData['itemId'] > 0) {
            $wishlists = WishList::withoutGlobalScope("wishlistCountry")->where('item_id', $this->requestData['itemId'])->where('user_id', app('session')->get('tempID'))->get();
            if ($wishlists->count() > 0) {
                foreach ($wishlists as $wishlist) {
                    array_push($selectedOccasions, (string) $wishlist->occasion_id);
                    $wishlistType = $wishlist->is_public;
                }
            }
        }


        $return['Occassions'] = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseCollection($occassionsCountry, '\OlaHub\UserPortal\ResponseHandlers\OccassionsForPrequestFormsResponseHandler');
        $return['selectedOccasions'] = $selectedOccasions;
        $return['wishlistType'] = $wishlistType;
        $return['status'] = true;
        $return['code'] = 200;
        $log->setLogSessionData(['response' => ['status' => FALSE, 'msg' => 'notInWishlist', 'code' => 204]]);
        $log->saveLogSessionData();
        return response($return, 200);
    }
    
    
    public function deleteItemFromWishlistById($id){
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "WishLists", 'function_name' => "Delete item from wishlist by id"]);
        
        $item = WishList::withoutGlobalScope("wishlistCountry")->where('user_id',app('session')->get('tempID'))->where('id',$id)->first();
        if($item){
            $item->delete();
            $logHelper = new \OlaHub\UserPortal\Helpers\LogHelper;
             $log->setLogSessionData(['response' => ['status' => TRUE, 'msg' => 'removedFromWishlistSuccessfully', 'code' => 200]]);
            $log->saveLogSessionData();
           return response(['status' => TRUE, 'msg' => 'removedFromWishlistSuccessfully', 'code' => 200], 200); 
        }
         $log->setLogSessionData(['response' => ['status' => FALSE, 'msg' => 'notInWishlist', 'code' => 204]]);
            $log->saveLogSessionData();
        return response(['status' => FALSE, 'msg' => 'notInWishlist', 'code' => 204], 200);
    }

    
    private function wishListFilter() {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "WishLists", 'function_name' => "wishListFilter"]);
        
        $filters = [];
        foreach ($this->requestData as $input => $value) {
            $filters[\OlaHub\UserPortal\Helpers\CommonHelper::getColumnName(WishList::$columnsMaping, $input)] = $value;
        }
        unset($value, $input);
        return WishList::withoutGlobalScope("wishlistCountry")->where('user_id', app('session')->get('tempID'))->where($filters)->first();
    }

    private function wishListAdd() {
         $log = new \OlaHub\UserPortal\Helpers\LogHelper();
         $log->setLogSessionData(['module_name' => "WishLists", 'function_name' => "wishListAdd"]);
        
        $this->wishListModel = new WishList;
        foreach ($this->requestData as $input => $value) {
            $this->wishListModel->{\OlaHub\UserPortal\Helpers\CommonHelper::getColumnName(WishList::$columnsMaping, $input)} = $value;
        }
        unset($value, $input);
        $this->wishListModel->user_id = app('session')->get('tempID');
    }

}
