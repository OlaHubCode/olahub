<?php

namespace OlaHub\UserPortal\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Http\Request;

class CronController extends BaseController {

    protected $requestData;
    protected $requestFilter;
    private $cartModel;

    public function __construct(Request $request) {
        $return = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::getRequest($request);
        $this->requestData = $return['requestData'];
        $this->requestFilter = $return['requestFilter'];
    }

    public function publishSpecificCelebration($id) {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Crons", 'function_name' => "publishSpecificCelebration"]);
       
        $celebration = \OlaHub\UserPortal\Models\CelebrationModel::find($id);
        $celebration->celebration_status = 5;
        $celebration->save();
        $celebrationOwner = \OlaHub\UserPortal\Models\UserModel::where('id', $celebration->user_id)->first();
        if (!$celebrationOwner) {
            $celebrationOwner = \OlaHub\UserPortal\Models\UserModel::withoutGlobalScope('notTemp')->where('id', $celebration->user_id)->first();
            $password = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::randomString(6);
            $celebrationOwner->password = $password;
            $celebrationOwner->save();
            if ($celebrationOwner->mobile_no && $celebrationOwner->email) {
                (new \OlaHub\UserPortal\Helpers\EmailHelper)->sendNotRegisterPublishedCelebrationOwner($celebrationOwner, $celebration->title, $celebration->id, $password);
                (new \OlaHub\UserPortal\Helpers\SmsHelper)->sendNotRegisterPublishedCelebrationOwner($celebrationOwner, $celebration->title, $celebration->id, $password);
            } else if ($celebrationOwner->mobile_no) {
                (new \OlaHub\UserPortal\Helpers\SmsHelper)->sendNotRegisterPublishedCelebrationOwner($celebrationOwner, $celebration->title, $celebration->id, $password);
            } else if ($celebrationOwner->email) {
                (new \OlaHub\UserPortal\Helpers\EmailHelper)->sendNotRegisterPublishedCelebrationOwner($celebrationOwner, $celebration->title, $celebration->id, $password);
            }
        } else {
            if ($celebrationOwner->mobile_no && $celebrationOwner->email) {
                (new \OlaHub\UserPortal\Helpers\EmailHelper)->sendPublishedCelebration($celebrationOwner, $celebration->title, $celebration->id);
                (new \OlaHub\UserPortal\Helpers\SmsHelper)->sendPublishedCelebration($celebrationOwner, $celebration->title, $celebration->id);
            } else if ($celebrationOwner->mobile_no) {
                (new \OlaHub\UserPortal\Helpers\SmsHelper)->sendPublishedCelebration($celebrationOwner, $celebration->title, $celebration->id);
            } else if ($celebrationOwner->email) {
                (new \OlaHub\UserPortal\Helpers\EmailHelper)->sendPublishedCelebration($celebrationOwner, $celebration->title, $celebration->id);
            }
        }
        $participants = \OlaHub\UserPortal\Models\CelebrationParticipantsModel::where('celebration_id', $celebration->id)->get();
        foreach ($participants as $participant) {
            $participantData = \OlaHub\UserPortal\Models\UserModel::where('id', $participant->user_id)->first();
            $notification = new \OlaHub\UserPortal\Models\NotificationMongo();
            $notification->type = 'celebration';
            $notification->content = "notifi_publishCelebration";
            $notification->user_name = $celebrationOwner->first_name . " " . $celebrationOwner->last_name;
            $notification->celebration_id = $celebration->id;
            $notification->celebration_title = $celebration->title;
            $notification->avatar_url = $participantData->profile_picture;
            $notification->read = 0;
            $notification->for_user = $participantData->id;
            $notification->save();
        }
    }

    public function pendingPaysActions() {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Crons", 'function_name' => "pendingPaysActions"]);
       
        $timeException = strtotime("-15 minutes", time());
        $dateTimeException = date("Y-m-d H:i:s", $timeException);
        $payStatusesData = \OlaHub\UserPortal\Models\PaymentShippingStatus::where("action_id", "!=", "0")->where("cycle_order", "1")->get();
        $payStatusesId = [];
        foreach ($payStatusesData as $statusId){
            $payStatusesId[] = $statusId->id;
        }
        $pindingPays = \OlaHub\UserPortal\Models\UserBill::withoutGlobalScope("currntUser")->where("billing_date", "<=", $dateTimeException)
                        ->whereIn("pay_status",$payStatusesId)
                        ->where("paid_by", "not like", "%255%")->get();
        foreach ($pindingPays as $bill) {
            $cart = \OlaHub\UserPortal\Models\Cart::WithoutGlobalScope("countryUser")->find($bill->temp_cart_id);
            $payStatuse = \OlaHub\UserPortal\Models\PaymentShippingStatus::find($bill->pay_status);
            if (!$cart || !$payStatuse) {
                continue;
            }
            $failStatus = \OlaHub\UserPortal\Models\PaymentShippingStatus::where("action_id", $payStatuse->action_id)->where("is_fail", "1")->first();
            if ($bill->voucher_used > 0) {
                $userVoucherAccount = \OlaHub\UserPortal\Models\UserVouchers::withoutGlobalScope("voucherCountry")
                                ->where('user_id', $bill->user_id)
                                ->where("country_id", $cart->country_id)->first();
                if (!$userVoucherAccount) {
                    $userVoucherAccount = new \OlaHub\UserPortal\Models\UserVouchers;
                    $userVoucherAccount->user_id = $bill->user_id;
                    $userVoucherAccount->voucher_balance = 0;
                    $userVoucherAccount->country_id = $cart->country_id;
                    $userVoucherAccount->save();
                }
                $userVoucherAccount->voucher_balance += $bill->voucher_used;
                $userVoucherAccount->save();
                $bill->voucher_used = 0;
                $bill->voucher_after_pay = 0;
            }
            if ($bill->points_used > 0) {
                $userInsert = new \OlaHub\UserPortal\Models\UserPoints;
                $userInsert->user_id = $bill->user_id;
                $userInsert->country_id = $cart->country_id;
                $userInsert->campign_id = 0;
                $userInsert->points_collected = $bill->points_used;
                $userInsert->collect_date = date("Y-m-d");
                $userInsert->save();
                $bill->points_used = 0;
                $bill->points_used_curr = 0;
            }
            if ($bill->promo_code_id > 0) {
                $userInsert = \OlaHub\UserPortal\Models\CouponUsers::withoutGlobalScope("couponUser")
                        ->where("promo_code_id", $bill->promo_code_id)
                        ->where("user_id", $bill->user_id)
                        ->first();
                if($userInsert){
                    if($userInsert->number_of_use > 1){
                        $userInsert->number_of_use -= 1;
                    }else{
                        $userInsert->delete();
                    }
                }
                $bill->promo_code_id = 0;
            }

            $bill->pay_status = $failStatus->id;
//            $bill->pay_result = "not_completed_payment";
            $bill->save();
        }
    }

    public function publishCelebration() {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Crons", 'function_name' => "publishCelebration"]);
       
        $celebrations = \OlaHub\UserPortal\Models\CelebrationModel::where('celebration_status', 4)->where('celebration_date', date("Y-m-d"))->get();
        if (count($celebrations) > 0) {
            foreach ($celebrations as $celebration) {
                $celebration->celebration_status = 5;
                $celebration->save();
                $celebrationOwner = \OlaHub\UserPortal\Models\UserModel::where('id', $celebration->user_id)->first();
                if (!$celebrationOwner) {
                    $celebrationOwner = \OlaHub\UserPortal\Models\UserModel::withoutGlobalScope('notTemp')->where('id', $celebration->user_id)->first();
                    $password = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::randomString(6);
                    $celebrationOwner->password = $password;
                    $celebrationOwner->save();
                    if ($celebrationOwner->mobile_no && $celebrationOwner->email) {
                        (new \OlaHub\UserPortal\Helpers\EmailHelper)->sendNotRegisterPublishedCelebrationOwner($celebrationOwner, $celebration->title, $celebration->id, $password);
                        (new \OlaHub\UserPortal\Helpers\SmsHelper)->sendNotRegisterPublishedCelebrationOwner($celebrationOwner, $celebration->title, $celebration->id, $password);
                    } else if ($celebrationOwner->mobile_no) {
                        (new \OlaHub\UserPortal\Helpers\SmsHelper)->sendNotRegisterPublishedCelebrationOwner($celebrationOwner, $celebration->title, $celebration->id, $password);
                    } else if ($celebrationOwner->email) {
                        (new \OlaHub\UserPortal\Helpers\EmailHelper)->sendNotRegisterPublishedCelebrationOwner($celebrationOwner, $celebration->title, $celebration->id, $password);
                    }
                } else {
                    if ($celebrationOwner->mobile_no && $celebrationOwner->email) {
                        (new \OlaHub\UserPortal\Helpers\EmailHelper)->sendPublishedCelebration($celebrationOwner, $celebration->title, $celebration->id);
                        (new \OlaHub\UserPortal\Helpers\SmsHelper)->sendPublishedCelebration($celebrationOwner, $celebration->title, $celebration->id);
                    } else if ($celebrationOwner->mobile_no) {
                        (new \OlaHub\UserPortal\Helpers\SmsHelper)->sendPublishedCelebration($celebrationOwner, $celebration->title, $celebration->id);
                    } else if ($celebrationOwner->email) {
                        (new \OlaHub\UserPortal\Helpers\EmailHelper)->sendPublishedCelebration($celebrationOwner, $celebration->title, $celebration->id);
                    }
                }
                $participants = \OlaHub\UserPortal\Models\CelebrationParticipantsModel::where('celebration_id', $celebration->id)->get();
                foreach ($participants as $participant) {
                    $participantData = \OlaHub\UserPortal\Models\UserModel::where('id', $participant->user_id)->first();
                    $notification = new \OlaHub\UserPortal\Models\NotificationMongo();
                    $notification->type = 'celebration';
                    $notification->content = "notifi_publishCelebration";
                    $notification->user_name = $celebrationOwner->first_name . " " . $celebrationOwner->last_name;
                    $notification->celebration_id = $celebration->id;
                    $notification->celebration_title = $celebration->title;
                    $notification->avatar_url = $participantData->profile_picture;
                    $notification->read = 0;
                    $notification->for_user = $participantData->id;
                    $notification->save();
                }
            }
        }
    }

    public function scheduleCelebration() {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Crons", 'function_name' => "scheduleCelebration"]);
       
        $celebrations = \OlaHub\UserPortal\Models\CelebrationModel::where('celebration_status', 3)->where('celebration_date', "<=", date("Y-m-d", strtotime("+3 days")))->get();
        if (count($celebrations) > 0) {
            foreach ($celebrations as $celebration) {
                $celebration->celebration_status = 4;
                $celebration->original_celebration_date = "";
                $celebration->save();
                $participantsData = \OlaHub\UserPortal\Models\CelebrationParticipantsModel::where("celebration_id", $celebration->id)->get();
                $participantsId = [];
                foreach ($participantsData as $participant) {
                    $participantsId[] = $participant->user_id;
                }
                $celebrationParticipants = \OlaHub\UserPortal\Models\UserModel::whereIn('id', $participantsId)->get();
                $celebrationOwner = \OlaHub\UserPortal\Models\UserModel::withoutGlobalScope('notTemp')->where('id', $celebration->user_id)->first();
                foreach ($celebrationParticipants as $oneUser) {
                    if ($oneUser->mobile_no && $oneUser->email) {
                        (new \OlaHub\UserPortal\Helpers\EmailHelper)->sendScheduleCelebration($oneUser, $celebration->title, $celebration->id, $celebrationOwner->first_name . ' ' . $celebrationOwner->last_name);
                        (new \OlaHub\UserPortal\Helpers\SmsHelper)->sendScheduleCelebration($oneUser, $celebration->title, $celebration->id, $celebrationOwner->first_name . ' ' . $celebrationOwner->last_name);
                    } else if ($oneUser->mobile_no) {
                        (new \OlaHub\UserPortal\Helpers\SmsHelper)->sendScheduleCelebration($oneUser, $celebration->title, $celebration->id, $celebrationOwner->first_name . ' ' . $celebrationOwner->last_name);
                    } else if ($oneUser->email) {
                        (new \OlaHub\UserPortal\Helpers\EmailHelper)->sendScheduleCelebration($oneUser, $celebration->title, $celebration->id, $celebrationOwner->first_name . ' ' . $celebrationOwner->last_name);
                    }
                }

                $creatorBill = \OlaHub\UserPortal\Models\UserBill::withoutGlobalScope("currntUser")->where("pay_for", $celebration->id)->where("user_id", $celebration->created_by)->first();
                $creatorBillDetails = $creatorBill->billDetails;
                $grouppedMers = \OlaHub\UserPortal\Helpers\PaymentHelper::groupBillMerchants($creatorBillDetails);
                (new \OlaHub\UserPortal\Helpers\EmailHelper)->sendMerchantScheduledOrderCelebration($grouppedMers, $creatorBill, $celebration, $celebrationOwner);
                (new \OlaHub\UserPortal\Helpers\EmailHelper)->sendSalesScheduledOrderCelebration($grouppedMers, $creatorBill, $celebration, $celebrationOwner);
            }
        }
    }

    public function autoCancelPayment() {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Crons", 'function_name' => "autoCancelPayment"]);
       
        $celebrations = \OlaHub\UserPortal\Models\CelebrationModel::where('celebration_status', 2)->where('commit_date', "<=", date("Y-m-d h:i:s", strtotime("-1 day")))->get();
        foreach ($celebrations as $celebration) {
            $celebration->celebration_status = 1;
            $celebration->commit_date = NULL;
            $celebration->save();
            $participants = \OlaHub\UserPortal\Models\CelebrationParticipantsModel::where('celebration_id', $celebration->id)->get();
            foreach ($participants as $participant) {
                if ($participant->payment_status == 3) {
                    
                }
                $participant->payment_status = 1;
                $participant->save();

                $notification = new \OlaHub\UserPortal\Models\NotificationMongo();
                $notification->type = 'celebration';
                $notification->content = "notifi_paymentCancellation";
                $notification->celebration_title = $celebration->title;
                $notification->user_name = '';
                $notification->celebration_id = $celebration->id;
                $notification->avatar_url = FRONT_URL."/images/logo.png";
                $notification->read = 0;
                $notification->for_user = $participant->user_id;
                $notification->save();
            }

            $cart = \OlaHub\UserPortal\Models\Cart::withoutGlobalScope('countryUser')->where('celebration_id', $celebration->id)->first();
            $cartItems = \OlaHub\UserPortal\Models\CartItems::withoutGlobalScope('countryUser')->where('shopping_cart_id', $cart->id)->get();
            if (count($cartItems->toArray()) > 0) {
                foreach ($cartItems as $cartItem) {
                    $cartItem->is_approved = 0;
                    $cartItem->save();
                }
            }
            (new \OlaHub\UserPortal\Helpers\CelebrationHelper)->saveCelebrationCart($celebration);
        }
    }

    public function sendGiftsToTarget() {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Crons", 'function_name' => "sendGiftsToTarget"]);
       
        $time = strtotime("-1 hour");
        $date = date("Y-m-d", $time);
        $bills = \OlaHub\UserPortal\Models\UserBill::where("gift_date", "<=", $date)
                ->where("gift_message_sent", "0")
                ->where("pay_status", "2")
                ->get();
        foreach ($bills as $bill) {
            if (!$bill->gift_date) {
                continue;
            }
            $bill->gift_message_sent = "1";
            $bill->save();
            $grouppedMers = \OlaHub\UserPortal\Helpers\PaymentHelper::groupBillMerchants($bill->billDetails);
            $shipping = unserialize($bill->order_address);
            $targetID = isset($shipping['for_user']) ? $shipping['for_user'] : NULL;
            $target = \OlaHub\UserPortal\Models\UserModel::withOutGlobalScope('notTemp')->find($targetID);
            $buyer = \OlaHub\UserPortal\Models\UserModel::where("id", $bill->user_id)->first();
            if (\OlaHub\UserPortal\Models\UserModel::checkTempUser($target)) {
                if ($target->email) {
                    (new \OlaHub\UserPortal\Helpers\EmailHelper)->sendNoneRegisteredTargetUserOrderGift($buyer, $bill, $grouppedMers, $target);
                } elseif ($target->mobile_no) {
                    (new \OlaHub\UserPortal\Helpers\SmsHelper)->sendNoneRegisteredTargetUserOrderGift($buyer, $bill, $grouppedMers, $target);
                }
            } else {
                if ($target->email) {
                    (new \OlaHub\UserPortal\Helpers\EmailHelper)->sendRegisteredTargetUserOrderGift($buyer, $bill, $grouppedMers, $target);
                } elseif ($target->mobile_no) {
                    (new \OlaHub\UserPortal\Helpers\SmsHelper)->sendRegisteredTargetUserOrderGift($buyer, $bill, $grouppedMers, $target);
                }
            }
        }
    }

    public function communitiesAddData() {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Crons", 'function_name' => "communitiesAddData"]);
       
        $communities = \OlaHub\UserPortal\Models\groups::get();
        foreach ($communities as $community) {
            if (!isset($community->posts_approve)) {
                $community->posts_approve = 0;
            }
            if (!isset($community->onlyMyStores)) {
                $community->onlyMyStores = 0;
            }
            $community->save();
        }
    }

    public function postsAddData() {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Crons", 'function_name' => "postsAddData"]);
       
        $posts = \OlaHub\UserPortal\Models\Post::get();
        foreach ($posts as $post) {
            if (!isset($post->isApprove)) {
                $post->isApprove = 1;
            }
            $post->save();
        }
    }

    public function usersPhoneNumbersChange() {
         $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Crons", 'function_name' => "usersPhoneNumbersChange"]);
       
        $users = \OlaHub\UserPortal\Models\UserModel::paginate(1000);
        foreach ($users as $user) {
            $user->mobile_no = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::rightPhoneNoJO($user->mobile_no);
            $user->save();
        }
    }
    
    public function updateCommunitiesTotalMembers(){
         $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Crons", 'function_name' => "updateCommunitiesTotalMembers"]);
       
        $communities = \OlaHub\UserPortal\Models\groups::get();
        foreach ($communities as $communitiy){
            if(!$communitiy->total_members || ($communitiy->total_members != count($communitiy->members))){
                $communitiy->total_members = count($communitiy->members);
                $communitiy->save();
            }
        }
    }

public function updateXiaomiItem(){
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Crons", 'function_name' => "updateXiaomiItem"]);
       
        $today = time();
        $lunchDate = strtotime("2019-05-21");
        $diff = round(($lunchDate - $today) / (60 * 60 * 24));
        if($diff >= 3){
            $item = \OlaHub\UserPortal\Models\CatalogItem::find(41158);
            $item->estimated_shipping_time = $diff + 1;
            $item->save();
        }
    }
    
    public function updateItemSlugUnique($slug){
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Crons", 'function_name' => "updateItemSlugUnique"]);
       
        $items = \OlaHub\UserPortal\Models\CatalogItem::withoutGlobalScope("published")->withoutGlobalScope("country")->where("item_slug", $slug)->get();
        if($items->count() > 1){
            foreach ($items as $item){
                $item->item_slug = $item->item_slug . "-$item->id";
                $item->save();
            }
            
            $posts = \OlaHub\UserPortal\Models\Post::where("item_slug", $slug)->get();
            if($posts->count() > 0){
                foreach ($posts as $post){
                    $image = explode("/",$post->post_image);
                    $id = $post[1];
                    $post->item_slug = $post->item_slug."-$id";
                    $post->save();
                }
            }
        }
    }
    
    public function updateCountriesCode(){
        $countries = \DB::table("shipping_countries")->get();
        foreach ($countries as $country){
            $olaHubCountry = \DB::table("countries")->where("two_letter_iso_code", strtoupper($country->code))->first();
            if($olaHubCountry){
                \DB::table("shipping_countries")->where("id", $country->id)->update(["olahub_country_id" => $olaHubCountry->id]);
            }
        }
    }

}
