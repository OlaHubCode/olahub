<?php

namespace OlaHub\UserPortal\Models;

use Illuminate\Database\Eloquent\Model;

class LikedItems extends Model {

    public function __construct(array $attributes = array()) {
        parent::__construct($attributes);
        static::addGlobalScope('currentUser', function ($query) {
            $query->where('user_id', app('session')->get('tempID'));
        });
    }

    protected $table = 'liked_items';
    static $columnsMaping = [
        'itemID' => [
            'column' => 'item_id',
            'type' => 'number',
            'relation' => false,
            'validation' => 'required|numeric'
        ],
    ];

    protected static function boot() {
        parent::boot();

        static::addGlobalScope('like', function (\Illuminate\Database\Eloquent\Builder $builder) {
            $builder->where('type', 'like');
        });

        static::saving(function ($query) {
            $query->type = 'like';
        });
    }

    public function itemsMainData() {
        return $this->belongsTo('OlaHub\UserPortal\Models\CatalogItem', 'item_id');
    }

}
