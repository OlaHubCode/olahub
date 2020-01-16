<?php

namespace OlaHub\UserPortal\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Http\Request;

class OlaHubDesginerController extends BaseController {

    protected $requestData;

    public function __construct(Request $request) {
        $return = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::getRequest($request);
        $this->requestData = $return['requestData'];
    }
    
    public function getAllAssignedToFranchiseCountries(){
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Desginer", 'function_name' => "getAllAssignedToFranchiseCountries"]);
       
        $franchiseCountries = \OlaHub\UserPortal\Models\FranchiseDesignerCountry::all();
        if($franchiseCountries->count() > 0){
            $countriesId = [];
            foreach ($franchiseCountries as $franchiseCountry){
                array_push($countriesId, $franchiseCountry->country_id);
            }
            $countries = \OlaHub\UserPortal\Models\Country::withoutGlobalScope('countrySupported')->whereIn('id', $countriesId)->get();
            if($countries->count() > 0){
                $return = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseCollection($countries, '\OlaHub\UserPortal\ResponseHandlers\CountriesForPrequestFormsResponseHandler');
                $return['status'] = TRUE;
                $return['code'] = 200;
                $log->setLogSessionData(['response' => $return]);
                $log->saveLogSessionData();
                return response($return, 200);
            }
        }
        $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'NoData', 'code' => 204]]);
        $log->saveLogSessionData();
        return response(['status' => false, 'msg' => 'NoData', 'code' => 204], 200);
    }
    
    public function getAllParentCategories(){
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Desginer", 'function_name' => "getAllParentCategories"]);
       
        $parentCategories = \OlaHub\UserPortal\Models\ItemCategory::where('parent_id', 0)->get();
        if($parentCategories->count() > 0){
            $return = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseCollection($parentCategories, '\OlaHub\UserPortal\ResponseHandlers\ItemCategoryForPrequestFormsResponseHandler');
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
    
    public function getAllClassifications(){
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Desginer", 'function_name' => "getAllClassifications"]);
       
        $classifications = \OlaHub\UserPortal\Models\Classification::withoutGlobalScope('country')->get();
        if($classifications->count() > 0){
            $return = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseCollection($classifications, '\OlaHub\UserPortal\ResponseHandlers\ClassificationForPrequestFormsResponseHandler');
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
    
    
    public function addNewDesginer(){
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Desginer", 'function_name' => "addNewDesginer"]);
       
        $validation = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::validateData(\OlaHub\UserPortal\Models\DesignerInvites::$columnsMaping, (array) $this->requestData);
        if (isset($validation['status']) && !$validation['status']) {
            $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'someData', 'code' => 406, 'errorData' => $validation['data']]]);
            $log->saveLogSessionData();
            return response(['status' => false, 'msg' => 'someData', 'code' => 406, 'errorData' => $validation['data']], 200);
        }
        
        
        if (!$this->checkUnique($this->requestData['desginerEmail'])) {
            $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'emailExist', 'code' => 406, 'errorData' => ['desginerEmail' => ['validation.unique.email']]]]);
            $log->saveLogSessionData();
            return response(['status' => false, 'msg' => 'emailExist', 'code' => 406, 'errorData' => ['desginerEmail' => ['validation.unique.email']]], 200);
        }

        if (!$this->checkUnique($this->requestData['desginerPhoneNumber'])) {
            $log->setLogSessionData(['response' => $return]);
            $log->saveLogSessionData();
            return response(['status' => false, 'msg' => 'phoneExist', 'code' => 406, 'errorData' => ['desginerPhoneNumber' => ['validation.unique.phone']]], 200);
        }
        
        

        if (isset($this->requestData['desginerCategories']) && count($this->requestData['desginerCategories']) <= 0) {
            $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'validation.required', 'code' => 406, 'errorData' => ['desginerCategories' => ['validation.required']]]]);
            $log->saveLogSessionData();
            return response(['status' => false, 'msg' => 'validation.required', 'code' => 406, 'errorData' => ['desginerCategories' => ['validation.required']]], 200);
        }
        
        if (isset($this->requestData['desginerCategories']) && count($this->requestData['desginerCategories']) > 3) {
            $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'validation.max.string', 'code' => 406, 'errorData' => ['desginerCategories' => ['validation.max.string']]]]);
            $log->saveLogSessionData();
            return response(['status' => false, 'msg' => 'validation.max.string', 'code' => 406, 'errorData' => ['desginerCategories' => ['validation.max.string']]], 200);
        }
        
        if (isset($this->requestData['desginerClassifications']) && count($this->requestData['desginerClassifications']) <= 0) {
            $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'validation.required', 'code' => 406, 'errorData' => ['desginerClassifications' => ['validation.required']]]]);
            $log->saveLogSessionData();
            return response(['status' => false, 'msg' => 'validation.required', 'code' => 406, 'errorData' => ['desginerClassifications' => ['validation.required']]], 200);
        }
        
        if (isset($this->requestData['desginerClassifications']) && count($this->requestData['desginerClassifications']) > 3) {
            $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'validation.max.string', 'code' => 406, 'errorData' => ['desginerClassifications' => ['validation.max.string']]]]);
            $log->saveLogSessionData();
            return response(['status' => false, 'msg' => 'validation.max.string', 'code' => 406, 'errorData' => ['desginerClassifications' => ['validation.max.string']]], 200);
        }
        
        $desginerInvite = new \OlaHub\UserPortal\Models\DesignerInvites;
        foreach ($this->requestData as $input => $value) {
            if (isset(\OlaHub\UserPortal\Models\DesignerInvites::$columnsMaping[$input])) {
                $desginerInvite->{\OlaHub\UserPortal\Helpers\CommonHelper::getColumnName(\OlaHub\UserPortal\Models\DesignerInvites::$columnsMaping, $input)} = $value ? $value : null;
            }
        }
        $desginerInvite->categories_id = serialize($this->requestData['desginerCategories']);
        $desginerInvite->classifications_id = serialize($this->requestData['desginerClassifications']);
        $desginerInvite->save();
        
        $countryFranchise = \OlaHub\UserPortal\Models\FranchiseDesignerCountry::where('country_id', $this->requestData['desginerCountry'])->first();
        if($countryFranchise){
            $franchise = \OlaHub\UserPortal\Models\Franchise::where('id', $countryFranchise->franchise_id)->first();
            if($franchise->email){
                (new \OlaHub\UserPortal\Helpers\EmailHelper)->sendNewDesginerRequest($franchise, $desginerInvite);
            }
        }
        
        $franchiseNotifications = new \OlaHub\UserPortal\Models\FranchiseNotifications;
        $franchiseNotifications->content = "newBecomeDesigner";
        $franchiseNotifications->module = "designer request";
        $franchiseNotifications->seenBy = [];
        $franchiseNotifications->country = (int)$desginerInvite->country_id;
        $franchiseNotifications->save();
        $log->setLogSessionData(['response' => ['status' => true, 'msg' => 'Data send successfully', 'code' => 200,]]);
        $log->saveLogSessionData();
        return response(['status' => true, 'msg' => 'Data send successfully', 'code' => 200,], 200);
    }
    
    
    private function checkUnique($value = false) {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Desginer", 'function_name' => "checkUnique"]);
       
        if ($value && strlen($value) > 3) {
            $exist = \OlaHub\UserPortal\Models\DesignerInvites::where('designer_email', $value)
                    ->orWhere('designer_phone', $value)
                    ->first();
            if (!$exist) {
                return true;
            }
        } elseif (strlen($value) <= 3) {
            return TRUE;
        }
        return false;
    }


}
