<?php

namespace OlaHub\UserPortal\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use OlaHub\UserPortal\Models\RegistryModel;
use OlaHub\UserPortal\Models\RegistryUsersModel;

class RegistryController extends BaseController
{

    protected $requestData;
    protected $requestFilter;
    private $registry;
    private $cartData;
    protected $userAgent;

    public function __construct(Request $request)
    {

        $return = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::getRequest($request);
        $this->requestData = $return['requestData'];
        $this->requestFilter = $return['requestFilter'];
        $this->userAgent = $request->header('uniquenum') ? $request->header('uniquenum') : $request->header('user-agent');
    }

    public function createNewRegistry()
    {
        $log = new \OlaHub\UserPortal\Helpers\Logs();
        $userData = app('session')->get('tempData');
        $log->saveLog($userData->id, $this->requestData, ' create_New_Registry');
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['module_name' => "Registry", 'function_name' => "Create new registry"]);

        $validator = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::validateData(RegistryModel::$columnsMaping, (array) $this->requestData);

        if (isset($validator['status']) && !$validator['status']) {
            (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['response' => ['status' => false, 'msg' => 'someData', 'code' => 406, 'errorData' => $validator['data']]]);
            (new \OlaHub\UserPortal\Helpers\LogHelper)->saveLogSessionData();
            return response(['status' => false, 'msg' => 'someData', 'code' => 406, 'errorData' => $validator['data']], 200);
        }

        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Start creating new registry"]);
        $saved = $this->saveRegistry();
        if ($saved) {
            $return = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseItem($this->registry, '\OlaHub\UserPortal\ResponseHandlers\RegistryResponseHandler');
            $return['status'] = TRUE;
            $return['code'] = 200;
            (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['response' => $return]);
            (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_endData" => "End creating new registry"]);
            (new \OlaHub\UserPortal\Helpers\LogHelper)->saveLogSessionData();
            return response($return, 200);
        }
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['response' => ['status' => false, 'msg' => 'InternalServerError', 'code' => 500]]);
        (new \OlaHub\UserPortal\Helpers\LogHelper)->saveLogSessionData();

        return response(['status' => false, 'msg' => 'InternalServerError', 'code' => 500], 200);
    }

    public function updateRegistry()
    {
        $log = new \OlaHub\UserPortal\Helpers\Logs();
        $userData = app('session')->get('tempData');
        $log->saveLog($userData->id, $this->requestData, ' update_Registry');
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['module_name' => "Registry", 'function_name' => "Update Registry"]);
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Start updating Registry"]);

        if (RegistryModel::validateRegistryId($this->requestData)) {
            $this->registry = RegistryModel::where('id', $this->requestData['registryId'])->first();
            if ($this->registry->user_id != app('session')->get('tempID')) {
                (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['response' => ['status' => false, 'msg' => 'NotAuthorizedToUpdateRegistry', 'code' => 400]]);
                (new \OlaHub\UserPortal\Helpers\LogHelper)->saveLogSessionData();
                return response(['status' => false, 'msg' => 'NotAuthorizedToUpdateRegistry', 'code' => 400], 200);
            } elseif ($this->registry->status > 1) {
                (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['response' => ['status' => false, 'msg' => 'NotAllowedToUpdateCompletedRegistry', 'code' => 400]]);
                (new \OlaHub\UserPortal\Helpers\LogHelper)->saveLogSessionData();
                return response(['status' => false, 'msg' => 'NotAllowedToUpdateCompletedRegistry', 'code' => 400], 200);
            }
            foreach ($this->requestData as $input => $value) {
                if (isset(RegistryModel::$columnsMaping[$input])) {
                    $this->registry->{\OlaHub\UserPortal\Helpers\CommonHelper::getColumnName(RegistryModel::$columnsMaping, $input)} = $value;
                }
            }

            if (isset($this->requestData['registryImage'])) {
                $image = (new \OlaHub\UserPortal\Helpers\RegistryHelper)->uploadImage($this->registry, 'image', $this->requestData['registryImage']);
                $this->registry->image = $image;
            }
            if (isset($this->requestData['registryVideo'])) {
                $video = \OlaHub\UserPortal\Helpers\GeneralHelper::uploader($this->requestData['registryVideo'], DEFAULT_IMAGES_PATH . "registries/" . $this->registry->id, "registries/" . $this->registry->id, false);
                if (array_key_exists('path', $video)) {
                    if ($this->registry->video) {
                        $oldImage = $this->registry->video;
                        @unlink(DEFAULT_IMAGES_PATH . '/' . $oldImage);
                    }
                    $this->registry->video = $video['path'];
                }
            }

            $saved = $this->registry->save();

            if ($saved) {

                $return = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseItem($this->registry, '\OlaHub\UserPortal\ResponseHandlers\RegistryResponseHandler');
                $return['status'] = TRUE;
                $return['code'] = 200;
                (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['response' => $return]);
                (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_endData" => "End updating registry"]);
                (new \OlaHub\UserPortal\Helpers\LogHelper)->saveLogSessionData();
                return response($return, 200);
            }
        }
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['response' => ['status' => false, 'msg' => 'someData', 'code' => 406,  'errorData' => ["registryId" => ["validation.required"]]]]);
        (new \OlaHub\UserPortal\Helpers\LogHelper)->saveLogSessionData();
        return response(['status' => false, 'msg' => 'someData', 'code' => 406, 'errorData' => ["registryId" => ["validation.required"]]], 200);
    }

    public function deleteRegistry()
    {
        $log = new \OlaHub\UserPortal\Helpers\Logs();
        $userData = app('session')->get('tempData');
        $log->saveLog($userData->id, $this->requestData, ' delete_Registry');
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['module_name' => "Registry", 'function_name' => "Delete Registry"]);

        if (RegistryModel::validateRegistryId($this->requestData)) {
            $registry = RegistryModel::where('id', $this->requestData['registryId'])->first();
            $participants = RegistryUsersModel::where('registry_id', $this->requestData['registryId'])->get();
            if ($registry) {
                if ($registry->user_id != app('session')->get('tempID')) {
                    (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['response' => ['status' => false, 'msg' => 'NotAuthorizedToDeleteRegistry', 'code' => 400]]);
                    (new \OlaHub\UserPortal\Helpers\LogHelper)->saveLogSessionData();

                    return response(['status' => false, 'msg' => 'NotAuthorizedToDeleteRegistry', 'code' => 400], 200);
                } elseif ($registry->status != 1) {
                    (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['response' => ['status' => false, 'msg' => 'participantsPaied', 'code' => 400]]);
                    (new \OlaHub\UserPortal\Helpers\LogHelper)->saveLogSessionData();

                    return response(['status' => false, 'msg' => 'participantsPaied', 'code' => 400], 200);
                }
            }
            $this->deleteRegistryDetails($registry);
            (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Delete Registry"]);
            $registry->delete();

            foreach ($participants as $Participant) {

                $participantData = \OlaHub\UserPortal\Models\UserModel::withoutGlobalScope('notTemp')->where('id', $Participant->user_id)->first();

                if ($participantData->mobile_no && $participantData->email) {
                    (new \OlaHub\UserPortal\Helpers\EmailHelper)->sendDeletedRegistry($participantData, $registry->title, $registry->user_name);
                    (new \OlaHub\UserPortal\Helpers\SmsHelper)->sendDeletedRegistry($participantData, $registry->title, $registry->user_name);
                } else if ($participantData->mobile_no) {
                    (new \OlaHub\UserPortal\Helpers\SmsHelper)->sendDeletedRegistry($participantData, $registry->title, $registry->user_name);
                } else if ($participantData->email) {
                    (new \OlaHub\UserPortal\Helpers\EmailHelper)->sendDeletedRegistry($participantData, $registry->title, $registry->user_name);
                }
            }
            (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Remove notifications related to registry"]);
            \OlaHub\UserPortal\Models\Notifications::where('type', 'registry')->where('registry_id', $this->requestData['registryId'])->delete();

            (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['response' => ['status' => true, 'msg' => 'registryDeletedSuccessfully', 'code' => 200]]);
            (new \OlaHub\UserPortal\Helpers\LogHelper)->saveLogSessionData();

            return response(['status' => true, 'msg' => 'registryDeletedSuccessfully', 'code' => 200], 200);
        }


        (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['response' => ['status' => false, 'msg' => 'NoData', 'code' => 204]]);
        (new \OlaHub\UserPortal\Helpers\LogHelper)->saveLogSessionData();

        // $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'NoData', 'code' => 204]]);
        // $log->saveLogSessionData();

        return response(['status' => false, 'msg' => 'NoData', 'code' => 204], 200);
    }

    private function deleteRegistryDetails($registry)
    {
        $log = new \OlaHub\UserPortal\Helpers\Logs();
        $userData = app('session')->get('tempData');

        $log->saveLog($userData->id, $this->requestData, ' delete_Registry_Details');

        (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['module_name' => "Registry", 'function_name' => "Delete registry details"]);

        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Delete registry details", "action_startData" => $registry]);
        $items = \OlaHub\UserPortal\Models\RegistryGiftModel::where('registry_id', $registry->id)->where('created_by', app('session')->get('tempID'))->delete();

        //        $cart = \OlaHub\UserPortal\Models\Cart::withoutGlobalScope('countryUser')->where('registry_id', $registry->id)->first();
        //
        //        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Delete gift items related to celebration"]);
        //        if ($cart) {
        //            $cartDetails = \OlaHub\UserPortal\Models\CartItems::withoutGlobalScope('countryUser')->where('shopping_cart_id', $cart->id)->get();
        //            if (count($cartDetails) > 0) {
        //                foreach ($cartDetails as $cartDetail) {
        //                    $cartDetail->delete();
        //                }
        //            }
        //            $cart->delete();
        //        }
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Delete video and image related to registry"]);

        if ($registry->video) {
            @unlink(DEFAULT_IMAGES_PATH . '/' . $registry->video);
        }
        if ($registry->image) {
            @unlink(DEFAULT_IMAGES_PATH . '/' . $registry->image);
        }


        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Delete registry participants related to registry"]);
        if ($registry->registryusers) {
            $registry->registryusers()->delete();
        }
    }

    private function saveRegistry()
    {
        $log = new \OlaHub\UserPortal\Helpers\Logs();
        $userData = app('session')->get('tempData');
        $log->saveLog($userData->id, $this->requestData, ' save_Registry');

        (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['module_name' => "Registry", 'function_name' => "saveRegistry"]);
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "saveRegistry"]);

        $this->registry = new RegistryModel;
        foreach ($this->requestData as $input => $value) {
            if ($input != 'registryImage' && $input != 'registryVideo') {
                if (isset(RegistryModel::$columnsMaping[$input])) {
                    $this->registry->{\OlaHub\UserPortal\Helpers\CommonHelper::getColumnName(RegistryModel::$columnsMaping, $input)} = $value;
                }
            }
        }
        $this->registry->user_id = app('session')->get('tempID');
        $saved = $this->registry->save();
        if ($saved) {
            if (isset($this->requestData['registryImage'])) {
                $image = \OlaHub\UserPortal\Helpers\GeneralHelper::moveImage($this->requestData['registryImage'], "registries/" . $this->registry->id);
                // $image = (new \OlaHub\UserPortal\Helpers\RegistryHelper)->uploadImage($this->registry, 'image', $this->requestData['registryImage']);
                $this->registry->image = $image;
            }
            if (isset($this->requestData['registryVideo'])) {
                $video = \OlaHub\UserPortal\Helpers\GeneralHelper::moveImage($this->requestData['registryVideo'], "registries/" . $this->registry->id);
                // $video = \OlaHub\UserPortal\Helpers\GeneralHelper::uploader($this->requestData['registryVideo'], DEFAULT_IMAGES_PATH . "registries/" . $this->registry->id, "registries/" . $this->registry->id, false);
                $this->registry->video = $video;
            }
            $saved = $this->registry->save();


            return true;
        }
        return false;
    }

    public function ListRegistry()
    {
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['module_name' => "Registry", 'function_name' => "ListRegistry"]);
        $participants = RegistryUsersModel::where('user_id', app('session')->get('tempID'))->get();
        $registriesId = [];
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "List user registries"]);
        if (count($participants) > 0) {
            foreach ($participants as $participant) {
                $registriesId[] = $participant->registry_id;
            }
        }
        $registries = RegistryModel::whereIn('id', $registriesId)
            ->orwhere('user_id', app('session')->get('tempID'))
            ->orderBy('created_at', 'desc')
            ->paginate(10);
        if ($registries) {

            $return = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseCollectionPginate($registries, '\OlaHub\UserPortal\ResponseHandlers\RegistryResponseHandler');
            $return['status'] = true;
            $return['code'] = 200;
            (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['response' => $return]);
            (new \OlaHub\UserPortal\Helpers\LogHelper)->saveLogSessionData();

            return response($return, 200);
        }
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['response' => ['status' => false, 'msg' => 'NoData', 'code' => 204]]);
        (new \OlaHub\UserPortal\Helpers\LogHelper)->saveLogSessionData();

        return response(['status' => false, 'msg' => 'NoData', 'code' => 204], 200);
    }

    public function getOneRegistry()
    {
        //$log = new \OlaHub\UserPortal\Helpers\Logs();
        //$userData = app('session')->get('tempData');

       // $log->saveLog($userData->id, $this->requestData, ' get_One_Registry');
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['module_name' => "Registry", 'function_name' => "Get one Registry"]);

        if (isset($this->requestData['registryId']) && $this->requestData['registryId'] > 0) {
            $registry = RegistryModel::where('id', $this->requestData['registryId'])
                ->where(function ($q) {
                    $q->where('user_id', app('session')->get('tempID'))
                        ->orWhere('is_published', 1);
                })->first();
            // $participant = RegistryUsersModel::where('registry_id', $this->requestData['registryId'])->where('user_id', app('session')->get('tempID'))->first();
            // if (!$participant) {
            //     if ($registry->status == 3 && $registry->user_id == app('session')->get('tempID')) {
            //         (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['response' => ['status' => false, 'msg' => 'authorizedToOpenRegistry', 'code' => 400]]);
            //         (new \OlaHub\UserPortal\Helpers\LogHelper)->saveLogSessionData();

            //         return response(['status' => false, 'msg' => 'authorizedToOpenRegistry', 'code' => 400], 200);
            //     } elseif ($registry->user_id != app('session')->get('tempID')) {
            //         (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['response' => ['status' => false, 'msg' => 'authorizedToOpenRegistry', 'code' => 400]]);
            //         (new \OlaHub\UserPortal\Helpers\LogHelper)->saveLogSessionData();

            //         return response(['status' => false, 'msg' => 'authorizedToOpenRegistry', 'code' => 401], 200);
            //     }
            // }
            // if (empty(app('session')->get('tempID')))
            //     return response(['status' => false, 'msg' => 'authorizedToOpenRegistry', 'code' => 401], 200);
            (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Check registry existance to show its details for user"]);
            if ($registry) {
                (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Show details of selected registry"]);
                $return = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseItem($registry, '\OlaHub\UserPortal\ResponseHandlers\RegistryResponseHandler');
                $return['status'] = true;
                $return['code'] = 200;
                (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['response' => $return]);
                (new \OlaHub\UserPortal\Helpers\LogHelper)->saveLogSessionData();
                return response($return, 200);
            }

            (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['response' => ['status' => false, 'msg' => 'NoData', 'code' => 204]]);
            (new \OlaHub\UserPortal\Helpers\LogHelper)->saveLogSessionData();

            return response(['status' => false, 'msg' => 'NoData', 'code' => 204], 200);
        }
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['response' => ['status' => false, 'msg' => 'NoData', 'code' => 204]]);
        (new \OlaHub\UserPortal\Helpers\LogHelper)->saveLogSessionData();

        return response(['status' => false, 'msg' => 'NoData', 'code' => 204], 200);
    }

    public function publishRegistry()
    {
        $log = new \OlaHub\UserPortal\Helpers\Logs();
        $userData = app('session')->get('tempData');

        $log->saveLog($userData->id, $this->requestData, 'publish_Registry');
        if (isset($this->requestData['registryId']) && $this->requestData['registryId'] > 0) {
            $registry = RegistryModel::where('id', $this->requestData['registryId'])
                ->where('user_id', app('session')->get('tempID'))->first();
            if ($registry) {
                $registry->is_published = 1;
                $registry->save();
                $return = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseItem($registry, '\OlaHub\UserPortal\ResponseHandlers\RegistryResponseHandler');
                $return['status'] = true;
                $return['code'] = 200;
                return response($return, 200);
            }
        }
        return response(['status' => false, 'msg' => 'NoData', 'code' => 204], 200);
    }

    public function registryWishes()
    {
        $log = new \OlaHub\UserPortal\Helpers\Logs();
        $userData = app('session')->get('tempData');

        $log->saveLog($userData->id, $this->requestData, 'registryWishes');
        if (isset($this->requestData['registryId']) && $this->requestData['registryId'] > 0) {
            $registry = RegistryModel::where('id', $this->requestData['registryId'])
                ->where('user_id', app('session')->get('tempID'))->first();
            if ($registry) {
                $mediaData = [];
                $texts = [];
                foreach ($registry->celebrations as $celebration) {
                    foreach ($celebration->celebrationParticipants as $participants) {
                        $type = '';
                        $celebrationContents = \OlaHub\UserPortal\Models\CelebrationContentsModel::where('celebration_id', $celebration->id)
                            ->where('created_by', $participants->id)->get();
                        $participantData = $this->setProfileData($participants->user_id);
                        if ($participants->personal_message) {
                            $texts[] = [
                                "username" => $participantData['username'],
                                "avatar" => $participantData['avatar'],
                                "msg" => $participants->personal_message,
                            ];
                        }
                        foreach ($celebrationContents as $celebrationContent) {
                            $explodedData = explode('.', $celebrationContent->reference);
                            $extension = end($explodedData);
                            if (in_array(strtolower($extension), VIDEO_EXT)) {
                                $type = 'video';
                            } elseif (in_array($extension, IMAGE_EXT)) {
                                $type = 'image';
                            }
                            $mediaData[] = [
                                "username" => $participantData['username'],
                                "avatar" => $participantData['avatar'],
                                "type" => $type,
                                "file" => isset($celebrationContent->reference) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($celebrationContent->reference) : NULL
                            ];
                        }
                    }
                }
                $return['media'] = $mediaData;
                $return['texts'] = $texts;
                $return['status'] = true;
                $return['code'] = 200;
                return response($return, 200);
            }
        }
        return response(['status' => false, 'msg' => 'NoData', 'code' => 204], 200);
    }

    private function setProfileData($user_id)
    {
        $return = [];
        $user = \OlaHub\UserPortal\Models\UserModel::withoutGlobalScope('notTemp')->where('id', $user_id)->first();
        $return["username"] = isset($user->first_name) ? $user->first_name . ' ' . $user->last_name : NULL;
        if (isset($user->profile_picture)) {
            $return['avatar'] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($user->profile_picture);
        } else {
            $return['avatar'] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl(false);
        }
        return $return;
    }
}
