<?php

namespace OlaHub\UserPortal\Helpers;

class BillsHelper extends OlaHubCommonHelper {

    function createUserBillNumber() {
        
        $lastBill = \OlaHub\UserPortal\Models\UserBill::where('country_id', app('session')->get('def_country')->id)->latest()->first();
        $billNumber = 0;
        if ($lastBill) {
            $billNumberTemp = explode('-', $lastBill->billing_number);
            $billNumber = (int) end($billNumberTemp);
        }
        $billNumber += 1;
        return BILL_PREF . strtoupper(app('session')->get('def_country')->two_letter_iso_code) . "-" . parent::createNumberPrefix(app('session')->get('tempID')) . "-" . parent::createNumberPrefix($billNumber, 2);
    }
}
