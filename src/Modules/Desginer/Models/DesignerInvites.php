<?php

namespace OlaHub\UserPortal\Models;

class DesignerInvites extends \Illuminate\Database\Eloquent\Model {

    //use \Illuminate\Database\Eloquent\SoftDeletes;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    public function __construct(array $attributes = array()) {
        parent::__construct($attributes);
    }

    protected $table = 'designer_invites';
    static $columnsMaping = [
        'desginerName' => [
            'column' => 'designer_name',
            'type' => 'string',
            'relation' => false,
            'validation' => 'required|max:50'
        ],
        'desginerCountry' => [
            'column' => 'country_id',
            'type' => 'string',
            'relation' => false,
            'validation' => 'required|exists:countries,id'
        ],
        'desginerPhoneNumber' => [
            'column' => 'designer_phone',
            'type' => 'string',
            'relation' => false,
            'validation' => 'max:20'
        ],
        'desginerEmail' => [
            'column' => 'designer_email',
            'type' => 'string',
            'relation' => false,
            'validation' => 'max:50|email'
        ],
    ];

}
