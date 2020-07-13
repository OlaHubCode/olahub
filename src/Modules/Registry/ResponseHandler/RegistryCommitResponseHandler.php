<?php

namespace OlaHub\UserPortal\ResponseHandlers;

use OlaHub\UserPortal\Models\RegistryModel;
use League\Fractal;

class RegistryCommitResponseHandler extends Fractal\TransformerAbstract {

    private $return;
    private $data;

    public function transform(RegistryModel $data) {
        $this->data = $data;
        $this->setDefaultData();
        $this->setDates();
        return $this->return;
    }

    private function setDefaultData() {
        $participants = $this->data->registryusers;
        $participantValue = [];
        foreach ($participants as $participant) {
            $user = \OlaHub\UserPortal\Models\UserModel::where('id',$participant->user_id)->first();
            $participantValue[] = [
                'participant' => isset($participant->id) ? $participant->id : 0,
                'participantName' => isset($user->first_name) ? $user->first_name.' '. $user->last_name: NULL,
            ];
            
        }
        $this->return["participants"] = $participantValue;
    }
    
    private function setDates() {
        $this->return["created"] = isset($this->data->created_at) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::convertStringToDate($this->data->created_at) : NULL;
        $this->return["creator"] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::defineRowCreator($this->data);
    }
    

}
