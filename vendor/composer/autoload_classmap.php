<?php

// autoload_classmap.php @generated by Composer

$vendorDir = dirname(dirname(__FILE__));
$baseDir = dirname($vendorDir);

return array(
    'App\\Mail\\EmailFunction' => $baseDir . '/src/Mail/EmailFunction.php',
    'Laravel\\Lumen\\Console\\Commands\\createModuleCommand' => $baseDir . '/src/Console/Commands/createModuleCommand.php',
    'OlaHub\\Exceptions\\Handler' => $baseDir . '/src/Exceptions/Handler.php',
    'OlaHub\\Helpers\\DTemplatesHelper' => $baseDir . '/src/Console/Commands/templatesData/Utilities/DTemplatesHelper.php',
    'OlaHub\\Models\\AdSlots' => $baseDir . '/src/Modules/Announcement/Models/AdSlots.php',
    'OlaHub\\Models\\AdSlotsCountries' => $baseDir . '/src/Modules/Announcement/Models/AdSlotsCountries.php',
    'OlaHub\\Models\\AdStatistics' => $baseDir . '/src/Modules/Announcement/Models/AdStatistics.php',
    'OlaHub\\Models\\Ads' => $baseDir . '/src/Modules/Announcement/Models/Ads.php',
    'OlaHub\\Models\\AdsMongo' => $baseDir . '/src/Modules/Announcement/Models/AdsMongo.php',
    'OlaHub\\Repositories\\DTemplatesRepository' => $baseDir . '/src/Console/Commands/templatesData/Repositories/DTemplatesRepository.php',
    'OlaHub\\Services\\DTemplatesServices' => $baseDir . '/src/Console/Commands/templatesData/Services/DTemplatesServices.php',
    'OlaHub\\UserPortal\\Console\\Kernel' => $baseDir . '/src/Console/Kernel.php',
    'OlaHub\\UserPortal\\Controllers\\AdsController' => $baseDir . '/src/Modules/Announcement/Controllers/AdsController.php',
    'OlaHub\\UserPortal\\Controllers\\CalendarController' => $baseDir . '/src/Modules/Calendar/Controllers/CalendarController.php',
    'OlaHub\\UserPortal\\Controllers\\CelebrationContentsController' => $baseDir . '/src/Modules/Celebration/Controllers/CelebrationContentsController.php',
    'OlaHub\\UserPortal\\Controllers\\CelebrationController' => $baseDir . '/src/Modules/Celebration/Controllers/CelebrationController.php',
    'OlaHub\\UserPortal\\Controllers\\CronController' => $baseDir . '/src/Modules/Crons/Controllers/CronController.php',
    'OlaHub\\UserPortal\\Controllers\\DTemplatesController' => $baseDir . '/src/Console/Commands/templatesData/Controllers/DTemplatesController.php',
    'OlaHub\\UserPortal\\Controllers\\DTemplatesTrashController' => $baseDir . '/src/Console/Commands/templatesData/Controllers/DTemplatesTrashController.php',
    'OlaHub\\UserPortal\\Controllers\\FriendController' => $baseDir . '/src/Modules/Profile/Controllers/FriendController.php',
    'OlaHub\\UserPortal\\Controllers\\GiftController' => $baseDir . '/src/Modules/Celebration/Controllers/GiftController.php',
    'OlaHub\\UserPortal\\Controllers\\MainController' => $baseDir . '/src/Modules/Groups/Controllers/MainController.php',
    'OlaHub\\UserPortal\\Controllers\\OlaHubAdvertisesController' => $baseDir . '/src/Modules/Items/Controllers/OlaHubAdvertisesController.php',
    'OlaHub\\UserPortal\\Controllers\\OlaHubCartController' => $baseDir . '/src/Modules/Cart/Controllers/OlaHubCartController.php',
    'OlaHub\\UserPortal\\Controllers\\OlaHubCouponsController' => $baseDir . '/src/Modules/Coupons/Controllers/OlaHubCouponsController.php',
    'OlaHub\\UserPortal\\Controllers\\OlaHubDesginerController' => $baseDir . '/src/Modules/Desginer/Controllers/OlaHubDesginerController.php',
    'OlaHub\\UserPortal\\Controllers\\OlaHubGeneralController' => $baseDir . '/src/CommonFiles/Controllers/OlaHubGeneralController.php',
    'OlaHub\\UserPortal\\Controllers\\OlaHubGuestController' => $baseDir . '/src/Modules/Users/Controllers/OlaHubGuestController.php',
    'OlaHub\\UserPortal\\Controllers\\OlaHubHeaderMenuController' => $baseDir . '/src/Modules/Items/Controllers/OlaHubHeaderMenuController.php',
    'OlaHub\\UserPortal\\Controllers\\OlaHubItemController' => $baseDir . '/src/Modules/Items/Controllers/OlaHubItemController.php',
    'OlaHub\\UserPortal\\Controllers\\OlaHubItemReviewsController' => $baseDir . '/src/Modules/Items/Controllers/OlaHubItemReviewsController.php',
    'OlaHub\\UserPortal\\Controllers\\OlaHubLandingPageController' => $baseDir . '/src/Modules/Items/Controllers/OlaHubLandingPageController.php',
    'OlaHub\\UserPortal\\Controllers\\OlaHubLikesController' => $baseDir . '/src/Modules/Likes/Controllers/OlaHubLikesController.php',
    'OlaHub\\UserPortal\\Controllers\\OlaHubPaymentsCallbackController' => $baseDir . '/src/Modules/Payments/Controllers/OlaHubPaymentsCallbackController.php',
    'OlaHub\\UserPortal\\Controllers\\OlaHubPaymentsMainController' => $baseDir . '/src/Modules/Payments/Controllers/OlaHubPaymentsMainController.php',
    'OlaHub\\UserPortal\\Controllers\\OlaHubPaymentsPrepareController' => $baseDir . '/src/Modules/Payments/Controllers/OlaHubPaymentsPrepareController.php',
    'OlaHub\\UserPortal\\Controllers\\OlaHubPostController' => $baseDir . '/src/Modules/Posts/Controllers/OlaHubPostController.php',
    'OlaHub\\UserPortal\\Controllers\\OlaHubUserController' => $baseDir . '/src/Modules/Users/Controllers/OlaHubUserController.php',
    'OlaHub\\UserPortal\\Controllers\\OlaHubWishListsController' => $baseDir . '/src/Modules/WishLists/Controllers/OlaHubWishListsController.php',
    'OlaHub\\UserPortal\\Controllers\\ParticipantController' => $baseDir . '/src/Modules/Celebration/Controllers/ParticipantController.php',
    'OlaHub\\UserPortal\\Controllers\\PurchasedItemsController' => $baseDir . '/src/Modules/MyPurshasedItems/Controllers/PurchasedItemsController.php',
    'OlaHub\\UserPortal\\Events\\OlaHubCommonEvent' => $baseDir . '/src/Events/OlaHubCommonEvent.php',
    'OlaHub\\UserPortal\\Helpers\\BillsHelper' => $baseDir . '/src/CommonFiles/Utilities/BillsHelper.php',
    'OlaHub\\UserPortal\\Helpers\\CartHelper' => $baseDir . '/src/Modules/Cart/Utilities/CartHelper.php',
    'OlaHub\\UserPortal\\Helpers\\CelebrationHelper' => $baseDir . '/src/Modules/Celebration/Utilities/CelebrationHelper.php',
    'OlaHub\\UserPortal\\Helpers\\CommonHelper' => $baseDir . '/src/CommonFiles/Utilities/CommonHelper.php',
    'OlaHub\\UserPortal\\Helpers\\CouponsHelper' => $baseDir . '/src/Modules/Coupons/Utilities/CouponsHelper.php',
    'OlaHub\\UserPortal\\Helpers\\EmailHelper' => $baseDir . '/src/CommonFiles/Utilities/EmailHelper.php',
    'OlaHub\\UserPortal\\Helpers\\ItemHelper' => $baseDir . '/src/Modules/Items/Utilities/ItemHelper.php',
    'OlaHub\\UserPortal\\Helpers\\OlaHubCommonHelper' => $baseDir . '/src/Utilities/OlaHubCommonHelper.php',
    'OlaHub\\UserPortal\\Helpers\\PaymentHelper' => $baseDir . '/src/Modules/Payments/Utilities/PaymentHelper.php',
    'OlaHub\\UserPortal\\Helpers\\SmsHelper' => $baseDir . '/src/CommonFiles/Utilities/SmsHelper.php',
    'OlaHub\\UserPortal\\Helpers\\UserHelper' => $baseDir . '/src/Modules/Users/Utilities/UserHelper.php',
    'OlaHub\\UserPortal\\Helpers\\UserShippingAddressHelper' => $baseDir . '/src/Modules/Users/Utilities/UserShippingAddressHelper.php',
    'OlaHub\\UserPortal\\Helpers\\WishListHelper' => $baseDir . '/src/Modules/WishLists/Utilities/WishListHelper.php',
    'OlaHub\\UserPortal\\Jobs\\OlaHubCommonJob' => $baseDir . '/src/Jobs/OlaHubCommonJob.php',
    'OlaHub\\UserPortal\\Libraries\\APIAlfresco' => $baseDir . '/src/Utilities/alfresco/APIAlfresco.php',
    'OlaHub\\UserPortal\\Libraries\\CmisConstraintException' => $baseDir . '/src/Utilities/alfresco/cmisRepWrapper.php',
    'OlaHub\\UserPortal\\Libraries\\CmisInvalidArgumentException' => $baseDir . '/src/Utilities/alfresco/cmisRepWrapper.php',
    'OlaHub\\UserPortal\\Libraries\\CmisNotImplementedException' => $baseDir . '/src/Utilities/alfresco/cmisRepWrapper.php',
    'OlaHub\\UserPortal\\Libraries\\CmisNotSupportedException' => $baseDir . '/src/Utilities/alfresco/cmisRepWrapper.php',
    'OlaHub\\UserPortal\\Libraries\\CmisObjectNotFoundException' => $baseDir . '/src/Utilities/alfresco/cmisRepWrapper.php',
    'OlaHub\\UserPortal\\Libraries\\CmisPermissionDeniedException' => $baseDir . '/src/Utilities/alfresco/cmisRepWrapper.php',
    'OlaHub\\UserPortal\\Libraries\\CmisRuntimeException' => $baseDir . '/src/Utilities/alfresco/cmisRepWrapper.php',
    'OlaHub\\UserPortal\\Libraries\\ImageReader' => $baseDir . '/src/Utilities/ImageReader.php',
    'OlaHub\\UserPortal\\Libraries\\InflectLibrary' => $baseDir . '/src/Utilities/InflectLibrary.php',
    'OlaHub\\UserPortal\\Libraries\\LoginSystem' => $baseDir . '/src/Utilities/LoginSystem.php',
    'OlaHub\\UserPortal\\Libraries\\OlaHubNotificationHelper' => $baseDir . '/src/Utilities/OlaHubNotificationHelper.php',
    'OlaHub\\UserPortal\\Libraries\\SendEmails' => $baseDir . '/src/Utilities/SendEmail.php',
    'OlaHub\\UserPortal\\Libraries\\cmisRepWrapper' => $baseDir . '/src/Utilities/alfresco/cmisRepWrapper.php',
    'OlaHub\\UserPortal\\Libraries\\cmisService' => $baseDir . '/src/Utilities/alfresco/cmisService.php',
    'OlaHub\\UserPortal\\Listeners\\OlaHubCommonListener' => $baseDir . '/src/Listeners/OlaHubCommonListener.php',
    'OlaHub\\UserPortal\\Middlewares\\KillVarsMiddleware' => $baseDir . '/src/Middleware/KillVarsMiddleware.php',
    'OlaHub\\UserPortal\\Middlewares\\LocaleMiddleware' => $baseDir . '/src/Middleware/LocaleMiddleware.php',
    'OlaHub\\UserPortal\\Middlewares\\LogMiddleware' => $baseDir . '/src/Middleware/LogMiddleware.php',
    'OlaHub\\UserPortal\\Middlewares\\RequestTypeMiddleware' => $baseDir . '/src/Middleware/RequestTypeMiddleware.php',
    'OlaHub\\UserPortal\\Models\\AttrValue' => $baseDir . '/src/Modules/Items/Models/AttrValue.php',
    'OlaHub\\UserPortal\\Models\\Attribute' => $baseDir . '/src/Modules/Items/Models/Attribute.php',
    'OlaHub\\UserPortal\\Models\\Brand' => $baseDir . '/src/Modules/Items/Models/Brand.php',
    'OlaHub\\UserPortal\\Models\\CalendarModel' => $baseDir . '/src/Modules/Calendar/Models/CalendarModel.php',
    'OlaHub\\UserPortal\\Models\\Cart' => $baseDir . '/src/Modules/Cart/Models/Cart.php',
    'OlaHub\\UserPortal\\Models\\CartItems' => $baseDir . '/src/Modules/Cart/Models/CartItems.php',
    'OlaHub\\UserPortal\\Models\\CatalogItem' => $baseDir . '/src/Modules/Items/Models/CatalogItem.php',
    'OlaHub\\UserPortal\\Models\\CatalogItemViews' => $baseDir . '/src/Modules/Items/Models/CatalogItemViews.php',
    'OlaHub\\UserPortal\\Models\\CelebrationContentsModel' => $baseDir . '/src/Modules/Celebration/Models/CelebrationContentsModel.php',
    'OlaHub\\UserPortal\\Models\\CelebrationModel' => $baseDir . '/src/Modules/Celebration/Models/CelebrationModel.php',
    'OlaHub\\UserPortal\\Models\\CelebrationParticipantsModel' => $baseDir . '/src/Modules/Celebration/Models/CelebrationParticipantsModel.php',
    'OlaHub\\UserPortal\\Models\\CelebrationShippingAddressModel' => $baseDir . '/src/Modules/Celebration/Models/CelebrationShippingAddressModel.php',
    'OlaHub\\UserPortal\\Models\\Classification' => $baseDir . '/src/Modules/Items/Models/Classification.php',
    'OlaHub\\UserPortal\\Models\\CompanyStaticData' => $baseDir . '/src/CommonFiles/Models/CompanyStaticData.php',
    'OlaHub\\UserPortal\\Models\\CountriesShipping' => $baseDir . '/src/CommonFiles/Models/CountriesShipping.php',
    'OlaHub\\UserPortal\\Models\\Country' => $baseDir . '/src/CommonFiles/Models/Country.php',
    'OlaHub\\UserPortal\\Models\\Coupon' => $baseDir . '/src/Modules/Coupons/Models/Coupon.php',
    'OlaHub\\UserPortal\\Models\\CouponUsers' => $baseDir . '/src/Modules/Coupons/Models/CouponUsers.php',
    'OlaHub\\UserPortal\\Models\\Currency' => $baseDir . '/src/CommonFiles/Models/Currency.php',
    'OlaHub\\UserPortal\\Models\\CurrnciesExchange' => $baseDir . '/src/CommonFiles/Models/CurrnciesExchange.php',
    'OlaHub\\UserPortal\\Models\\DTemplate' => $baseDir . '/src/Console/Commands/templatesData/Models/DTemplate.php',
    'OlaHub\\UserPortal\\Models\\DesginerItems' => $baseDir . '/src/Modules/Desginer/Models/DesginerItems.php',
    'OlaHub\\UserPortal\\Models\\Designer' => $baseDir . '/src/Modules/Desginer/Models/Designer.php',
    'OlaHub\\UserPortal\\Models\\DesignerInvites' => $baseDir . '/src/Modules/Desginer/Models/DesignerInvites.php',
    'OlaHub\\UserPortal\\Models\\DesignerItemAttrValue' => $baseDir . '/src/Modules/Desginer/Models/DesignerItemAttrValue.php',
    'OlaHub\\UserPortal\\Models\\DesignerItemImages' => $baseDir . '/src/Modules/Desginer/Models/DesignerItemImages.php',
    'OlaHub\\UserPortal\\Models\\DesignerItemInterests' => $baseDir . '/src/Modules/Desginer/Models/DesignerItemInterests.php',
    'OlaHub\\UserPortal\\Models\\DesignerItemOccasions' => $baseDir . '/src/Modules/Desginer/Models/DesignerItemOccasions.php',
    'OlaHub\\UserPortal\\Models\\ExchangeAndRefund' => $baseDir . '/src/CommonFiles/Models/ExchangeAndRefund.php',
    'OlaHub\\UserPortal\\Models\\FcmStoreToken' => $baseDir . '/src/Modules/Payments/Models/FcmStoreToken.php',
    'OlaHub\\UserPortal\\Models\\Following' => $baseDir . '/src/CommonFiles/Models/Following.php',
    'OlaHub\\UserPortal\\Models\\Franchise' => $baseDir . '/src/CommonFiles/Models/Franchise.php',
    'OlaHub\\UserPortal\\Models\\FranchiseDesignerCountry' => $baseDir . '/src/Modules/Desginer/Models/FranchiseDesignerCountry.php',
    'OlaHub\\UserPortal\\Models\\FranchiseNotifications' => $baseDir . '/src/Modules/Desginer/Models/FranchiseNotifications.php',
    'OlaHub\\UserPortal\\Models\\Friends' => $baseDir . '/src/Modules/Profile/Models/Friends.php',
    'OlaHub\\UserPortal\\Models\\GroupMembers' => $baseDir . '/src/Modules/Groups/Models/GroupMembers.php',
    'OlaHub\\UserPortal\\Models\\Interests' => $baseDir . '/src/CommonFiles/Models/Interests.php',
    'OlaHub\\UserPortal\\Models\\ItemAttrValue' => $baseDir . '/src/Modules/Items/Models/ItemAttrValue.php',
    'OlaHub\\UserPortal\\Models\\ItemBrandMer' => $baseDir . '/src/Modules/Items/Models/ItemBrandMer.php',
    'OlaHub\\UserPortal\\Models\\ItemCategory' => $baseDir . '/src/CommonFiles/Models/ItemCategory.php',
    'OlaHub\\UserPortal\\Models\\ItemImages' => $baseDir . '/src/Modules/Items/Models/ItemImages.php',
    'OlaHub\\UserPortal\\Models\\ItemInterests' => $baseDir . '/src/Modules/Items/Models/ItemInterests.php',
    'OlaHub\\UserPortal\\Models\\ItemOccasions' => $baseDir . '/src/Modules/Items/Models/ItemOccasions.php',
    'OlaHub\\UserPortal\\Models\\ItemPickuAddr' => $baseDir . '/src/Modules/Items/Models/ItemPickuAddr.php',
    'OlaHub\\UserPortal\\Models\\ItemReviews' => $baseDir . '/src/Modules/Items/Models/ItemReviews.php',
    'OlaHub\\UserPortal\\Models\\ItemStore' => $baseDir . '/src/Modules/Items/Models/ItemStore.php',
    'OlaHub\\UserPortal\\Models\\Language' => $baseDir . '/src/CommonFiles/Models/Language.php',
    'OlaHub\\UserPortal\\Models\\LikedItems' => $baseDir . '/src/Modules/Likes/Models/LikedItems.php',
    'OlaHub\\UserPortal\\Models\\ManyToMany\\ItemCountriesCategory' => $baseDir . '/src/CommonFiles/Models/ItemCountriesCategory.php',
    'OlaHub\\UserPortal\\Models\\ManyToMany\\PaymentCountryRelation' => $baseDir . '/src/Modules/Payments/Models/PaymentCountryRelation.php',
    'OlaHub\\UserPortal\\Models\\ManyToMany\\PaymentTypeRelation' => $baseDir . '/src/Modules/Payments/Models/PaymentTypeRelation.php',
    'OlaHub\\UserPortal\\Models\\ManyToMany\\State' => $baseDir . '/src/CommonFiles/Models/State.php',
    'OlaHub\\UserPortal\\Models\\ManyToMany\\exchRefundPolicyCountries' => $baseDir . '/src/CommonFiles/Models/exchRefundPolicyCountries.php',
    'OlaHub\\UserPortal\\Models\\ManyToMany\\occasionCountries' => $baseDir . '/src/CommonFiles/Models/occasionCountries.php',
    'OlaHub\\UserPortal\\Models\\Merchant' => $baseDir . '/src/Modules/Items/Models/Merchant.php',
    'OlaHub\\UserPortal\\Models\\MerchantCategory' => $baseDir . '/src/Modules/Items/Models/MerchantCategory.php',
    'OlaHub\\UserPortal\\Models\\MerchantInvite' => $baseDir . '/src/CommonFiles/Models/MerchantInvite.php',
    'OlaHub\\UserPortal\\Models\\MessageTemplate' => $baseDir . '/src/CommonFiles/Models/MessageTemplate.php',
    'OlaHub\\UserPortal\\Models\\Notifications' => $baseDir . '/src/CommonFiles/Models/NotificationMongo.php',
    'OlaHub\\UserPortal\\Models\\Occasion' => $baseDir . '/src/CommonFiles/Models/Occasion.php',
    'OlaHub\\UserPortal\\Models\\OlaHubCommonModelsHelper' => $baseDir . '/src/CommonFiles/Models/OlaHubCommonModelsHelper.php',
    'OlaHub\\UserPortal\\Models\\PaymentMethod' => $baseDir . '/src/Modules/Payments/Models/PaymentMethod.php',
    'OlaHub\\UserPortal\\Models\\PaymentShippingStatus' => $baseDir . '/src/Modules/MyPurshasedItems/Models/PaymentShippingStatus.php',
    'OlaHub\\UserPortal\\Models\\PaymentType' => $baseDir . '/src/Modules/Payments/Models/PaymentType.php',
    'OlaHub\\UserPortal\\Models\\Post' => $baseDir . '/src/Modules/Posts/Models/Post.php',
    'OlaHub\\UserPortal\\Models\\SellWithUsUnsupport' => $baseDir . '/src/CommonFiles/Models/SellWithUsUnsupport.php',
    'OlaHub\\UserPortal\\Models\\ShippingCities' => $baseDir . '/src/CommonFiles/Models/ShippingCities.php',
    'OlaHub\\UserPortal\\Models\\ShippingCountries' => $baseDir . '/src/CommonFiles/Models/ShippingCountries.php',
    'OlaHub\\UserPortal\\Models\\ShippingRegions' => $baseDir . '/src/CommonFiles/Models/ShippingRegions.php',
    'OlaHub\\UserPortal\\Models\\StaticPages' => $baseDir . '/src/CommonFiles/Models/StaticPages.php',
    'OlaHub\\UserPortal\\Models\\StorePickups' => $baseDir . '/src/Modules/Items/Models/StorePickups.php',
    'OlaHub\\UserPortal\\Models\\UserBill' => $baseDir . '/src/Modules/MyPurshasedItems/Models/UserBill.php',
    'OlaHub\\UserPortal\\Models\\UserBillDetails' => $baseDir . '/src/Modules/MyPurshasedItems/Models/UserBillDetails.php',
    'OlaHub\\UserPortal\\Models\\UserLoginsModel' => $baseDir . '/src/Modules/Users/Models/UserLoginsModel.php',
    'OlaHub\\UserPortal\\Models\\UserModel' => $baseDir . '/src/Modules/Users/Models/UserModel.php',
    'OlaHub\\UserPortal\\Models\\UserMongo' => $baseDir . '/src/Modules/Users/Models/UserMongo.php',
    'OlaHub\\UserPortal\\Models\\UserPoints' => $baseDir . '/src/Modules/Users/Models/UserPoints.php',
    'OlaHub\\UserPortal\\Models\\UserSessionModel' => $baseDir . '/src/Modules/Users/Models/UserSessionModel.php',
    'OlaHub\\UserPortal\\Models\\UserShippingAddressModel' => $baseDir . '/src/Modules/Users/Models/UserShippingAddressModel.php',
    'OlaHub\\UserPortal\\Models\\UserVouchers' => $baseDir . '/src/Modules/Users/Models/UserVouchers.php',
    'OlaHub\\UserPortal\\Models\\WishList' => $baseDir . '/src/Modules/WishLists/Models/WishList.php',
    'OlaHub\\UserPortal\\Models\\groups' => $baseDir . '/src/Modules/Groups/Models/groups.php',
    'OlaHub\\UserPortal\\Observers\\OlaHubCommonObserve' => $baseDir . '/src/Observes/OlaHubCommonObserve.php',
    'OlaHub\\UserPortal\\Providers\\AppServiceProvider' => $baseDir . '/src/Providers/AppServiceProvider.php',
    'OlaHub\\UserPortal\\Providers\\EventServiceProvider' => $baseDir . '/src/Providers/EventServiceProvider.php',
    'OlaHub\\UserPortal\\Providers\\ValidationServiceProvider' => $baseDir . '/src/Providers/ValidationServiceProvider.php',
    'OlaHub\\UserPortal\\ResponseHandlers\\AdsResponseHandler' => $baseDir . '/src/Modules/Announcement/ResponseHandler/AdsResponseHandler.php',
    'OlaHub\\UserPortal\\ResponseHandlers\\BrandSearchResponseHandler' => $baseDir . '/src/CommonFiles/ResponseHandler/BrandSearchResponseHandler.php',
    'OlaHub\\UserPortal\\ResponseHandlers\\BrandsResponseHandler' => $baseDir . '/src/Modules/Items/ResponseHandler/BrandsResponseHandler.php',
    'OlaHub\\UserPortal\\ResponseHandlers\\CalendarForCelebrationResponseHandler' => $baseDir . '/src/Modules/Calendar/ResponseHandler/CalendarForCelebrationResponseHandler.php',
    'OlaHub\\UserPortal\\ResponseHandlers\\CalendarsResponseHandler' => $baseDir . '/src/Modules/Calendar/ResponseHandler/CalendarsResponseHandler.php',
    'OlaHub\\UserPortal\\ResponseHandlers\\CartResponseHandler' => $baseDir . '/src/Modules/Cart/ResponseHandler/CartResponseHandler.php',
    'OlaHub\\UserPortal\\ResponseHandlers\\CartTotalsResponseHandler' => $baseDir . '/src/Modules/Cart/ResponseHandler/CartTotalsResponseHandler.php',
    'OlaHub\\UserPortal\\ResponseHandlers\\CelebrationCommitResponseHandler' => $baseDir . '/src/Modules/Celebration/ResponseHandler/CelebrationCommitResponseHandler.php',
    'OlaHub\\UserPortal\\ResponseHandlers\\CelebrationGiftDoneResponseHandler' => $baseDir . '/src/Modules/Celebration/ResponseHandler/CelebrationGiftDoneResponseHandler.php',
    'OlaHub\\UserPortal\\ResponseHandlers\\CelebrationGiftResponseHandler' => $baseDir . '/src/Modules/Celebration/ResponseHandler/CelebrationGiftResponseHandler.php',
    'OlaHub\\UserPortal\\ResponseHandlers\\CelebrationParticipantResponseHandler' => $baseDir . '/src/Modules/Celebration/ResponseHandler/CelebrationParticipantResponseHandler.php',
    'OlaHub\\UserPortal\\ResponseHandlers\\CelebrationResponseHandler' => $baseDir . '/src/Modules/Celebration/ResponseHandler/CelebrationResponseHandler.php',
    'OlaHub\\UserPortal\\ResponseHandlers\\ClassificationFilterResponseHandler' => $baseDir . '/src/Modules/Items/ResponseHandler/ClassificationFilterResponseHandler.php',
    'OlaHub\\UserPortal\\ResponseHandlers\\ClassificationForPrequestFormsResponseHandler' => $baseDir . '/src/Modules/Desginer/ResponseHandler/ClassificationForPrequestFormsResponseHandler.php',
    'OlaHub\\UserPortal\\ResponseHandlers\\ClassificationResponseHandler' => $baseDir . '/src/Modules/Items/ResponseHandler/ClassificationResponseHandler.php',
    'OlaHub\\UserPortal\\ResponseHandlers\\ClassificationSearchResponseHandler' => $baseDir . '/src/CommonFiles/ResponseHandler/ClassificationSearchResponseHandler.php',
    'OlaHub\\UserPortal\\ResponseHandlers\\CommunitiesForLandingPageResponseHandler' => $baseDir . '/src/CommonFiles/ResponseHandler/CommunitiesForLandingPageResponseHandler.php',
    'OlaHub\\UserPortal\\ResponseHandlers\\CountriesCodeForPrequestFormsResponseHandler' => $baseDir . '/src/CommonFiles/ResponseHandler/CountriesCodeForPrequestFormsResponseHandler.php',
    'OlaHub\\UserPortal\\ResponseHandlers\\CountriesForPrequestFormsResponseHandler' => $baseDir . '/src/CommonFiles/ResponseHandler/CountriesForPrequestFormsResponseHandler.php',
    'OlaHub\\UserPortal\\ResponseHandlers\\DTemplatesResponseHandler' => $baseDir . '/src/Console/Commands/templatesData/ResponseHandler/DTemplatesResponseHandler.php',
    'OlaHub\\UserPortal\\ResponseHandlers\\DesginerItemsSearchResponseHandler' => $baseDir . '/src/CommonFiles/ResponseHandler/DesginerItemsSearchResponseHandler.php',
    'OlaHub\\UserPortal\\ResponseHandlers\\DesignersSearchResponseHandler' => $baseDir . '/src/CommonFiles/ResponseHandler/DesignersSearchResponseHandler.php',
    'OlaHub\\UserPortal\\ResponseHandlers\\FriendsResponseHandler' => $baseDir . '/src/Modules/Profile/ResponseHandler/FriendsResponseHandler.php',
    'OlaHub\\UserPortal\\ResponseHandlers\\GroupBrandsResponseHandler' => $baseDir . '/src/Modules/Groups/ResponseHandler/GroupBrandsResponseHandler.php',
    'OlaHub\\UserPortal\\ResponseHandlers\\GroupSearchResponseHandler' => $baseDir . '/src/CommonFiles/ResponseHandler/GroupSearchResponseHandler.php',
    'OlaHub\\UserPortal\\ResponseHandlers\\HeaderDataResponseHandler' => $baseDir . '/src/Modules/Users/ResponseHandler/HeaderDataResponseHandler.php',
    'OlaHub\\UserPortal\\ResponseHandlers\\InterestsForPrequestFormsResponseHandler' => $baseDir . '/src/CommonFiles/ResponseHandler/InterestsForPrequestFormsResponseHandler.php',
    'OlaHub\\UserPortal\\ResponseHandlers\\InterestsHomeResponseHandler' => $baseDir . '/src/Modules/Items/ResponseHandler/InterestsHomeResponseHandler.php',
    'OlaHub\\UserPortal\\ResponseHandlers\\ItemCategoryForPrequestFormsResponseHandler' => $baseDir . '/src/Modules/Desginer/ResponseHandler/ItemCategoryForPrequestFormsResponseHandler.php',
    'OlaHub\\UserPortal\\ResponseHandlers\\ItemResponseHandler' => $baseDir . '/src/Modules/Items/ResponseHandler/ItemResponseHandler.php',
    'OlaHub\\UserPortal\\ResponseHandlers\\ItemReviewsResponseHandler' => $baseDir . '/src/Modules/Items/ResponseHandler/ItemReviewsResponseHandler.php',
    'OlaHub\\UserPortal\\ResponseHandlers\\ItemSearchResponseHandler' => $baseDir . '/src/CommonFiles/ResponseHandler/ItemSearchResponseHandler.php',
    'OlaHub\\UserPortal\\ResponseHandlers\\ItemsListResponseHandler' => $baseDir . '/src/Modules/Items/ResponseHandler/ItemsListResponseHandler.php',
    'OlaHub\\UserPortal\\ResponseHandlers\\LikedItemsResponseHandler' => $baseDir . '/src/Modules/Likes/ResponseHandler/LikedItemsResponseHandler.php',
    'OlaHub\\UserPortal\\ResponseHandlers\\MainGroupResponseHandler' => $baseDir . '/src/Modules/Groups/ResponseHandler/MainGroupResponseHandler.php',
    'OlaHub\\UserPortal\\ResponseHandlers\\MembersResponseHandler' => $baseDir . '/src/Modules/Groups/ResponseHandler/MembersResponseHandler.php',
    'OlaHub\\UserPortal\\ResponseHandlers\\MyFriendsResponseHandler' => $baseDir . '/src/Modules/Users/ResponseHandler/MyFriendsResponseHandler.php',
    'OlaHub\\UserPortal\\ResponseHandlers\\NotLoginCartResponseHandler' => $baseDir . '/src/Modules/Cart/ResponseHandler/NotLoginCartResponseHandler.php',
    'OlaHub\\UserPortal\\ResponseHandlers\\OccasionsHomeResponseHandler' => $baseDir . '/src/CommonFiles/ResponseHandler/OccasionsHomeResponseHandler.php',
    'OlaHub\\UserPortal\\ResponseHandlers\\OccasionsResponseHandler' => $baseDir . '/src/Modules/Items/ResponseHandler/OccasionsResponseHandler.php',
    'OlaHub\\UserPortal\\ResponseHandlers\\OccassionsForPrequestFormsResponseHandler' => $baseDir . '/src/Modules/Calendar/ResponseHandler/OccassionsForPrequestFormsResponseHandler.php',
    'OlaHub\\UserPortal\\ResponseHandlers\\PaymentResponseHandler' => $baseDir . '/src/Modules/Payments/ResponseHandler/PaymentResponseHandler.php',
    'OlaHub\\UserPortal\\ResponseHandlers\\PostsResponseHandler' => $baseDir . '/src/Modules/Posts/ResponseHandler/PostsResponseHandler.php',
    'OlaHub\\UserPortal\\ResponseHandlers\\ProfileInfoResponseHandler' => $baseDir . '/src/Modules/Users/ResponseHandler/ProfileInfoResponseHandler.php',
    'OlaHub\\UserPortal\\ResponseHandlers\\PurchasedItemResponseHandler' => $baseDir . '/src/Modules/MyPurshasedItems/ResponseHandler/PurchasedItemResponseHandler.php',
    'OlaHub\\UserPortal\\ResponseHandlers\\PurchasedItemsResponseHandler' => $baseDir . '/src/Modules/MyPurshasedItems/ResponseHandler/PurchasedItemsResponseHandler.php',
    'OlaHub\\UserPortal\\ResponseHandlers\\UpcomingEventsResponseHandler' => $baseDir . '/src/Modules/Profile/ResponseHandler/UpcomingEventsResponseHandler.php',
    'OlaHub\\UserPortal\\ResponseHandlers\\UserBalanceDetailsResponseHandler' => $baseDir . '/src/Modules/Users/ResponseHandler/UserBalanceDetailsResponseHandler.php',
    'OlaHub\\UserPortal\\ResponseHandlers\\UserSearchResponseHandler' => $baseDir . '/src/CommonFiles/ResponseHandler/UserSearchResponseHandler.php',
    'OlaHub\\UserPortal\\ResponseHandlers\\UsersResponseHandler' => $baseDir . '/src/Modules/Users/ResponseHandler/UsersResponseHandler.php',
    'OlaHub\\UserPortal\\ResponseHandlers\\WishListsResponseHandler' => $baseDir . '/src/Modules/WishLists/ResponseHandler/WishListsResponseHandler.php',
    'OlaHub\\UserPortal\\ResponseHandlers\\searchUsersForPrequestFormsResponseHandler' => $baseDir . '/src/CommonFiles/ResponseHandler/searchUsersForPrequestFormsResponseHandler.php',
);
