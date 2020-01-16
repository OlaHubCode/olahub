<?php

namespace OlaHub\UserPortal\ResponseHandlers;

use OlaHub\UserPortal\Models\CelebrationModel;
use League\Fractal;

class CelebrationCommitResponseHandler extends Fractal\TransformerAbstract {

    private $return;
    private $data;

    public function transform(CelebrationModel $data) {
        $this->data = $data;
        $this->setDefaultData();
        $this->setDates();
        return $this->return;
    }

    private function setDefaultData() {
        $participants = $this->data->celebrationParticipants;
        $participantValue = [];
        foreach ($participants as $participant) {
            $user = \OlaHub\UserPortal\Models\UserModel::where('id',$participant->user_id)->first();
            $participantValue[] = [
                'participant' => isset($participant->id) ? $participant->id : 0,
                'participantName' => isset($user->first_name) ? $user->first_name.' '. $user->last_name: NULL,
                'amountToPay' => isset($participant->amount_to_pay) ? $participant->amount_to_pay : NULL,
            ];
            
        }
        $this->return["participants"] = $participantValue;
    }
    
    private function setDates() {
        $this->return["created"] = isset($this->data->created_at) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::convertStringToDate($this->data->created_at) : NULL;
        $this->return["creator"] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::defineRowCreator($this->data);
        $this->return["updated"] = isset($this->data->updated_at) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::convertStringToDate($this->data->updated_at) : NULL;
        $this->return["updater"] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::defineRowCreator($this->data);
    }
    

}
