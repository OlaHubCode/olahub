<?php

namespace OlaHub\UserPortal\Models;

use Jenssegers\Mongodb\Eloquent\HybridRelations;
use Illuminate\Database\Eloquent\Model;

class UserPoints extends Model {

    use HybridRelations;

    public function __construct(array $attributes = array()) {
        parent::__construct($attributes);

        static::addGlobalScope('currentUser', function (\Illuminate\Database\Eloquent\Builder $builder) {
            $builder->where('user_id', app('session')->get('tempID'));
        });

        static::creating(function($model) {
            if (app('session')->get('tempID') > 0) {
                $model->user_id = app('session')->get('tempID');
            }
        });
    }

    protected $connection = 'mysql';
    protected $table = 'user_points_archive';

}
