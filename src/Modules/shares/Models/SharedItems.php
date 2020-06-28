<?php

namespace OlaHub\UserPortal\Models;

use Illuminate\Database\Eloquent\Model;

class SharedItems extends Model
{

    public function __construct(array $attributes = array())
    {
        parent::__construct($attributes);
        static::addGlobalScope('currentUser', function ($query) {
            $query->where('user_id', app('session')->get('tempID'));
        });
    }

    protected $table = 'shared_items';
    static $columnsMaping = [
        'itemID' => [
            'column' => 'item_id',
            'type' => 'number',
            'relation' => false,
            'validation' => 'required|numeric'
        ],
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($query) {
            $query->user_id = app('session')->get('tempID');
        });
    }

    public function itemsMainData()
    {
        return $this->belongsTo('OlaHub\UserPortal\Models\CatalogItem', 'item_id');
    }
}
