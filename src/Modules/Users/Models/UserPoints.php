<?php

namespace OlaHub\UserPortal\Models;

use Illuminate\Database\Eloquent\Model;

class UserPoints extends Model
{

    protected $connection = 'mysql';
    protected $table = 'user_points_archive';
    
    public function __construct(array $attributes = array())
    {
        parent::__construct($attributes);

        static::addGlobalScope('currentUser', function (\Illuminate\Database\Eloquent\Builder $builder) {
            $builder->where('user_id', app('session')->get('tempID'));
        });

        static::creating(function ($model) {
            if (app('session')->get('tempID') > 0) {
                $model->user_id = app('session')->get('tempID');
            }
        });
    }
}
