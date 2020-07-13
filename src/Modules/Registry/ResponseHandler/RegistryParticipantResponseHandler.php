<?php

namespace OlaHub\UserPortal\ResponseHandlers;

use OlaHub\UserPortal\Models\RegistryUsersModel;
use League\Fractal;

class RegistryParticipantResponseHandler extends Fractal\TransformerAbstract {

    private $return;
    private $data;

    public function transform(RegistryUsersModel $data) {
        $this->data = $data;
        $this->setDefaultData();
        $this->setProfileData();
//        $this->setCelebrationContent();
//        $this->setParticipantStatus();
        return $this->return;
    }

    private function setDefaultData() {
        $registry = \OlaHub\UserPortal\Models\RegistryModel::where('id', $this->data->registry_id)->first();
        $registryOwner = \OlaHub\UserPortal\Models\UserModel::withoutGlobalScope('notTemp')->where('id', $registry->user_id)->first();
        $this->return = [
            "participant" => isset($this->data->id) ? $this->data->id : 0,
            "participantId" => isset($this->data->user_id) ? $this->data->user_id : 0,
//            "participantAmountToPay" => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setPrice((double) $this->data->amount_to_pay, true, $celebration->country_id),
//            "participantPaymentStatus" => $this->data->payment_status,
//            "participantWishText" => isset($this->data->personal_message) ? $this->data->personal_message : "",
            "registryId" => isset($registry) ? $registry->id : NULL,
            "participantLogged" => $this->data->user_id == app('session')->get('tempID') ? TRUE : FALSE,
            "registryOwnerSlug" => isset($registryOwner->profile_url) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::checkSlug($registryOwner, "profile_url", $registryOwner->first_name . " " . $registryOwner->last_name, ".") : NULL,
        ];
    }

//    private function setParticipantStatus() {
//        if ($this->data->is_creator) {
//            $this->return["participantStatus"] = "Creator";
//        } else if ($this->data->is_approved) {
//            $this->return["participantStatus"] = "Joined";
//        } else {
//            $this->return["participantStatus"] = "Pending";
//        }
//    }

    private function setProfileData() {
        $user = \OlaHub\UserPortal\Models\UserModel::withoutGlobalScope('notTemp')->where('id', $this->data->user_id)->first();
        $this->return["participantName"] = isset($user->first_name) ? $user->first_name . ' ' . $user->last_name : NULL;
        $this->return["participantSlug"] = isset($user->profile_url) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::checkSlug($user, "profile_url", $this->return["participantName"], ".") : NULL;
        if (isset($user->profile_picture)) {
            $this->return['participantProfilePhoto'] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($user->profile_picture);
        } else {
            $this->return['participantProfilePhoto'] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl(false);
        }
    }

//    private function setCelebrationContent() {
//        $mediaData = [];
//        $type = '';
//        $celebrationContents = \OlaHub\UserPortal\Models\CelebrationContentsModel::where('celebration_id', $this->data->celebration_id)->where('created_by', $this->data->id)->get();
//        foreach ($celebrationContents as $celebrationContent) {
//            $explodedData = explode('.', $celebrationContent->reference);
//            $extension = end($explodedData);
//            if (in_array(strtolower($extension), VIDEO_EXT)) {
//                $type = 'video';
//            } elseif (in_array($extension, IMAGE_EXT)) {
//                $type = 'image';
//            }
//            $mediaData[] = [
//                "mediaId" => $celebrationContent->id,
//                "mediaOwner" => $this->data->user_id,
//                "mediaType" => $type,
//                "participantWishVideo" => isset($celebrationContent->reference) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($celebrationContent->reference) : NULL
//            ];
//        }
//        $this->return['participantWishMedia'] = $mediaData;
//    }

}
