<?php

namespace OlaHub\UserPortal\Models\ManyToMany;

class State extends \OlaHub\UserPortal\Models\OlaHubCommonModels {

    /**
     * The table associated with the model.
     *
     * @var string
     */
    public function __construct(array $attributes = array()) {
        parent::__construct($attributes);
    }
    protected $table = 'states';
    

    static function preRequestData($countryID) {
        $return = [];
        $data = State::where('country_id', $countryID)->get();
        foreach ($data as $one) {
            $return[] = [
                'value' => $one->id,
                'text' => \OlaHub\Helpers\OlaHubCommonHelper::returnCurrentLangField($one, 'name'),
            ];
        }
        return $return;
    }
}
