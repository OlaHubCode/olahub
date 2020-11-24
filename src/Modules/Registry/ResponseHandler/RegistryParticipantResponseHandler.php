<?php

namespace OlaHub\UserPortal\ResponseHandlers;

use OlaHub\UserPortal\Models\RegistryUsersModel;
use League\Fractal;

class RegistryParticipantResponseHandler extends Fractal\TransformerAbstract
{

    private $return;
    private $data;

    public function transform(RegistryUsersModel $data)
    {
        $this->data = $data;
        $this->setDefaultData();
        $this->setProfileData();
        return $this->return;
    }

    private function setDefaultData()
    {
        $registry = \OlaHub\UserPortal\Models\RegistryModel::where('id', $this->data->registry_id)->first();
        $registryOwner = \OlaHub\UserPortal\Models\UserModel::withoutGlobalScope('notTemp')->where('id', $registry->user_id)->first();
        $this->return = [
            "participant" => isset($this->data->id) ? $this->data->id : 0,
            "participantId" => isset($this->data->user_id) ? $this->data->user_id : 0,
            "registryId" => isset($registry) ? $registry->id : NULL,
            "participantLogged" => $this->data->user_id == app('session')->get('tempID') ? TRUE : FALSE,
            "registryOwnerSlug" => isset($registryOwner->profile_url) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::checkSlug($registryOwner, "profile_url", $registryOwner->first_name . " " . $registryOwner->last_name, ".") : NULL,
        ];
    }

    private function setProfileData()
    {
        $user = \OlaHub\UserPortal\Models\UserModel::withoutGlobalScope('notTemp')->where('id', $this->data->user_id)->first();
        $this->return["participantName"] = isset($user->first_name) ? $user->first_name . ' ' . $user->last_name : NULL;
        $this->return["participantSlug"] = isset($user->profile_url) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::checkSlug($user, "profile_url", $this->return["participantName"], ".") : NULL;
        if (isset($user->profile_picture)) {
            $this->return['participantProfilePhoto'] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($user->profile_picture);
        } else {
            $this->return['participantProfilePhoto'] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl(false);
        }
    }
}
