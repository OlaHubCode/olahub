<?php

namespace OlaHub\UserPortal\Models;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;

class FranchiseNotifications extends Eloquent {

    protected $connection = 'mongo';
    protected $collection = 'franchise_notifications';
    
}
