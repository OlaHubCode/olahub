<?php

namespace OlaHub\UserPortal\Libraries;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use OlaHub\UserPortal\Models\MessageTemplate;
use OlaHub\UserPortal\Helpers\OlaHubCommonHelper;

class OlaHubNotificationHelper {

    public $type = 'email';
    public $template_code;
    public $to;
    public $cc;
    public $replace;
    public $replace_with;
    private $body;
    private $subject;
    private $final_to;
    private $final_cc = [];
    private $template_data;
    private $notifTypes = [
        'email',
        'sms',
    ];
    private $typesMapping = [
        'email' => ['1', '3'],
        'sms' => ['2', '3'],
    ];

    public function __construct() {
        
    }

    public function send() {
        if (in_array($this->type, $this->notifTypes)) {
            $this->{'send' . $this->type}();
        }
    }

    protected function sendemail() {
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Send Email"]);
        $this->getTemplateData();
        $this->getSubjectData();
        $this->getBodyData();
        $this->getTo();
        $this->getCC();
        $sendEmail = new \OlaHub\UserPortal\Libraries\SendEmails;
        $sendEmail->subject = $this->subject;
        $sendEmail->body = $this->body;
        $sendEmail->to = $this->final_to;
        $sendEmail->ccMail = $this->final_cc;
        $sendEmail->send();
    }

    protected function sendsms() {
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Send SMS"]);
        $this->getTemplateData();
        $this->getBodyData();
        $this->getTo();
        $username = 'ohub_api';
        $password = 'oL_Aa$d_2$%d';
        $sendSmsApiUrl = "https://bulksms.arabiacell.net/index.php/api/send_sms/send";
        $senderId = 'OlaHub';
        $phoneNumberFormated = $this->final_to;

        $client = new \GuzzleHttp\Client(['verify' => false]);
        $hed = base64_encode($username . ':' . $password);
        try {
            $res = $client->post($sendSmsApiUrl, array(
                'headers' => array(
                    'Authorization' => 'Basic ' . $hed,
                ),
                'form_params' => array(
                    'mobile_number' => $phoneNumberFormated,
                    'msg' => $this->body,
                    'from' => $senderId,
                    'tag' => 1
                )
                    )
            );
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $to = [];
            $to[] = [
                'email' => "amjad.sawais@olahub.com"
            ];
            $to[] = [
                'email' => "mohamed.elabsy@olahub.com"
            ];
            $sendEmail = new \OlaHub\UserPortal\Libraries\SendEmails;
            $sendEmail->subject = "SMS Error";
            $sendEmail->body = "<b>The error is: </b>".$e->getMessage() 
                    . "<br /><b>When try send to: </b>" . $this->final_to
                    . "<br /><b>The message content is: </b>" . $this->body;
            $sendEmail->to = $to;
            $sendEmail->send();
            return false;
        }
    }

    private function getTemplateData() {
        if ($this->template_code) {
            $this->template_data = MessageTemplate::where('code', $this->template_code)
                    ->whereIn('message_type', $this->typesMapping[$this->type])
                    ->first();
        }
        $this->checkVarValue('template_data');
    }

    private function getSubjectData() {
        $this->subject = OlaHubCommonHelper::returnCurrentLangField($this->template_data, "subject");
        $this->checkVarValue('subject');
    }

    private function getBodyData() {
        $this->body = OlaHubCommonHelper::returnCurrentLangField($this->template_data, "body");
        $this->checkVarValue('body');
        if ($this->replace && $this->replace_with) {
            $this->body = str_replace($this->replace, $this->replace_with, $this->body);
        }
    }

    private function getTo() {
        if (is_array($this->to)) {
            foreach ($this->to as $one) {
                if (is_array($one)) {
                    if (count($one) == 2) {
                        $this->final_to[] = [
                            'email' => $one[0],
                            'name' => $one[1],
                        ];
                    } else {
                        $this->final_to[] = [
                            'email' => $one[0]
                        ];
                    }
                } else {
                    $this->final_to = $this->to;
                }
            }
        } else {
            $this->final_to = $this->to;
        }
        $this->checkVarValue('final_to');
    }

    private function getCC() {
        if (is_array($this->cc) && count($this->cc)) {
            $this->final_cc = $this->cc;
        } elseif (strlen($this->cc) > 0) {
            $this->final_cc[] = $this->cc;
        }
    }

    private function checkVarValue($var) {
        if (!$this->$var || $this->$var == NULL) {
            throw new BadRequestHttpException(404);
        }
    }

}
