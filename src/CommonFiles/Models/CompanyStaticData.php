<?php

/**
 * Countries model 
 * To connect with database and make all queries  
 * all functions return with eloqouent object or array of objects
 * 
 * @copyright (c) 2018, OlaHub LLC
 * @version 1.0.0 
 */

namespace OlaHub\UserPortal\Models;
use Illuminate\Database\Eloquent\SoftDeletes;


class CompanyStaticData extends \Illuminate\Database\Eloquent\Model {

    use SoftDeletes;

    public function __construct(array $attributes = array()) {
        parent::__construct($attributes);
    }

    protected $table = 'company_static_data';

    protected static function boot() {
        parent::boot();

        static::addGlobalScope('currentCountry', function (\Illuminate\Database\Eloquent\Builder $builder) {
            $builder->where('country_id', app('session')->get('def_country')->id);
        });
    }

    public function scopeOfType($query, $type, $secondType) {
        return $query->where('type', $type)->where('second_type', $secondType);
    }

}
