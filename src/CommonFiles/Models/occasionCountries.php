<?php

namespace OlaHub\UserPortal\Models\ManyToMany;

use Illuminate\Database\Eloquent\Model;

class occasionCountries extends Model {

    /**
     * The table associated with the model.
     *
     * @var string
     */
    public function __construct(array $attributes = array()) {
        parent::__construct($attributes);
        $this->statusColumn = 'occasionStatus';
    }

    public static function boot() {
        parent::boot();
        //static::addGlobalScope(new \OlaHub\Scopes\publishScope);
    }

    protected $table = 'country_occasion_types';

    public function countryData() {
        return $this->belongsTo('OlaHub\UserPortal\Models\Country', 'country_id');
    }

    public function occasionData() {
        return $this->belongsTo('OlaHub\UserPortal\Models\Occasion', 'occasion_type_id');
    }

}
