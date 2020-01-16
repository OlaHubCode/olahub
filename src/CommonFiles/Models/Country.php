<?php

/**
 * Countries model 
 * To connect with database and make all queries  
 * all functions return with eloqouent object or array of objects
 * 
 * @author Mohamed EL-Absy <mohamed.elabsy@yahoo.com>
 * @copyright (c) 2018, OlaHub LLC
 * @version 1.0.0 
 */

namespace OlaHub\UserPortal\Models;

class Country extends \Illuminate\Database\Eloquent\Model {

    public function __construct(array $attributes = array()) {
        parent::__construct($attributes);
    }

    protected $table = 'countries';
    protected $guarded = array('created_at', 'updated_at', 'deleted_at', 'id','name','two_letter_iso_code','three_letter_iso_code','language_id','currency_id','is_published','is_supported');
    
    protected $columnsMaping = [
        'countryName' => [
            'column' => 'name',
            'type' => 'language',
            'relation' => false,
            'validation' => 'max:4000'
        ]
    ];
    
    protected static function boot() {
        parent::boot();

        static::addGlobalScope('countrySupported', function (\Illuminate\Database\Eloquent\Builder $builder) {
            $builder->where('is_supported', '1');
            $builder->where('is_published', '1');
        });
    }
    
    public function currencyData() {
        return $this->belongsTo('OlaHub\UserPortal\Models\Currency', 'currency_id');
    }

    public function languageData() {
        return $this->belongsTo('OlaHub\UserPortal\Models\Language', 'language_id');
    }

    public function statesRelation() {
        return $this->hasMany('OlaHub\UserPortal\Models\State', 'country_id');
    }
}