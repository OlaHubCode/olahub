<?php

/**
 * MerBankInfos routes
 * Handling URL requests with method type to send to Controller
 * 
 * @copyright (c) 2018, OlaHub LLC
 * @version 1.0.0
 */

/*
 * List items APIs
 */

$router->group([
    'prefix' => basename(strtolower(dirname(__DIR__)))
        ], function () use($router) {
    $router->post('/', 'OlaHubItemController@getRamiItems');
    $router->post('sitemap', 'OlaHubLandingPageController@getItemsSitemap');
    // $router->post('/', 'OlaHubItemController@getItems');
    $router->post('voucher', 'OlaHubItemController@getVoucherItems');
    $router->post('ads', 'OlaHubAdvertisesController@getAdsData');
    $router->post('classifications', 'OlaHubHeaderMenuController@getClassificationsData');
    $router->post('categories', 'OlaHubHeaderMenuController@getCategoriesData');
    $router->post('brands', 'OlaHubHeaderMenuController@getBrandsData');
    $router->post('trending', 'OlaHubLandingPageController@getTrendingData');
    $router->post('offers', 'OlaHubLandingPageController@getMostOfferData');
    $router->post('homeOccasions', 'OlaHubLandingPageController@getOccasionsData');
    $router->post('homeInterests', 'OlaHubLandingPageController@getInterestsData');
    $router->post('uploadCustomImage', 'OlaHubItemController@uploadCustomImage');
    $router->post('offerItemsPage', 'OlaHubItemController@getOfferItemsPage');
    $router->post('offerItemsPage/attribute', 'OlaHubItemController@getOfferItemsPageAttribute');
    $router->post('offerItemsPage/categories', 'OlaHubItemController@getOfferItemsPageCategories');
    $router->post('offerItemsPage/categories/{all:\ball\b}', 'OlaHubItemController@getOfferItemsPageCategories');

    /*
     * Filters (Left side filters)
     */
    
    $router->group([
        'prefix' => 'filters'
            ], function () use($router) {

        // Filter paginated routes
        $router->post('brands', 'OlaHubItemController@getItemFiltersBrandData');
        $router->post('attributes', 'OlaHubItemController@getItemFiltersAttrsData');
        $router->post('classifications', 'OlaHubItemController@getItemFiltersClassessData');
        $router->post('categories', 'OlaHubItemController@getRamiCatsData');
        // $router->post('categories', 'OlaHubItemController@getItemFiltersCatsData');
        $router->post('occasions', 'OlaHubItemController@getItemFiltersOccasionData');
        $router->post('mayAlsoLike', 'OlaHubItemController@getAlsoLikeItems');
        $router->post('selectedAttributes', 'OlaHubItemController@getSelectedAttributes');

        // Filter All routes
        $router->post('brands/{all:\ball\b}', 'OlaHubItemController@getItemFiltersBrandData');
        $router->post('attributes/{all:\ball\b}', 'OlaHubItemController@getItemFiltersAttrsData');
        $router->post('classifications/{all:\ball\b}', 'OlaHubItemController@getItemFiltersClassessData');
        $router->post('categories/{all:\ball\b}', 'OlaHubItemController@getItemFiltersCatsData');
        $router->post('occasions/{all:\ball\b}', 'OlaHubItemController@getItemFiltersOccasionData');
    });
    
    
    $router->group([
        'middleware' => ['checkAuth']
            ], function () use($router) {
        $router->post('reviews/add', 'OlaHubItemReviewsController@addReview');
    });
    
});

/*
 * One item APIs
 */

$router->group([
    'prefix' => 'item/{slug}'
        ], function () use($router) {
    $router->post('/', 'OlaHubItemController@getOneItem');
    $router->post('/attribute', 'OlaHubItemController@getOneItemAttrsData');
    $router->post('related', 'OlaHubItemController@getOneItemRelatedItems');
    $router->post('reviews', 'OlaHubItemReviewsController@getReviews');

    
});

