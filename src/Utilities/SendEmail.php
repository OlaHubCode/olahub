<?php

namespace OlaHub\UserPortal\Libraries;

use App\Mail\EmailFunction;
use Illuminate\Support\Facades\Mail;

class SendEmails {

    public
            $subject,
            $body,
            $to,
            $ccMail,
            $bccMail;
    function send() {        
        Mail::to($this->to)->bcc($this->bccMail)->send(new EmailFunction($this->body, $this->subject,  $this->ccMail));
        return true;
    }

}
