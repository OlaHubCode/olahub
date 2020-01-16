<?php

namespace OlaHub\UserPortal\Models;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;

class NotificationMongo extends Eloquent {

    protected $connection = 'mongo';
    protected $collection = 'notifications';


}
