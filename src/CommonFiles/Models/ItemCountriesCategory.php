<?php

namespace OlaHub\UserPortal\Models\ManyToMany;

class ItemCountriesCategory extends \Illuminate\Database\Eloquent\Model {

    /**
     * The table associated with the model.
     *
     * @var string
     */
    public function __construct(array $attributes = array()) {
        parent::__construct($attributes);
        $this->statusColumn = 'catStatus';
    }

    protected $table = 'country_item_categories';

    public function countryData() {
        return $this->belongsTo('OlaHub\UserPortal\Models\Country', 'country_id');
    }

    public function categoryData() {
        return $this->belongsTo('OlaHub\UserPortal\Models\ItemCategory', 'category_id');
    }

}
