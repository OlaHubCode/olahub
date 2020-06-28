<?php

namespace OlaHub\UserPortal\Models;
use Illuminate\Database\Eloquent\SoftDeletes;

class Language extends \Illuminate\Database\Eloquent\Model {

//    use SoftDeletes;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    public function __construct(array $attributes = array()) {
        parent::__construct($attributes);
    }
    protected $table = 'languages';
    
    protected $requestValidationRules = [
        'name' => "required|max:4000|json",
        'code' => "max:30",
        'default_locale' => "required|max:255",
        'direction' => "max:3",
        'is_published' => "in:1,0",
    ];
    
    public function countries(){
        return $this->hasMany('OlaHub\UserPortal\Models\Country', 'language_id');
    }
}
