<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitbba53004a0b53843540453fe6dd181df
{
    public static $classMap = array (
        'App\\Mail\\EmailFunction' => __DIR__ . '/../..' . '/src/Mail/EmailFunction.php',
        'Laravel\\Lumen\\Console\\Commands\\createModuleCommand' => __DIR__ . '/../..' . '/src/Console/Commands/createModuleCommand.php',
        'OlaHub\\Exceptions\\Handler' => __DIR__ . '/../..' . '/src/Exceptions/Handler.php',
        'OlaHub\\Helpers\\DTemplatesHelper' => __DIR__ . '/../..' . '/src/Console/Commands/templatesData/Utilities/DTemplatesHelper.php',
        'OlaHub\\Models\\AdSlots' => __DIR__ . '/../..' . '/src/Modules/Announcement/Models/AdSlots.php',
        'OlaHub\\Models\\AdSlotsCountries' => __DIR__ . '/../..' . '/src/Modules/Announcement/Models/AdSlotsCountries.php',
        'OlaHub\\Models\\AdStatistics' => __DIR__ . '/../..' . '/src/Modules/Announcement/Models/AdStatistics.php',
        'OlaHub\\Models\\Ads' => __DIR__ . '/../..' . '/src/Modules/Announcement/Models/Ads.php',
        'OlaHub\\Models\\AdsMongo' => __DIR__ . '/../..' . '/src/Modules/Announcement/Models/AdsMongo.php',
        'OlaHub\\Repositories\\DTemplatesRepository' => __DIR__ . '/../..' . '/src/Console/Commands/templatesData/Repositories/DTemplatesRepository.php',
        'OlaHub\\Services\\DTemplatesServices' => __DIR__ . '/../..' . '/src/Console/Commands/templatesData/Services/DTemplatesServices.php',
        'OlaHub\\UserPortal\\Console\\Kernel' => __DIR__ . '/../..' . '/src/Console/Kernel.php',
        'OlaHub\\UserPortal\\Controllers\\AdsController' => __DIR__ . '/../..' . '/src/Modules/Announcement/Controllers/AdsController.php',
        'OlaHub\\UserPortal\\Controllers\\CalendarController' => __DIR__ . '/../..' . '/src/Modules/Calendar/Controllers/CalendarController.php',
        'OlaHub\\UserPortal\\Controllers\\CelebrationContentsController' => __DIR__ . '/../..' . '/src/Modules/Celebration/Controllers/CelebrationContentsController.php',
        'OlaHub\\UserPortal\\Controllers\\CelebrationController' => __DIR__ . '/../..' . '/src/Modules/Celebration/Controllers/CelebrationController.php',
        'OlaHub\\UserPortal\\Controllers\\CronController' => __DIR__ . '/../..' . '/src/Modules/Crons/Controllers/CronController.php',
        'OlaHub\\UserPortal\\Controllers\\DTemplatesController' => __DIR__ . '/../..' . '/src/Console/Commands/templatesData/Controllers/DTemplatesController.php',
        'OlaHub\\UserPortal\\Controllers\\DTemplatesTrashController' => __DIR__ . '/../..' . '/src/Console/Commands/templatesData/Controllers/DTemplatesTrashController.php',
        'OlaHub\\UserPortal\\Controllers\\FriendController' => __DIR__ . '/../..' . '/src/Modules/Profile/Controllers/FriendController.php',
        'OlaHub\\UserPortal\\Controllers\\GiftController' => __DIR__ . '/../..' . '/src/Modules/Celebration/Controllers/GiftController.php',
        'OlaHub\\UserPortal\\Controllers\\MainController' => __DIR__ . '/../..' . '/src/Modules/Groups/Controllers/MainController.php',
        'OlaHub\\UserPortal\\Controllers\\OlaHubAdvertisesController' => __DIR__ . '/../..' . '/src/Modules/Items/Controllers/OlaHubAdvertisesController.php',
        'OlaHub\\UserPortal\\Controllers\\OlaHubCartController' => __DIR__ . '/../..' . '/src/Modules/Cart/Controllers/OlaHubCartController.php',
        'OlaHub\\UserPortal\\Controllers\\OlaHubCouponsController' => __DIR__ . '/../..' . '/src/Modules/Coupons/Controllers/OlaHubCouponsController.php',
        'OlaHub\\UserPortal\\Controllers\\OlaHubDesginerController' => __DIR__ . '/../..' . '/src/Modules/Desginer/Controllers/OlaHubDesginerController.php',
        'OlaHub\\UserPortal\\Controllers\\OlaHubGeneralController' => __DIR__ . '/../..' . '/src/CommonFiles/Controllers/OlaHubGeneralController.php',
        'OlaHub\\UserPortal\\Controllers\\OlaHubGuestController' => __DIR__ . '/../..' . '/src/Modules/Users/Controllers/OlaHubGuestController.php',
        'OlaHub\\UserPortal\\Controllers\\OlaHubHeaderMenuController' => __DIR__ . '/../..' . '/src/Modules/Items/Controllers/OlaHubHeaderMenuController.php',
        'OlaHub\\UserPortal\\Controllers\\OlaHubItemController' => __DIR__ . '/../..' . '/src/Modules/Items/Controllers/OlaHubItemController.php',
        'OlaHub\\UserPortal\\Controllers\\OlaHubItemReviewsController' => __DIR__ . '/../..' . '/src/Modules/Items/Controllers/OlaHubItemReviewsController.php',
        'OlaHub\\UserPortal\\Controllers\\OlaHubLandingPageController' => __DIR__ . '/../..' . '/src/Modules/Items/Controllers/OlaHubLandingPageController.php',
        'OlaHub\\UserPortal\\Controllers\\OlaHubLikesController' => __DIR__ . '/../..' . '/src/Modules/Likes/Controllers/OlaHubLikesController.php',
        'OlaHub\\UserPortal\\Controllers\\OlaHubPaymentsCallbackController' => __DIR__ . '/../..' . '/src/Modules/Payments/Controllers/OlaHubPaymentsCallbackController.php',
        'OlaHub\\UserPortal\\Controllers\\OlaHubPaymentsMainController' => __DIR__ . '/../..' . '/src/Modules/Payments/Controllers/OlaHubPaymentsMainController.php',
        'OlaHub\\UserPortal\\Controllers\\OlaHubPaymentsPrepareController' => __DIR__ . '/../..' . '/src/Modules/Payments/Controllers/OlaHubPaymentsPrepareController.php',
        'OlaHub\\UserPortal\\Controllers\\OlaHubPostController' => __DIR__ . '/../..' . '/src/Modules/Posts/Controllers/OlaHubPostController.php',
        'OlaHub\\UserPortal\\Controllers\\OlaHubSharesController' => __DIR__ . '/../..' . '/src/Modules/shares/Controllers/OlaHubSharesController.php',
        'OlaHub\\UserPortal\\Controllers\\OlaHubUserController' => __DIR__ . '/../..' . '/src/Modules/Users/Controllers/OlaHubUserController.php',
        'OlaHub\\UserPortal\\Controllers\\OlaHubWishListsController' => __DIR__ . '/../..' . '/src/Modules/WishLists/Controllers/OlaHubWishListsController.php',
        'OlaHub\\UserPortal\\Controllers\\ParticipantController' => __DIR__ . '/../..' . '/src/Modules/Celebration/Controllers/ParticipantController.php',
        'OlaHub\\UserPortal\\Controllers\\PurchasedItemsController' => __DIR__ . '/../..' . '/src/Modules/MyPurshasedItems/Controllers/PurchasedItemsController.php',
        'OlaHub\\UserPortal\\Controllers\\RegistryController' => __DIR__ . '/../..' . '/src/Modules/Registry/Controllers/RegistryController.php',
        'OlaHub\\UserPortal\\Controllers\\RegistryGiftController' => __DIR__ . '/../..' . '/src/Modules/Registry/Controllers/RegistryGiftController.php',
        'OlaHub\\UserPortal\\Controllers\\RegistryParticipantController' => __DIR__ . '/../..' . '/src/Modules/Registry/Controllers/RegistryParticipantController.php',
        'OlaHub\\UserPortal\\Events\\OlaHubCommonEvent' => __DIR__ . '/../..' . '/src/Events/OlaHubCommonEvent.php',
        'OlaHub\\UserPortal\\Helpers\\BillsHelper' => __DIR__ . '/../..' . '/src/CommonFiles/Utilities/BillsHelper.php',
        'OlaHub\\UserPortal\\Helpers\\CartHelper' => __DIR__ . '/../..' . '/src/Modules/Cart/Utilities/CartHelper.php',
        'OlaHub\\UserPortal\\Helpers\\CelebrationHelper' => __DIR__ . '/../..' . '/src/Modules/Celebration/Utilities/CelebrationHelper.php',
        'OlaHub\\UserPortal\\Helpers\\CommonHelper' => __DIR__ . '/../..' . '/src/CommonFiles/Utilities/CommonHelper.php',
        'OlaHub\\UserPortal\\Helpers\\CouponsHelper' => __DIR__ . '/../..' . '/src/Modules/Coupons/Utilities/CouponsHelper.php',
        'OlaHub\\UserPortal\\Helpers\\EmailHelper' => __DIR__ . '/../..' . '/src/CommonFiles/Utilities/EmailHelper.php',
        'OlaHub\\UserPortal\\Helpers\\ItemHelper' => __DIR__ . '/../..' . '/src/Modules/Items/Utilities/ItemHelper.php',
        'OlaHub\\UserPortal\\Helpers\\OlaHubCommonHelper' => __DIR__ . '/../..' . '/src/Utilities/OlaHubCommonHelper.php',
        'OlaHub\\UserPortal\\Helpers\\PaymentHelper' => __DIR__ . '/../..' . '/src/Modules/Payments/Utilities/PaymentHelper.php',
        'OlaHub\\UserPortal\\Helpers\\SmsHelper' => __DIR__ . '/../..' . '/src/CommonFiles/Utilities/SmsHelper.php',
        'OlaHub\\UserPortal\\Helpers\\UserHelper' => __DIR__ . '/../..' . '/src/Modules/Users/Utilities/UserHelper.php',
        'OlaHub\\UserPortal\\Helpers\\UserShippingAddressHelper' => __DIR__ . '/../..' . '/src/Modules/Users/Utilities/UserShippingAddressHelper.php',
        'OlaHub\\UserPortal\\Helpers\\WishListHelper' => __DIR__ . '/../..' . '/src/Modules/WishLists/Utilities/WishListHelper.php',
        'OlaHub\\UserPortal\\Jobs\\OlaHubCommonJob' => __DIR__ . '/../..' . '/src/Jobs/OlaHubCommonJob.php',
        'OlaHub\\UserPortal\\Libraries\\APIAlfresco' => __DIR__ . '/../..' . '/src/Utilities/alfresco/APIAlfresco.php',
        'OlaHub\\UserPortal\\Libraries\\CmisConstraintException' => __DIR__ . '/../..' . '/src/Utilities/alfresco/cmisRepWrapper.php',
        'OlaHub\\UserPortal\\Libraries\\CmisInvalidArgumentException' => __DIR__ . '/../..' . '/src/Utilities/alfresco/cmisRepWrapper.php',
        'OlaHub\\UserPortal\\Libraries\\CmisNotImplementedException' => __DIR__ . '/../..' . '/src/Utilities/alfresco/cmisRepWrapper.php',
        'OlaHub\\UserPortal\\Libraries\\CmisNotSupportedException' => __DIR__ . '/../..' . '/src/Utilities/alfresco/cmisRepWrapper.php',
        'OlaHub\\UserPortal\\Libraries\\CmisObjectNotFoundException' => __DIR__ . '/../..' . '/src/Utilities/alfresco/cmisRepWrapper.php',
        'OlaHub\\UserPortal\\Libraries\\CmisPermissionDeniedException' => __DIR__ . '/../..' . '/src/Utilities/alfresco/cmisRepWrapper.php',
        'OlaHub\\UserPortal\\Libraries\\CmisRuntimeException' => __DIR__ . '/../..' . '/src/Utilities/alfresco/cmisRepWrapper.php',
        'OlaHub\\UserPortal\\Libraries\\ImageReader' => __DIR__ . '/../..' . '/src/Utilities/ImageReader.php',
        'OlaHub\\UserPortal\\Libraries\\InflectLibrary' => __DIR__ . '/../..' . '/src/Utilities/InflectLibrary.php',
        'OlaHub\\UserPortal\\Libraries\\LoginSystem' => __DIR__ . '/../..' . '/src/Utilities/LoginSystem.php',
        'OlaHub\\UserPortal\\Libraries\\OlaHubNotificationHelper' => __DIR__ . '/../..' . '/src/Utilities/OlaHubNotificationHelper.php',
        'OlaHub\\UserPortal\\Libraries\\SendEmails' => __DIR__ . '/../..' . '/src/Utilities/SendEmail.php',
        'OlaHub\\UserPortal\\Libraries\\cmisRepWrapper' => __DIR__ . '/../..' . '/src/Utilities/alfresco/cmisRepWrapper.php',
        'OlaHub\\UserPortal\\Libraries\\cmisService' => __DIR__ . '/../..' . '/src/Utilities/alfresco/cmisService.php',
        'OlaHub\\UserPortal\\Listeners\\OlaHubCommonListener' => __DIR__ . '/../..' . '/src/Listeners/OlaHubCommonListener.php',
        'OlaHub\\UserPortal\\Middlewares\\KillVarsMiddleware' => __DIR__ . '/../..' . '/src/Middleware/KillVarsMiddleware.php',
        'OlaHub\\UserPortal\\Middlewares\\LocaleMiddleware' => __DIR__ . '/../..' . '/src/Middleware/LocaleMiddleware.php',
        'OlaHub\\UserPortal\\Middlewares\\LogMiddleware' => __DIR__ . '/../..' . '/src/Middleware/LogMiddleware.php',
        'OlaHub\\UserPortal\\Middlewares\\RequestTypeMiddleware' => __DIR__ . '/../..' . '/src/Middleware/RequestTypeMiddleware.php',
        'OlaHub\\UserPortal\\Models\\AttrValue' => __DIR__ . '/../..' . '/src/Modules/Items/Models/AttrValue.php',
        'OlaHub\\UserPortal\\Models\\Attribute' => __DIR__ . '/../..' . '/src/Modules/Items/Models/Attribute.php',
        'OlaHub\\UserPortal\\Models\\Brand' => __DIR__ . '/../..' . '/src/Modules/Items/Models/Brand.php',
        'OlaHub\\UserPortal\\Models\\CalendarModel' => __DIR__ . '/../..' . '/src/Modules/Calendar/Models/CalendarModel.php',
        'OlaHub\\UserPortal\\Models\\Cart' => __DIR__ . '/../..' . '/src/Modules/Cart/Models/Cart.php',
        'OlaHub\\UserPortal\\Models\\CartItems' => __DIR__ . '/../..' . '/src/Modules/Cart/Models/CartItems.php',
        'OlaHub\\UserPortal\\Models\\CatalogItem' => __DIR__ . '/../..' . '/src/Modules/Items/Models/CatalogItem.php',
        'OlaHub\\UserPortal\\Models\\CatalogItemViews' => __DIR__ . '/../..' . '/src/Modules/Items/Models/CatalogItemViews.php',
        'OlaHub\\UserPortal\\Models\\CelebrationContentsModel' => __DIR__ . '/../..' . '/src/Modules/Celebration/Models/CelebrationContentsModel.php',
        'OlaHub\\UserPortal\\Models\\CelebrationModel' => __DIR__ . '/../..' . '/src/Modules/Celebration/Models/CelebrationModel.php',
        'OlaHub\\UserPortal\\Models\\CelebrationParticipantsModel' => __DIR__ . '/../..' . '/src/Modules/Celebration/Models/CelebrationParticipantsModel.php',
        'OlaHub\\UserPortal\\Models\\CelebrationShippingAddressModel' => __DIR__ . '/../..' . '/src/Modules/Celebration/Models/CelebrationShippingAddressModel.php',
        'OlaHub\\UserPortal\\Models\\Classification' => __DIR__ . '/../..' . '/src/Modules/Items/Models/Classification.php',
        'OlaHub\\UserPortal\\Models\\CompanyStaticData' => __DIR__ . '/../..' . '/src/CommonFiles/Models/CompanyStaticData.php',
        'OlaHub\\UserPortal\\Models\\CountriesShipping' => __DIR__ . '/../..' . '/src/CommonFiles/Models/CountriesShipping.php',
        'OlaHub\\UserPortal\\Models\\Country' => __DIR__ . '/../..' . '/src/CommonFiles/Models/Country.php',
        'OlaHub\\UserPortal\\Models\\Coupon' => __DIR__ . '/../..' . '/src/Modules/Coupons/Models/Coupon.php',
        'OlaHub\\UserPortal\\Models\\CouponUsers' => __DIR__ . '/../..' . '/src/Modules/Coupons/Models/CouponUsers.php',
        'OlaHub\\UserPortal\\Models\\Currency' => __DIR__ . '/../..' . '/src/CommonFiles/Models/Currency.php',
        'OlaHub\\UserPortal\\Models\\CurrnciesExchange' => __DIR__ . '/../..' . '/src/CommonFiles/Models/CurrnciesExchange.php',
        'OlaHub\\UserPortal\\Models\\DTemplate' => __DIR__ . '/../..' . '/src/Console/Commands/templatesData/Models/DTemplate.php',
        'OlaHub\\UserPortal\\Models\\Designer' => __DIR__ . '/../..' . '/src/Modules/Desginer/Models/Designer.php',
        'OlaHub\\UserPortal\\Models\\DesignerInvites' => __DIR__ . '/../..' . '/src/Modules/Desginer/Models/DesignerInvites.php',
        'OlaHub\\UserPortal\\Models\\DesignerItemAttrValue' => __DIR__ . '/../..' . '/src/Modules/Desginer/Models/DesignerItemAttrValue.php',
        'OlaHub\\UserPortal\\Models\\DesignerItemImages' => __DIR__ . '/../..' . '/src/Modules/Desginer/Models/DesignerItemImages.php',
        'OlaHub\\UserPortal\\Models\\DesignerItemInterests' => __DIR__ . '/../..' . '/src/Modules/Desginer/Models/DesignerItemInterests.php',
        'OlaHub\\UserPortal\\Models\\DesignerItemOccasions' => __DIR__ . '/../..' . '/src/Modules/Desginer/Models/DesignerItemOccasions.php',
        'OlaHub\\UserPortal\\Models\\DesignerItems' => __DIR__ . '/../..' . '/src/Modules/Desginer/Models/DesginerItems.php',
        'OlaHub\\UserPortal\\Models\\ExchangeAndRefund' => __DIR__ . '/../..' . '/src/CommonFiles/Models/ExchangeAndRefund.php',
        'OlaHub\\UserPortal\\Models\\FcmStoreToken' => __DIR__ . '/../..' . '/src/Modules/Payments/Models/FcmStoreToken.php',
        'OlaHub\\UserPortal\\Models\\Following' => __DIR__ . '/../..' . '/src/CommonFiles/Models/Following.php',
        'OlaHub\\UserPortal\\Models\\Franchise' => __DIR__ . '/../..' . '/src/CommonFiles/Models/Franchise.php',
        'OlaHub\\UserPortal\\Models\\FranchiseDesignerCountry' => __DIR__ . '/../..' . '/src/Modules/Desginer/Models/FranchiseDesignerCountry.php',
        'OlaHub\\UserPortal\\Models\\FranchiseNotifications' => __DIR__ . '/../..' . '/src/Modules/Desginer/Models/FranchiseNotifications.php',
        'OlaHub\\UserPortal\\Models\\Friends' => __DIR__ . '/../..' . '/src/Modules/Profile/Models/Friends.php',
        'OlaHub\\UserPortal\\Models\\GroupMembers' => __DIR__ . '/../..' . '/src/Modules/Groups/Models/GroupMembers.php',
        'OlaHub\\UserPortal\\Models\\Interests' => __DIR__ . '/../..' . '/src/CommonFiles/Models/Interests.php',
        'OlaHub\\UserPortal\\Models\\ItemAttrValue' => __DIR__ . '/../..' . '/src/Modules/Items/Models/ItemAttrValue.php',
        'OlaHub\\UserPortal\\Models\\ItemBrandMer' => __DIR__ . '/../..' . '/src/Modules/Items/Models/ItemBrandMer.php',
        'OlaHub\\UserPortal\\Models\\ItemCategory' => __DIR__ . '/../..' . '/src/CommonFiles/Models/ItemCategory.php',
        'OlaHub\\UserPortal\\Models\\ItemImages' => __DIR__ . '/../..' . '/src/Modules/Items/Models/ItemImages.php',
        'OlaHub\\UserPortal\\Models\\ItemInterests' => __DIR__ . '/../..' . '/src/Modules/Items/Models/ItemInterests.php',
        'OlaHub\\UserPortal\\Models\\ItemOccasions' => __DIR__ . '/../..' . '/src/Modules/Items/Models/ItemOccasions.php',
        'OlaHub\\UserPortal\\Models\\ItemPickuAddr' => __DIR__ . '/../..' . '/src/Modules/Items/Models/ItemPickuAddr.php',
        'OlaHub\\UserPortal\\Models\\ItemReviews' => __DIR__ . '/../..' . '/src/Modules/Items/Models/ItemReviews.php',
        'OlaHub\\UserPortal\\Models\\ItemStore' => __DIR__ . '/../..' . '/src/Modules/Items/Models/ItemStore.php',
        'OlaHub\\UserPortal\\Models\\Language' => __DIR__ . '/../..' . '/src/CommonFiles/Models/Language.php',
        'OlaHub\\UserPortal\\Models\\LikedItems' => __DIR__ . '/../..' . '/src/Modules/Likes/Models/LikedItems.php',
        'OlaHub\\UserPortal\\Models\\ManyToMany\\ItemCountriesCategory' => __DIR__ . '/../..' . '/src/CommonFiles/Models/ItemCountriesCategory.php',
        'OlaHub\\UserPortal\\Models\\ManyToMany\\PaymentCountryRelation' => __DIR__ . '/../..' . '/src/Modules/Payments/Models/PaymentCountryRelation.php',
        'OlaHub\\UserPortal\\Models\\ManyToMany\\PaymentTypeRelation' => __DIR__ . '/../..' . '/src/Modules/Payments/Models/PaymentTypeRelation.php',
        'OlaHub\\UserPortal\\Models\\ManyToMany\\State' => __DIR__ . '/../..' . '/src/CommonFiles/Models/State.php',
        'OlaHub\\UserPortal\\Models\\ManyToMany\\exchRefundPolicyCountries' => __DIR__ . '/../..' . '/src/CommonFiles/Models/exchRefundPolicyCountries.php',
        'OlaHub\\UserPortal\\Models\\ManyToMany\\occasionCountries' => __DIR__ . '/../..' . '/src/CommonFiles/Models/occasionCountries.php',
        'OlaHub\\UserPortal\\Models\\Merchant' => __DIR__ . '/../..' . '/src/Modules/Items/Models/Merchant.php',
        'OlaHub\\UserPortal\\Models\\MerchantCategory' => __DIR__ . '/../..' . '/src/Modules/Items/Models/MerchantCategory.php',
        'OlaHub\\UserPortal\\Models\\MerchantInvite' => __DIR__ . '/../..' . '/src/CommonFiles/Models/MerchantInvite.php',
        'OlaHub\\UserPortal\\Models\\MessageTemplate' => __DIR__ . '/../..' . '/src/CommonFiles/Models/MessageTemplate.php',
        'OlaHub\\UserPortal\\Models\\Notifications' => __DIR__ . '/../..' . '/src/CommonFiles/Models/NotificationMongo.php',
        'OlaHub\\UserPortal\\Models\\Occasion' => __DIR__ . '/../..' . '/src/CommonFiles/Models/Occasion.php',
        'OlaHub\\UserPortal\\Models\\OlaHubCommonModelsHelper' => __DIR__ . '/../..' . '/src/CommonFiles/Models/OlaHubCommonModelsHelper.php',
        'OlaHub\\UserPortal\\Models\\PaymentMethod' => __DIR__ . '/../..' . '/src/Modules/Payments/Models/PaymentMethod.php',
        'OlaHub\\UserPortal\\Models\\PaymentShippingStatus' => __DIR__ . '/../..' . '/src/Modules/MyPurshasedItems/Models/PaymentShippingStatus.php',
        'OlaHub\\UserPortal\\Models\\PaymentType' => __DIR__ . '/../..' . '/src/Modules/Payments/Models/PaymentType.php',
        'OlaHub\\UserPortal\\Models\\Post' => __DIR__ . '/../..' . '/src/Modules/Posts/Models/Post.php',
        'OlaHub\\UserPortal\\Models\\PostComments' => __DIR__ . '/../..' . '/src/Modules/Posts/Models/PostComments.php',
        'OlaHub\\UserPortal\\Models\\PostLikes' => __DIR__ . '/../..' . '/src/Modules/Posts/Models/PostLikes.php',
        'OlaHub\\UserPortal\\Models\\PostReplies' => __DIR__ . '/../..' . '/src/Modules/Posts/Models/PostReplies.php',
        'OlaHub\\UserPortal\\Models\\PostReport' => __DIR__ . '/../..' . '/src/Modules/Posts/Models/PostReport.php',
        'OlaHub\\UserPortal\\Models\\PostShares' => __DIR__ . '/../..' . '/src/Modules/Posts/Models/PostShares.php',
        'OlaHub\\UserPortal\\Models\\RegistryGiftModel' => __DIR__ . '/../..' . '/src/Modules/Registry/Models/RegistryGiftModel.php',
        'OlaHub\\UserPortal\\Models\\RegistryModel' => __DIR__ . '/../..' . '/src/Modules/Registry/Models/RegistryModel.php',
        'OlaHub\\UserPortal\\Models\\RegistryUsersModel' => __DIR__ . '/../..' . '/src/Modules/Registry/Models/RegistryUsersModel.php',
        'OlaHub\\UserPortal\\Models\\PostVote' => __DIR__ . '/../..' . '/src/Modules/Posts/Models/PostVote.php',
        'OlaHub\\UserPortal\\Models\\SellWithUsUnsupport' => __DIR__ . '/../..' . '/src/CommonFiles/Models/SellWithUsUnsupport.php',
        'OlaHub\\UserPortal\\Models\\SharedItems' => __DIR__ . '/../..' . '/src/Modules/shares/Models/SharedItems.php',
        'OlaHub\\UserPortal\\Models\\ShippingCities' => __DIR__ . '/../..' . '/src/CommonFiles/Models/ShippingCities.php',
        'OlaHub\\UserPortal\\Models\\ShippingCountries' => __DIR__ . '/../..' . '/src/CommonFiles/Models/ShippingCountries.php',
        'OlaHub\\UserPortal\\Models\\ShippingRegions' => __DIR__ . '/../..' . '/src/CommonFiles/Models/ShippingRegions.php',
        'OlaHub\\UserPortal\\Models\\StaticPages' => __DIR__ . '/../..' . '/src/CommonFiles/Models/StaticPages.php',
        'OlaHub\\UserPortal\\Models\\StorePickups' => __DIR__ . '/../..' . '/src/Modules/Items/Models/StorePickups.php',
        'OlaHub\\UserPortal\\Models\\UserBill' => __DIR__ . '/../..' . '/src/Modules/MyPurshasedItems/Models/UserBill.php',
        'OlaHub\\UserPortal\\Models\\UserBillDetails' => __DIR__ . '/../..' . '/src/Modules/MyPurshasedItems/Models/UserBillDetails.php',
        'OlaHub\\UserPortal\\Models\\UserLoginsModel' => __DIR__ . '/../..' . '/src/Modules/Users/Models/UserLoginsModel.php',
        'OlaHub\\UserPortal\\Models\\UserModel' => __DIR__ . '/../..' . '/src/Modules/Users/Models/UserModel.php',
        'OlaHub\\UserPortal\\Models\\UserMongo' => __DIR__ . '/../..' . '/src/Modules/Users/Models/UserMongo.php',
        'OlaHub\\UserPortal\\Models\\UserNotificationNewItems' => __DIR__ . '/../..' . '/src/CommonFiles/Models/UserNotificationNewItems.php',
        'OlaHub\\UserPortal\\Models\\UserPoints' => __DIR__ . '/../..' . '/src/Modules/Users/Models/UserPoints.php',
        'OlaHub\\UserPortal\\Models\\UserSessionModel' => __DIR__ . '/../..' . '/src/Modules/Users/Models/UserSessionModel.php',
        'OlaHub\\UserPortal\\Models\\UserShippingAddressModel' => __DIR__ . '/../..' . '/src/Modules/Users/Models/UserShippingAddressModel.php',
        'OlaHub\\UserPortal\\Models\\UserVouchers' => __DIR__ . '/../..' . '/src/Modules/Users/Models/UserVouchers.php',
        'OlaHub\\UserPortal\\Models\\UsersReferenceCodeModel' => __DIR__ . '/../..' . '/src/Modules/Users/Models/UsersReferenceCodeModel.php',
        'OlaHub\\UserPortal\\Models\\UsersReferenceCodeUsedModel' => __DIR__ . '/../..' . '/src/Modules/Users/Models/UsersReferenceCodeUsedModel.php',
        'OlaHub\\UserPortal\\Models\\VotePostUser' => __DIR__ . '/../..' . '/src/Modules/Posts/Models/VotePostUser.php',
        'OlaHub\\UserPortal\\Models\\WishList' => __DIR__ . '/../..' . '/src/Modules/WishLists/Models/WishList.php',
        'OlaHub\\UserPortal\\Models\\groups' => __DIR__ . '/../..' . '/src/Modules/Groups/Models/groups.php',
        'OlaHub\\UserPortal\\Observers\\OlaHubCommonObserve' => __DIR__ . '/../..' . '/src/Observes/OlaHubCommonObserve.php',
        'OlaHub\\UserPortal\\Providers\\AppServiceProvider' => __DIR__ . '/../..' . '/src/Providers/AppServiceProvider.php',
        'OlaHub\\UserPortal\\Providers\\EventServiceProvider' => __DIR__ . '/../..' . '/src/Providers/EventServiceProvider.php',
        'OlaHub\\UserPortal\\Providers\\ValidationServiceProvider' => __DIR__ . '/../..' . '/src/Providers/ValidationServiceProvider.php',
        'OlaHub\\UserPortal\\ResponseHandlers\\AdsResponseHandler' => __DIR__ . '/../..' . '/src/Modules/Announcement/ResponseHandler/AdsResponseHandler.php',
        'OlaHub\\UserPortal\\ResponseHandlers\\BrandSearchResponseHandler' => __DIR__ . '/../..' . '/src/CommonFiles/ResponseHandler/BrandSearchResponseHandler.php',
        'OlaHub\\UserPortal\\ResponseHandlers\\BrandsResponseHandler' => __DIR__ . '/../..' . '/src/Modules/Items/ResponseHandler/BrandsResponseHandler.php',
        'OlaHub\\UserPortal\\ResponseHandlers\\CalendarForCelebrationResponseHandler' => __DIR__ . '/../..' . '/src/Modules/Calendar/ResponseHandler/CalendarForCelebrationResponseHandler.php',
        'OlaHub\\UserPortal\\ResponseHandlers\\CalendarsResponseHandler' => __DIR__ . '/../..' . '/src/Modules/Calendar/ResponseHandler/CalendarsResponseHandler.php',
        'OlaHub\\UserPortal\\ResponseHandlers\\CartResponseHandler' => __DIR__ . '/../..' . '/src/Modules/Cart/ResponseHandler/CartResponseHandler.php',
        'OlaHub\\UserPortal\\ResponseHandlers\\CartTotalsResponseHandler' => __DIR__ . '/../..' . '/src/Modules/Cart/ResponseHandler/CartTotalsResponseHandler.php',
        'OlaHub\\UserPortal\\ResponseHandlers\\CelebrationCommitResponseHandler' => __DIR__ . '/../..' . '/src/Modules/Celebration/ResponseHandler/CelebrationCommitResponseHandler.php',
        'OlaHub\\UserPortal\\ResponseHandlers\\CelebrationGiftDoneResponseHandler' => __DIR__ . '/../..' . '/src/Modules/Celebration/ResponseHandler/CelebrationGiftDoneResponseHandler.php',
        'OlaHub\\UserPortal\\ResponseHandlers\\CelebrationGiftResponseHandler' => __DIR__ . '/../..' . '/src/Modules/Celebration/ResponseHandler/CelebrationGiftResponseHandler.php',
        'OlaHub\\UserPortal\\ResponseHandlers\\CelebrationParticipantResponseHandler' => __DIR__ . '/../..' . '/src/Modules/Celebration/ResponseHandler/CelebrationParticipantResponseHandler.php',
        'OlaHub\\UserPortal\\ResponseHandlers\\CelebrationResponseHandler' => __DIR__ . '/../..' . '/src/Modules/Celebration/ResponseHandler/CelebrationResponseHandler.php',
        'OlaHub\\UserPortal\\ResponseHandlers\\ClassificationFilterResponseHandler' => __DIR__ . '/../..' . '/src/Modules/Items/ResponseHandler/ClassificationFilterResponseHandler.php',
        'OlaHub\\UserPortal\\ResponseHandlers\\ClassificationForPrequestFormsResponseHandler' => __DIR__ . '/../..' . '/src/Modules/Desginer/ResponseHandler/ClassificationForPrequestFormsResponseHandler.php',
        'OlaHub\\UserPortal\\ResponseHandlers\\ClassificationResponseHandler' => __DIR__ . '/../..' . '/src/Modules/Items/ResponseHandler/ClassificationResponseHandler.php',
        'OlaHub\\UserPortal\\ResponseHandlers\\ClassificationSearchResponseHandler' => __DIR__ . '/../..' . '/src/CommonFiles/ResponseHandler/ClassificationSearchResponseHandler.php',
        'OlaHub\\UserPortal\\ResponseHandlers\\CommunitiesForLandingPageResponseHandler' => __DIR__ . '/../..' . '/src/CommonFiles/ResponseHandler/CommunitiesForLandingPageResponseHandler.php',
        'OlaHub\\UserPortal\\ResponseHandlers\\CountriesCodeForPrequestFormsResponseHandler' => __DIR__ . '/../..' . '/src/CommonFiles/ResponseHandler/CountriesCodeForPrequestFormsResponseHandler.php',
        'OlaHub\\UserPortal\\ResponseHandlers\\CountriesForPrequestFormsResponseHandler' => __DIR__ . '/../..' . '/src/CommonFiles/ResponseHandler/CountriesForPrequestFormsResponseHandler.php',
        'OlaHub\\UserPortal\\ResponseHandlers\\DTemplatesResponseHandler' => __DIR__ . '/../..' . '/src/Console/Commands/templatesData/ResponseHandler/DTemplatesResponseHandler.php',
        'OlaHub\\UserPortal\\ResponseHandlers\\DesignerItemsSearchResponseHandler' => __DIR__ . '/../..' . '/src/CommonFiles/ResponseHandler/DesginerItemsSearchResponseHandler.php',
        'OlaHub\\UserPortal\\ResponseHandlers\\DesignersSearchResponseHandler' => __DIR__ . '/../..' . '/src/CommonFiles/ResponseHandler/DesignersSearchResponseHandler.php',
        'OlaHub\\UserPortal\\ResponseHandlers\\FriendsResponseHandler' => __DIR__ . '/../..' . '/src/Modules/Profile/ResponseHandler/FriendsResponseHandler.php',
        'OlaHub\\UserPortal\\ResponseHandlers\\GroupBrandsResponseHandler' => __DIR__ . '/../..' . '/src/Modules/Groups/ResponseHandler/GroupBrandsResponseHandler.php',
        'OlaHub\\UserPortal\\ResponseHandlers\\GroupSearchResponseHandler' => __DIR__ . '/../..' . '/src/CommonFiles/ResponseHandler/GroupSearchResponseHandler.php',
        'OlaHub\\UserPortal\\ResponseHandlers\\HeaderDataResponseHandler' => __DIR__ . '/../..' . '/src/Modules/Users/ResponseHandler/HeaderDataResponseHandler.php',
        'OlaHub\\UserPortal\\ResponseHandlers\\InterestsForPrequestFormsResponseHandler' => __DIR__ . '/../..' . '/src/CommonFiles/ResponseHandler/InterestsForPrequestFormsResponseHandler.php',
        'OlaHub\\UserPortal\\ResponseHandlers\\InterestsHomeResponseHandler' => __DIR__ . '/../..' . '/src/Modules/Items/ResponseHandler/InterestsHomeResponseHandler.php',
        'OlaHub\\UserPortal\\ResponseHandlers\\ItemCategoryForPrequestFormsResponseHandler' => __DIR__ . '/../..' . '/src/Modules/Desginer/ResponseHandler/ItemCategoryForPrequestFormsResponseHandler.php',
        'OlaHub\\UserPortal\\ResponseHandlers\\ItemResponseHandler' => __DIR__ . '/../..' . '/src/Modules/Items/ResponseHandler/ItemResponseHandler.php',
        'OlaHub\\UserPortal\\ResponseHandlers\\ItemReviewsResponseHandler' => __DIR__ . '/../..' . '/src/Modules/Items/ResponseHandler/ItemReviewsResponseHandler.php',
        'OlaHub\\UserPortal\\ResponseHandlers\\ItemSearchResponseHandler' => __DIR__ . '/../..' . '/src/CommonFiles/ResponseHandler/ItemSearchResponseHandler.php',
        'OlaHub\\UserPortal\\ResponseHandlers\\ItemsListResponseHandler' => __DIR__ . '/../..' . '/src/Modules/Items/ResponseHandler/ItemsListResponseHandler.php',
        'OlaHub\\UserPortal\\ResponseHandlers\\LikedItemsResponseHandler' => __DIR__ . '/../..' . '/src/Modules/Likes/ResponseHandler/LikedItemsResponseHandler.php',
        'OlaHub\\UserPortal\\ResponseHandlers\\MainGroupResponseHandler' => __DIR__ . '/../..' . '/src/Modules/Groups/ResponseHandler/MainGroupResponseHandler.php',
        'OlaHub\\UserPortal\\ResponseHandlers\\MembersResponseHandler' => __DIR__ . '/../..' . '/src/Modules/Groups/ResponseHandler/MembersResponseHandler.php',
        'OlaHub\\UserPortal\\ResponseHandlers\\MyFriendsResponseHandler' => __DIR__ . '/../..' . '/src/Modules/Users/ResponseHandler/MyFriendsResponseHandler.php',
        'OlaHub\\UserPortal\\ResponseHandlers\\NotLoginCartResponseHandler' => __DIR__ . '/../..' . '/src/Modules/Cart/ResponseHandler/NotLoginCartResponseHandler.php',
        'OlaHub\\UserPortal\\ResponseHandlers\\OccasionsHomeResponseHandler' => __DIR__ . '/../..' . '/src/CommonFiles/ResponseHandler/OccasionsHomeResponseHandler.php',
        'OlaHub\\UserPortal\\ResponseHandlers\\OccasionsResponseHandler' => __DIR__ . '/../..' . '/src/Modules/Items/ResponseHandler/OccasionsResponseHandler.php',
        'OlaHub\\UserPortal\\ResponseHandlers\\OccassionsForPrequestFormsResponseHandler' => __DIR__ . '/../..' . '/src/Modules/Calendar/ResponseHandler/OccassionsForPrequestFormsResponseHandler.php',
        'OlaHub\\UserPortal\\ResponseHandlers\\PaymentResponseHandler' => __DIR__ . '/../..' . '/src/Modules/Payments/ResponseHandler/PaymentResponseHandler.php',
        'OlaHub\\UserPortal\\ResponseHandlers\\PostsResponseHandler' => __DIR__ . '/../..' . '/src/Modules/Posts/ResponseHandler/PostsResponseHandler.php',
        'OlaHub\\UserPortal\\ResponseHandlers\\ProfileInfoResponseHandler' => __DIR__ . '/../..' . '/src/Modules/Users/ResponseHandler/ProfileInfoResponseHandler.php',
        'OlaHub\\UserPortal\\ResponseHandlers\\PurchasedItemResponseHandler' => __DIR__ . '/../..' . '/src/Modules/MyPurshasedItems/ResponseHandler/PurchasedItemResponseHandler.php',
        'OlaHub\\UserPortal\\ResponseHandlers\\PurchasedItemsResponseHandler' => __DIR__ . '/../..' . '/src/Modules/MyPurshasedItems/ResponseHandler/PurchasedItemsResponseHandler.php',
        'OlaHub\\UserPortal\\ResponseHandlers\\RegistryGiftResponseHandler' => __DIR__ . '/../..' . '/src/Modules/Registry/ResponseHandler/RegistryGiftResponseHandler.php',
        'OlaHub\\UserPortal\\ResponseHandlers\\RegistryParticipantResponseHandler' => __DIR__ . '/../..' . '/src/Modules/Registry/ResponseHandler/RegistryParticipantResponseHandler.php',
        'OlaHub\\UserPortal\\ResponseHandlers\\RegistryResponseHandler' => __DIR__ . '/../..' . '/src/Modules/Registry/ResponseHandler/RegistryResponseHandler.php',
        'OlaHub\\UserPortal\\ResponseHandlers\\UpcomingEventsResponseHandler' => __DIR__ . '/../..' . '/src/Modules/Profile/ResponseHandler/UpcomingEventsResponseHandler.php',
        'OlaHub\\UserPortal\\ResponseHandlers\\UserBalanceDetailsResponseHandler' => __DIR__ . '/../..' . '/src/Modules/Users/ResponseHandler/UserBalanceDetailsResponseHandler.php',
        'OlaHub\\UserPortal\\ResponseHandlers\\UserSearchResponseHandler' => __DIR__ . '/../..' . '/src/CommonFiles/ResponseHandler/UserSearchResponseHandler.php',
        'OlaHub\\UserPortal\\ResponseHandlers\\UsersResponseHandler' => __DIR__ . '/../..' . '/src/Modules/Users/ResponseHandler/UsersResponseHandler.php',
        'OlaHub\\UserPortal\\ResponseHandlers\\WishListsResponseHandler' => __DIR__ . '/../..' . '/src/Modules/WishLists/ResponseHandler/WishListsResponseHandler.php',
        'OlaHub\\UserPortal\\ResponseHandlers\\searchUsersForPrequestFormsResponseHandler' => __DIR__ . '/../..' . '/src/CommonFiles/ResponseHandler/searchUsersForPrequestFormsResponseHandler.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->classMap = ComposerStaticInitbba53004a0b53843540453fe6dd181df::$classMap;

        }, null, ClassLoader::class);
    }
}
