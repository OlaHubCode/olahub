<?php

/**
 * StaticPages model 
 * To connect with database and make all queries  
 * all functions return with eloqouent object or array of objects
 * 
 * @author Mohamed EL-Absy <mohamed.elabsy@yahoo.com>
 * @copyright (c) 2018, OlaHub LLC
 * @version 1.0.0 
 */

namespace OlaHub\UserPortal\Models;

use Illuminate\Database\Eloquent\Model;

class StaticPages extends Model {

    public function __construct(array $attributes = array()) {
        parent::__construct($attributes);

        static::addGlobalScope('pageCountry', function (\Illuminate\Database\Eloquent\Builder $builder) {
            $builder->where('country_id', app('session')->get('def_country')->id);
            $builder->where('second_type', 'user');
        });
    }

    protected $table = 'company_static_data';

}
