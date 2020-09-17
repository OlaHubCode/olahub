<?php

namespace OlaHub\UserPortal\Models;

use Illuminate\Database\Eloquent\Model;

class Notifications extends Model
{

    protected $table = 'users_notifications';


    public function userData()
    {
        return $this->hasMany('OlaHub\UserPortal\Models\UserModel', 'id', 'friend_id');
    }
    public function registryData()
    {
        return $this->hasMany('OlaHub\UserPortal\Models\RegistryModel', 'id', 'registry_id');
    }

    public function groupData()
    {
        return $this->hasMany('OlaHub\UserPortal\Models\groups', 'id', 'group_id');
    }

    public function celebrationData()
    {
        return $this->hasMany('OlaHub\UserPortal\Models\CelebrationModel', 'id', 'celebration_id');
    }



    static function sendFCM($user_id, $key, $data, $lang = 'en', $title = NULL, $word = NULL, $word2 = NULL)
    {
        $user = app('session')->get('tempData');
        $username = "$user->first_name $user->last_name";

        $url = 'https://fcm.googleapis.com/fcm/send';
        $fields = array(
            'to' => '/topics/OlaHubFCM-' . $user_id,
            'priority' => "high",
            'restricted_package_name' => "com.olahub.app",
            'notification' => array(
                'sound' => "default",
                'click_action' => "FCM_PLUGIN_ACTIVITY",
                "title" => $title ? $title : $username,
                "body" => Notifications::translate($key, $lang, $word, $word2),
            ),
            'data' => $data,
        );
        $fields = json_encode($fields);
        $headers = array(
            'Authorization: key=AIzaSyDhg2nMPJCtrHtxdfPWfY3jWxgxivbjAIw',
            'Content-Type: application/json',
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);

        curl_exec($ch);
        curl_close($ch);
    }

    static function translate($key, $lang, $word = NULL, $word2 = NULL)
    {
        $langs = new \stdClass;
        $langs->en = [
            "chat_days" => $word . ($word > 1 ? " Days" : " Day") . " ago",
            "chat_hours" => $word . ($word > 1 ? " Hours" : " Hour") . " ago",
            "chat_minutes" => $word . ($word > 1 ? " Minutes" : " Minute") . " ago",
            "chat_seconds" => ($word > 5 ? $word . " Seconds ago" : "Just now"),
            "friend_request" => "Sent you a friend request",
            "accept_request" => "Accepted your friend request",
            "post_like" => "Liked your post",
            "post_comment" => "Commented on your post",
            "post_reply" => "Replied to your comment in a post",
            "cel_part_add" => "Added you to the " . $word . " celebration",
            "registry_part_add" => "Added you to the " . $word . " registry",
            "cel_part_remove" => "Removed you from the " . $word . " celebration",
            "registry_part_remove" => "Removed you from the " . $word . " registry",
            "accept_celebration" => "Accepted the invitation to the " . $word . " celebration",
            "reject_celebration" => "Rejected the invitation to the " . $word . " celebration",
            "leave_celebration" => "Just left the " . $word . " celebration",
            "add_gift" => "Added new gift to the " . $word . " celebration",
            "remove_gift" => "Removed a gift from the " . $word . " celebration",
            "like_gift" => "Liked a gift in the " . $word . " celebration",
            "commit_celebration" => "Commited the " . $word . " celebration",
            "uncommit_celebration" => "Uncommited the " . $word . " celebration",
            "upload_video" => "Uploaded a new video to the " . $word . " celebration",
            "upload_media" => "Uploaded a media to the " . $word . " celebration",
            "payment_celebration" => "Paid his share into the " . $word . " celebration",
            "join_group" => $word . " joined your community",
            "accept_member" => $word . " accepted your request to join community " . $word2,
            "invite_group" => $word . " invited you to join community " . $word2,
            "ask_group" => $word . " asked to join your community",
            "add_post" => $word . " added new post in your community",
            "add_post_friend" => $word . " added a new post in your timeline",
            "notifi_user_vote_Post" => $word . "Created  a new vote",
            "notifi_user_new_Post" => $word . "added a new post",
            "notifi_vote_on_post" => $word . "voted on your post",
            "notifi_post_like_for_follower" =>  "Liked " . $word . "post",
            "notifi_post_comment_for_follower" =>  " commented on " . $word . " post",
            "notifi_mention_post" =>  $word . "mentioned you in his post"

        ];
        $langs->ar = [
            "chat_days" => "منذ " . ($word > 10 ? $word . " يوم" : ($word > 1 ? $word . " أيام" : " يوم")),
            "chat_hours" => "منذ " . ($word > 10 ? $word . " ساعة" : ($word > 1 ? $word . " ساعات" : " ساعة")),
            "chat_minutes" => "منذ " . ($word > 10 ? $word . " دقيقة" : ($word > 1 ? $word . " دقائق" : " دقيقة")),
            "chat_seconds" => "منذ " . ($word > 10 ? $word . " ثانية" : ($word > 5 ? $word . " ثوان" : " لحظات")),
            "friend_request" => "أرسل لك طلب صداقة",
            "accept_request" => "وافق على طلب الصداقة",
            "post_like" => "أعجب بمنشورك",
            "post_comment" => "قام بالتعليق على منشورك",
            "post_reply" => "رد على تعليق لك في منشور",
            "cel_part_add" => "قام بإضافتك إلى الإحتفال " . $word,
            "registry_part_add" => "قام بإضافتك إلى الدعوة " . $word,
            "cel_part_remove" => "قام بإزالتك من الإحتفال " . $word,
            "registry_part_remove" => "قام بإزالتك من الدعوة " . $word,
            "accept_celebration" => "وافق على طلب الإنضمام إلى الإحتفال " . $word,
            "reject_celebration" => "رفض  طلب الإنضمام إلى الإحتفال " . $word,
            "leave_celebration" => "غادر الإحتفال" . $word,
            "add_gift" => "قام بإضافة هدية جديدة إلى الإحتفال " . $word,
            "remove_gift" => "قام بإزالة هدية من الإحتفال " . $word,
            "like_gift" => "أعجب بهدية في الإحتفال " . $word,
            "commit_celebration" => "لقد اختار الهديه / الهدايا للاحتفال " . $word . ". تحتاج إلى دفع حصتك من سعر الهدايا في غضون 24 ساعة.",
            "uncommit_celebration" => "لقد قام بحذف هديه من الاحتفال " . $word . " . الآن يمكنك اختيار هدايا أخرى للمتابعة.",
            "upload_video" => "قام برفع فيديو جديدة إلى الإحتفال " . $word,
            "upload_media" => "قام برفع ميديا جديدة إلى الإحتفال " . $word,
            "payment_celebration" => "قام بدفع حصته في الإحتفال " . $word,
            "join_group" => "قام " . $word . " بالإنضمام إلى مجتمع",
            "accept_member" => "وافق " . $word . " على طلب الإنضمام إلى مجتمع " . $word2,
            "invite_group" => "قام " . $word . " بدعوتك بالإنضمام إلى مجتمع " . $word2,
            "ask_group" => "قام " . $word . " بطلب دعوة بالإنضمام إلى مجتمعك",
            "add_post" => "قام " . $word . " بإضافة منشور جديد إلى مجتمع",
            "add_post_friend" => "قام " . $word . " بإضافة منشور جديد إلى يومياتك",
            "notifi_user_new_Post" => " قام " . $word . " بإضافة منشور جديد",
            "notifi_user_vote_Post" => " قام " . $word . " بإنشاء تصويت جديد",
            "notifi_vote_on_post" => " قام " . $word . " بتصويت على منشورك",
            "notifi_post_like_for_follower" => " أعجب بمنشور" . $word,
            "notifi_post_comment_for_follower" => "علق على منشور" . $word,
            "notifi_mention_post" => " قام " . $word . " بلإشارة اليك في منشوره ",



        ];
        return $key ? $langs->$lang[$key] : $langs->$lang['en'];
    }
}
