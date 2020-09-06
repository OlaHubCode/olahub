<?php

namespace OlaHub\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AdStatistics extends Model {

    use SoftDeletes;
    protected $table = 'campaign_statistics';
}
