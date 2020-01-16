<?php

namespace OlaHub\UserPortal\Models;

class FranchiseDesignerCountry extends \Illuminate\Database\Eloquent\Model {

    //use \Illuminate\Database\Eloquent\SoftDeletes;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    public function __construct(array $attributes = array()) {
        parent::__construct($attributes);
    }

    protected $table = 'franchise_designer_country';

}
