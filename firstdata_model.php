<?php

// FD4016 =
// First Data Closed Loop Gift Card Merchant Interface Specifications:
// SVdot Internet Transaction Manual
// Version 4.0 Release 16, August 12 2010

// Provided by Villa, Arnel N Arnel.Villa@firstdata.com via email on 6 Jul 2011.
//
// MID: 99032809997
// TID: 00000000000
//
// Please note that we normally mapped the Datawire TID with the Alt-MID.
//
// Datawire ID: Your test Datawire DID will be obtained when you successfully complete the "Self-Registration" function as outlined in the SecureTransport API provided to you.
//
// Following the instructions provided in the SecureTransport API and as part of your integration testing you will need to configure your application with the following:
//
// Staging URLs
// Primary URL: https://staging1.datawire.net/sd
// Secondary URL: https://staging2.datawire.net/sd
// * For testing purposes only; please use these URLs and assigned parameters noted.
//
// Production URLs
// Primary URL: https://vxn.datawire.net/sd
// Secondary URL: https://vxn1.datawire.net/sd
// * For Production purposes only; please ensure these are defaulted and configurable for production.
//
// Service ID: 104 (A Datawire assigned value to be hard coded in the Application)
//
// App ID: TRWGIFTCARDUNIX2 (The AppID is a Datawire assigned Vendor specific value to be hard coded in the Application.)
//
// Client Ref: Client Ref should be 14 digits set up as follows "tttttttVnnnnnn":
//    "ttttttt" = 7 digit transaction ID that is unique to each transaction for at least a day. Pad with trailing zeros or truncate as required.
//    "V" = the letter "V" for Version. (the version should be dynamic and change along with your app version)
//    "nnnnnn" = the version number of your application, 6 digits long, no periods or spaces, pad with trailing zeros or truncate as required to meet 6

// Test DID=00010743194075661445, obtained 15 Jul 2011.
// Production DID=00017313520045460248, obtained 5 Oct 2011.


class Firstdata_model extends CI_Model
{
        var $vxnapid_host = "127.0.0.1";
        var $vxnapid_port = "29999";

        // currency_code parameter is ISO 4217, translate symbolic to numeric if necessary
        var $currency_code_map = array(
                'CAD' => "124",
                'GBP' => "826",
                'EUR' => "978",
                'CHF' => "756",
                'USD' => "840"
        );

        var $default_parameters = array(
                // VXN (Secure Transport) parameters
                'SVCID' => null,
                'MID' => null,
                'TID' => null,
                'DID' => null,
                // SVdot/CLGC (Closed Loop Gift Card) parameters
                'merchant_number' => null, // identifies Transaction Wireless
                'alternate_merchant_number' => "", // store number
                'source_code' => "30", // Internet With EAN
                //'source_code' => "31", // Internet Without EAN
                'promotion_code' => "0", // Identifies card issuer, including certain card attributes
                // the most important being "demoninated" (fixed value on activation) versus
                // "non-demoninated" (variable value on activation)
                'terminal_id' => "0", // FLD #42
                'currency_code' => "840", // FLD #C0, FIXME: should come from the card or cardtype
                'history_code' => "2410", // transaction type for getHistory()
                'mwk' => null, // for encrypting/decrypting EANs
                'user_1' => "", // FLD #09
                'user_2' => "", // FLD #10
                'client_id' => "", // FLD #CA (see PR15003165 OLTP Aggregator Implementation for VLTW.doc)
                'first_transaction_number' => "-10", // FLD #CE
                'transaction_count' => "10", // FLD #CF
                'history_format' => "84", // FLD #E8
                'mwkid' => "1", // FLD #F3
                'connection_timeout' => 10.0, // in seconds (double)
                'read_timeout' => 31, // in seconds (int)
                'logtofile' => "",
                'debug' => 0,
                'donotpack' => "", // a comma-separated list of fields (eg: "9,15") that won't be packed
                'simulate_timeout' => "0", // for testing reversals
                'force_vxn_response_code' => "", // for testing reversals
                'cardextra' => true,
                'activation_code' => "",
                'stripbin' => "", // a comma-separated list of BINs (eg: "912505,912501") that will be stripped from the card number
                'delayed_activation' => "v1",
        );
        var $parameters = array();

        var $svdot_version_number = "4";
        var $svdot_format_number = "0";
        var $field_separator;
        var $field_table;

        var $result; // returned to model caller on error

        var $transaction_request_code;
        var $request_header;
        var $request_field;
        var $response_field;

        var $time;

        // FIXME: for Decline Code Testing
        var $force_pin = "";

        var $vdata;

        function __construct()
        {
                parent::__construct();

                $this->benchmark->mark('instantiation_start');

                // REVIEW #ENG-6891
                $this->load->helper('numbers');
                $this->load->model('svp_model'); // for SVP error codes

                $this->result['svperrnum'] = "";
                $this->result['errmsg'] = "";
                $this->result['errnum'] = SVP_ERRNUM_NONE;

                $this->transaction_request_code = "";
                $this->request_header = "";

                /*
                $cluster = @file_get_contents("/var/tw/cluster");
                $cluster = trim($cluster);
                switch ($cluster) {
                case "production":
                        break;
                default:
                        // test:
                        break;
                }
*/

                $this->field_separator = chr(0x1C);

                $t = &$this->field_table;
                $t[0x01] = array("Card Inquiry", "AN", 1, 234);
                $t[0x04] = array("Transaction Amount", "N", 1, 12);
                $t[0x05] = array("Adjustment Amount", "SN", 2, 13);
                $t[0x06] = array("Card Cost", "N", 1, 12);
                $t[0x07] = array("Escheatable Transaction", "AN", 1, 1);
                $t[0x08] = array("Reference Number", "AN", 1, 16);
                $t[0x09] = array("User 1", "AN", 1, 20);
                $t[0x10] = array("User 2", "AN", 1, 20);
                $t[0x11] = array("System Trace Number", "N", 6, 6);
                $t[0x12] = array("Local Transaction Time", "N", 6, 6);
                $t[0x13] = array("Local Transaction Date", "N", 8, 8);
                $t[0x15] = array("Terminal Transaction Number", "AN", 1, 17);
                $t[0x16] = array("Card Available for Use Date", "N", 8, 8);
                $t[0x18] = array("SIC Code", "N", 4, 4);
                $t[0x31] = array("Target Security Card Value", "N", 3, 8);
                $t[0x32] = array("Security Card Value (SCV)", "N", 3, 8);
                $t[0x33] = array("Target Extended Account Number", "HT", 32, 32);
                $t[0x34] = array("Extended Account Number (EAN)", "HT", 32, 32);
                $t[0x35] = array("Track II Data", "AN", 37, 37);
                $t[0x36] = array("Foreign Account", "AN", 9, 40);
                $t[0x38] = array("Authorization Code", "N", 6, 6);
                //$t[0x38] = array("Authorization Code", "AN", 8, 8);
                $t[0x39] = array("Response Code", "N", 2, 4);
                $t[0x40] = array("Source Security Code", "HT", 8, 32);
                $t[0x41] = array("Target Security Code", "HT", 8, 32);
                $t[0x42] = array("Merchant & Terminal ID", "N", 15, 15);
                $t[0x44] = array("Alternate Merchant Number", "N", 1, 11);
                $t[0x53] = array("Post Date", "N", 8, 8);
                $t[0x54] = array("Cashback", "N", 1, 12);
                $t[0x62] = array("Clerk ID", "AN", 1, 8);
                $t[0x63] = array("Working Key", "HT", 80, 80);
                //$t[0x70] = array("Embossed Card Number", "N", 13, 19);
                $t[0x70] = array("Embossed Card Number", "ZN", 10, 19);
                $t[0x75] = array("Previous Balance", "N", 1, 12);
                $t[0x76] = array("New Balance", "N", 1, 12);
                $t[0x78] = array("Lock Amount", "N", 1, 12);
                $t[0x79] = array("Local Lock ID", "N", 1, 4);
                $t[0x7F] = array("Echo Back", "AN", 1, 26);
                $t[0x80] = array("Base Previous Balance", "N", 1, 12);
                $t[0x81] = array("Base New Balance", "N", 1, 12);
                $t[0x82] = array("Base Lock Amount", "N", 1, 12);
                $t[0x83] = array("Base Cashback", "N", 1, 12);
                $t[0xAA] = array("Account Origin", "AN", 1, 1);
                $t[0xA0] = array("Expiration Date", "N", 8, 8);
                $t[0xA2] = array("EAN Expiration Date", "N", 13, 13);
                $t[0xAC] = array("Foreign Access Code", "N", 1, 8);
                $t[0xB0] = array("Card Class", "N", 1, 4);
                $t[0xBC] = array("Base Currency", "N", 3, 3);
                $t[0xC0] = array("Local Currency", "N", 3, 3);
                $t[0xCA] = array("Client Identifier", "N", 1, 8);
                $t[0xCE] = array("First Transaction Number", "SN", 1, 5);
                $t[0xCF] = array("Transaction Count", "N", 1, 4);
                $t[0xD0] = array("Transaction History Detail", "FDCD", 1, 512);
                $t[0xE1] = array("Call Trace Information", "AN", 1, 19);
                $t[0xE2] = array("Account Status", "N", 1, 2);
                $t[0xE7] = array("Discounted Amount", "N", 1, 12);
                $t[0xE8] = array("History Format", "N", 1, 2);
                $t[0xE9] = array("Exchange Rate", "N", 3, 18);
                $t[0xEA] = array("Source Code", "N", 2, 3);
                $t[0xEB] = array("Count", "N", 1, 4);
                $t[0xEC] = array("Transaction Record Length", "N", 1, 3);
                $t[0xED] = array("Target Embossed Card Number", "N", 16, 16);
                $t[0xF2] = array("Promotion Code", "N", 1, 8);
                $t[0xF3] = array("Merchant Key ID", "N", 1, 4);
                $t[0xF4] = array("Fraud/Watch/Restricted Status", "N", 1, 2);
                $t[0xF6] = array("Original Transaction Request", "N", 4, 4);
                $t[0xF7] = array("Client Identifier (F7)", "N", 1, 8);
                $t[0xFA] = array("Remove Balance", "AN", 1, 1);

                $this->benchmark->mark('instantiation_end');
        }

        function _log($msg)
        {
                if (isset($this->parameters['logtofile']) && strlen($this->parameters['logtofile'])) {
                        $date = date("M j H:i:s");
                        $pid = getmypid();
                        $msg = $date . " [" . $pid . "]: " . $msg . "\n";
                        file_put_contents($this->parameters['logtofile'], $msg, FILE_APPEND);
                }
        }

        function _dbg($msg)
        {
                if ($this->parameters['debug'])
                        $this->_log($msg);
        }

        function _dump($var, $val)
        {
                if ($this->parameters['debug']) {
                        $msg = $var . "=" . print_r($val, 1);
                        $this->_log($msg);
                }
        }

        function _logInfo($msg)
        {
                $this->_log("INFO: " . $msg);
                logInfo($msg);
        }

        function _logError($msg)
        {
                $this->_log("ERROR: " . $msg);
                logError($msg);
        }

        function _pack_error($msg)
        {
                $this->result['errmsg'] = $msg;
                $this->result['errnum'] = SVP_ERRNUM_INTERNAL;
                return false;
        }

        function _pack(&$data, $name, $type, $minlen, $maxlen, $val)
        {
                $x = "";
                if ($type == "AN") {
                        $x = str_pad($val, $minlen, " ", STR_PAD_RIGHT); // left justified
                } else if ($type == "HT") {
                        $x = $val;
                } else if (($type == "N") || ($type == "SN")) {
                        //$x = sprintf("%d", $val);
                        //$x = string($val);
                        $x = $val;
                        $x = str_pad($x, $minlen, " ", STR_PAD_LEFT); // right justified
                } else if ($type == "ZN") { // "N" but zero-filled
                        $x = sprintf("%d", $val);
                        $x = str_pad($x, $minlen, "0", STR_PAD_LEFT); // right justified
                } else {
                        return $this->_pack_error("name=" . $name . " type=" . $type . " minlen=" . $minlen . " maxlen=" . $maxlen . " val='" . $val . "' unknown type");
                }
                if ($this->parameters['debug'])
                        $this->_log("type=" . $type . " minlen=" . $minlen . " maxlen=" . $maxlen . " val=" . $val . "= x=" . $x . "=");
                $len = strlen($x);
                if (($len < $minlen) || ($len > $maxlen))
                        return $this->_pack_error("name=" . $name . " type=" . $type . " minlen=" . $minlen . " maxlen=" . $maxlen . " val='" . $val . "' bad length");
                $data .= $x;
                return true;
        }

        function _pack_field($fld, $val)
        {
                if (strlen($this->parameters['donotpack'])) {
                        // some testing requires certain fields to not be sent
                        $donotpack = explode(',', $this->parameters['donotpack']);
                        $find = sprintf("%02X", $fld);
                        if (in_array($find, $donotpack)) {
                                $this->_dbg("field " . $fld . " not packed.\n");
                                return true;
                        }
                }
                if (!isset($this->field_table[$fld]))
                        return $this->_pack_error("unknown field=" . $fld);
                $t = $this->field_table[$fld];
                $name = $t[0];
                if ($this->parameters['debug'])
                        $this->_log("pack fld=" . sprintf("%02X", $fld) . " name=" . $name);
                $type = $t[1];
                $minlen = $t[2];
                $maxlen = $t[3];
                $data = $this->field_separator;
                $data .= sprintf("%02X", $fld);
                if (!$this->_pack($data, $name, $type, $minlen, $maxlen, $val))
                        return false;
                $this->request_field[$fld] = $data;

                return true;
        }

        function _req_header($transaction_request_code)
        {
                $this->request_field = array();
                $this->response_field = array();
                if ($transaction_request_code != 704)
                        $this->transaction_request_code = $transaction_request_code;
                $data = "";
                $this->_dbg("pack header");
                if (!$this->_pack($data, "Message Identifier", "AN", 3, 3, "SV."))
                        return false;
                if (!$this->_pack($data, "Merchant ID", "N", 11, 11, $this->parameters['merchant_number']))
                        return false;
                $data .= $this->field_separator;
                if (!$this->_pack($data, "Version Number", "N", 1, 1, $this->svdot_version_number))
                        return false;
                if (!$this->_pack($data, "Format Number", "N", 1, 1, $this->svdot_format_number))
                        return false;
                if (!$this->_pack($data, "Transaction Request Code", "ZN", 4, 4, $transaction_request_code))
                        return false;
                $this->request_header = $data;
                return true;
        }

        function _pack_field_04($amt)
        {
                return $this->_pack_field(0x04, round($amt * 100));
        }

        function _pack_field_05($amt)
        {
                return $this->_pack_field(0x05, sprintf("%+d", round($amt * 100)));
        }

        function _pack_field_08($refnum)
        {
                return $this->_pack_field(0x08, $refnum);
        }

        function _pack_field_09()
        {
                if (empty($this->parameters['user_1']))
                        return true;
                return $this->_pack_field(0x09, $this->parameters['user_1']);
        }

        function _pack_field_10()
        {
                if (empty($this->parameters['user_2']))
                        return true;
                return $this->_pack_field(0x10, $this->parameters['user_2']);
        }

        function _pack_order_details()
        {
                if (!empty($this->parameters['send_order_details'])) {
                                $order_details = $this->svp_model->getOrderDetails();
                                if (empty($order_details)) {
                                                logInfo("FirstData:  Param send_order_details is set but no order_details found...returning.");
                                                return true;
                                        }
                                if (strlen($order_details) > 20) {
                                                logInfo("FirstData:  Param send_order_details is set but order_details is too long...returning.");
                                                return true;
                                        }
                                //Example: user_2 = 123456789;
                                $this->parameters[$this->parameters['send_order_details']] = $order_details;
                                logInfo("FirstData:  Sending transacton_id ($order_details) in field {$this->parameters['send_order_details']}.");
                        }
                return true;
        }

        function _pack_user_details() //Added: RB 44566887
        {
                if (!empty($this->parameters['pack_users'])) {
                        logInfo("FirstData: pack user details enabled.");
                        $user_1 = substr($this->svp_model->getUser1(), 0, 20);
            $user_2 = substr($this->svp_model->getUser2(), 0, 20);
                        if (strlen($user_1) > 20 || strlen($user_2) > 20) {
                                logInfo("FirstData: param pack_users, user fields larger than 20 characters...returning.");
                                return false;
                        }
                        $this->parameters['user_1'] = $user_1;
                        $this->parameters['user_2'] = $user_2;
                }
                return true;
        }

        private function _pack_mid_details() // ENG-2087
        {
                if (!empty($this->parameters['enable_dynamic_mid'])) {
                        logInfo("FirstData: dynamic mid/alt mid enabled.");
                        $mid = $this->svp_model->getMid();
                        $alt_mid = $this->svp_model->getAltmid();
                        //logInfo("FirstData:  Dynamic MID/Alt Mid ($mid) ($alt_mid)");
                        if ($mid !== null) {
                                $this->parameters['merchant_number'] = $mid;
                        }
                        if ($alt_mid !== null) {
                                $this->parameters['alternate_merchant_number'] = $alt_mid;
                        }

                        $this->svp_model->setMid(null); //ENG-3468
                        $this->svp_model->setAltmid(null); //ENG-3468
                }
                return true;
        }

        //This is initally for SuperValue but can be used for whomever | 1-19-2018
        // Parameter Setup: &send_transaction_id=user_2
        function _pack_transaction_id()
        {
                if (!empty($this->parameters['send_transaction_id'])) {
                                $transaction_id = $this->svp_model->getTransactionId();
                                if (empty($transaction_id)) {
                                                logInfo("FirstData:  Param send_transaction_id is set but no transaction id found...returning.");
                                                return true;
                                        }
                                if (strlen($transaction_id) > 20) {
                                                logInfo("FirstData:  Param send_transaction_id is set but transaction id is too long...returning.");
                                                return true;
                                        }
                                //Example: user_2 = 123456789;
                                $this->parameters[$this->parameters['send_transaction_id']] = $transaction_id;
                                logInfo("FirstData:  Sending transacton_id ($transaction_id) in field {$this->parameters['send_transaction_id']}.");
                        }
                return true;
        }

        function _pack_field_12()
        {
                return $this->_pack_field(0x12, date('His', $this->time));
        }

        function _pack_field_13()
        {
                return $this->_pack_field(0x13, date('mdY', $this->time));
        }

        function _pack_field_15()
        {
                if (isset($this->vdata['msg_id'])) {
                        $id = $this->vdata['msg_id'];
                } else {
                        // must be unique for 90 days
                        $DAY = sprintf("%02d", date('z') % 90); // 2 digits (day of year modulo 90)
                        // REVIEW #ENG-6891
                        $IX = sprintf("%08d", random_integer(0, 99999999)); //uses Mersenne Twister, should be good
                        $id = $DAY . $IX;
                }
                return $this->_pack_field(0x15, $id);
        }

        function _pack_field_32($card)
        {
                if (isset($card['reference_number']) && strlen($card['reference_number']))
                        $pin = $card['reference_number'];
                else if (isset($card['pin']) && strlen($card['pin']))
                        $pin = $card['pin'];
                if (strlen($this->force_pin))
                        $pin = $this->force_pin;
                if (!isset($pin))
                        return true; // no pin to specify
                return $this->_pack_field(0x32, $pin);
        }

        // more generic version of _pack_field_32(), could replace it someday...
        // added by Paul Gardner - 18 Nov 2020
        function _pack_field_scv($field, $card)
        {
                if (isset($card['reference_number']) && strlen($card['reference_number']))
                        $pin = $card['reference_number'];
                else if (isset($card['pin']) && strlen($card['pin']))
                        $pin = $card['pin'];
                if (strlen($this->force_pin))
                        $pin = $this->force_pin;
                if (!isset($pin))
                        return true; // no pin to specify
                return $this->_pack_field($field, $pin);
        }

        function _pack_field_34($card)
        {
                if (isset($card['reference_number']) && strlen($card['reference_number']))
                        $pin = $card['reference_number'];
                else if (isset($card['pin']) && strlen($card['pin']))
                        $pin = $card['pin'];
                if (strlen($this->force_pin))
                        $pin = $this->force_pin;
                if (!isset($pin))
                        return true; // no pin to specify
                $cmd = "firstdata_crypt encrypt_ean " . $this->parameters['mwk'] . " " . $pin;
                $this->_dbg("exec " . $cmd);
                exec($cmd, $outraw, $status);
                $output = join("\n", $outraw);
                $this->_dbg("status=" . $status . " output=" . $output);
                if ($status != 0) {
                        $msg = "EAN encryption failed ($cmd): " . $output;
                        $this->result['errmsg'] = $msg;
                        $this->result['errnum'] = SVP_ERRNUM_INTERNAL;
                        return false;
                }
                $ean = trim($output);
                return $this->_pack_field(0x34, $ean);
        }

        // more generic version of _pack_field_34(), could replace it someday...
        // added by Paul Gardner - 18 Nov 2020
        function _pack_field_ean($field, $card)
        {
                if (isset($card['reference_number']) && strlen($card['reference_number']))
                        $pin = $card['reference_number'];
                else if (isset($card['pin']) && strlen($card['pin']))
                        $pin = $card['pin'];
                if (strlen($this->force_pin))
                        $pin = $this->force_pin;
                if (!isset($pin))
                        return true; // no pin to specify
                $cmd = "firstdata_crypt encrypt_ean " . $this->parameters['mwk'] . " " . $pin;
                $this->_dbg("exec " . $cmd);
                exec($cmd, $outraw, $status);
                $output = join("\n", $outraw);
                $this->_dbg("status=" . $status . " output=" . $output);
                if ($status != 0) {
                        $msg = "EAN encryption failed ($cmd): " . $output;
                        $this->result['errmsg'] = $msg;
                        $this->result['errnum'] = SVP_ERRNUM_INTERNAL;
                        return false;
                }
                $ean = trim($output);
                return $this->_pack_field($field, $ean);
        }

        function _pack_field_42()
        {
                $xdata = "";
                if (!$this->_pack($xdata, "-", "N", 11, 11, $this->parameters['merchant_number']))
                        return false;
                if (!$this->_pack($xdata, "-", "ZN", 4, 4, $this->parameters['terminal_id']))
                        return false;
                return $this->_pack_field(0x42, $xdata);
        }

        function _pack_field_44()
        {
                if (!empty($this->parameters['alternate_merchant_number']))
                        return $this->_pack_field(0x44, $this->parameters['alternate_merchant_number']);
                return true;
        }

        function _pack_field_53()
        {
                return $this->_pack_field(0x53, date('mdY', $this->time));
        }

        function _pack_field_63()
        {
                return $this->_pack_field(0x63, $this->parameters['mwk']);
        }

        function _pack_field_70($card)
        {
                return $this->_pack_field(0x70, $card['account_number']);
        }

        function _pack_field_79($lockid)
        {
                return $this->_pack_field(0x79, $lockid);
        }

        function _pack_field_C0()
        {
                return $this->_pack_field(0xC0, $this->parameters['currency_code']);
        }

        function _pack_field_CA()
        {
                if (empty($this->parameters['client_id']))
                        return true;
                return $this->_pack_field(0xCA, $this->parameters['client_id']);
        }

        function _pack_field_CE($offset = '')
        { // for getHistory()
                if (strlen($offset) > 0) //JC -- allow us to get more than 10 results
                        return $this->_pack_field(0xCE, "-$offset");
                else
                        return $this->_pack_field(0xCE, $this->parameters['first_transaction_number']);
        }

        function _pack_field_CF()
        { // for getHistory()
                if (strlen($this->parameters['transaction_count']))
                        return $this->_pack_field(0xCF, $this->parameters['transaction_count']);

                return true;
        }

        function _pack_field_E8()
        { // for getHistory()
                return $this->_pack_field(0xE8, $this->parameters['history_format']);
        }

        function _pack_field_EA()
        {
                return $this->_pack_field(0xEA, $this->parameters['source_code']);
        }

        function _pack_field_ED($card)
        {
                return $this->_pack_field(0xED, $card['account_number']);
        }


        function _pack_field_F2()
        {
                return $this->_pack_field(0xF2, $this->parameters['promotion_code']);
        }

        function _pack_field_F3()
        {
                if ($this->parameters['source_code'] == "30") // with EAN
                        return $this->_pack_field(0xF3, $this->parameters['mwkid']);
                return true;
        }

        /*
        Used to set the account status to Watch or Fraud or restricted.
                0 – Reverse: Remove the Watch/Fraud/Restricted status (reverts to prior account status).
                1 – Watch status: Suspicious card
                2 – Fraud status: Confirmed fraud
                3 – Restricted status: It restricts a default and configurable internet transaction list maintained at consortium level.
         */
        function _pack_field_F4()
        {
                if (!empty($this->parameters['watch_status'])) {
                        $status = (int)$this->parameters['watch_status'];
                } else {
                        $status = 2; // default to fraud
                }

                return $this->_pack_field(0xF4, $status);
        }

        function _pack_field_F6($code)
        {
                return $this->_pack_field(0xF6, $code);
        }

        /*
        Used to remove balance when closing an account.
                ‘Y’ - remove balance if non-zero
                ‘N’ - do not remove balance.
         */
        function _pack_field_FA()
        {
                if (!empty($this->parameters['remove_balance'])) {
                        $remove = (string)$this->parameters['remove_balance'];
                } else {
                        $remove = 'Y'; // default to yes
                }

                return $this->_pack_field(0xFA, $remove);
        }

        function _pack_card($card)
        {
                if (strlen($this->parameters['stripbin']) && strlen($card['account_number']) >= 16) {
                        // Winco needs certain BINs to be stripped from the card number
                        $stripbin = explode(',', $this->parameters['stripbin']);
                        foreach ($stripbin as $bin) {
                                $bin = trim($bin);
                                if (strpos($card['account_number'], $bin) === 0) {
                                        $card['account_number'] = substr($card['account_number'], strlen($bin));
                                        break;
                                }
                        }
                }

                if (!$this->_pack_field_70($card))
                        return $this->result;
                if ($this->parameters['source_code'] == "30") {
                        if (!$this->_pack_field_34($card))
                                return $this->result;
                } else if ($this->parameters['source_code'] == "31") {
                        if (!$this->_pack_field_32($card))
                                return $this->result;
                } else {
                        $this->result['errmsg'] = "Invalid source_code value";
                        $this->result['errnum'] = SVP_ERRNUM_INTERNAL;
                        return false;
                }
                return true;
        }

        // more generic version of _pack_card(), could replace it someday...
        // added by Paul Gardner - 18 Nov 2020
        function _pack_card_plus($card, $account_number_field = 0x70, $ean_field = 0x34, $scv_field = 0x32)
        {
                if (strlen($this->parameters['stripbin']) && strlen($card['account_number']) >= 16) {
                        // Winco needs certain BINs to be stripped from the card number
                        $stripbin = explode(',', $this->parameters['stripbin']);
                        foreach ($stripbin as $bin) {
                                $bin = trim($bin);
                                if (strpos($card['account_number'], $bin) === 0) {
                                        $card['account_number'] = substr($card['account_number'], strlen($bin));
                                        break;
                                }
                        }
                }

                if (!$this->_pack_field($account_number_field, $card['account_number']))
                        return $this->result;
                if ($this->parameters['source_code'] == "30") {
                        if (!$this->_pack_field_ean($ean_field, $card))
                                return $this->result;
                } else if ($this->parameters['source_code'] == "31") {
                        if (!$this->_pack_field_scv($scv_field, $card))
                                return $this->result;
                } else {
                        $this->result['errmsg'] = "Invalid source_code value";
                        $this->result['errnum'] = SVP_ERRNUM_INTERNAL;
                        return false;
                }
                return true;
        }

        function _vxn_errmsg($rc)
        {
                switch ($rc) {
                        case "1":
                                return "Authentication Failed — Invalid ID(s)";
                        case "2":
                                return "Invalid Packet ID";
                        case "5":
                                return "Invalid Data Length";
                        case "6":
                                return "Invalid Session Context";
                        case "8":
                                return "Network Error";
                        case "9":
                                return "Send Error";
                        case "11":
                                return "Timeout Error";
                        case "13":
                                return "Authentication Failed";
                        case "14":
                                return "Null Query";
                        case "30":
                                return "No Memory";
                        case "35":
                                return "Invalid Service Name";
                        case "40":
                                return "Service Unavailable";
                        case "41":
                                return "XML Error";
                        case "42":
                                return "HTTP Error";
                        case "43":
                                return "Internet Error";
                        case "44":
                                return "Incorrect URL(s)";
                        case "45":
                                return "No Service";
                        case "46":
                                return "XML Parse Error";
                        case "47":
                                return "Request Overflow";
                        case "48":
                                return "Incorrect Response";
                        case "51":
                                return "Response Overflow";
                        case "52":
                                return "Internet Timeout";
                        case "53":
                                return "Send Error";
                        case "54":
                                return "Receive Error";
                        case "55":
                                return "Retry Registration";
                        case "56":
                                return "Duplicate Registration";
                        case "57":
                                return "Registration Failed";
                        case "58":
                                return "Access Denied";
                        case "59":
                                return "Either MID or TID is not correct";
                        case "60":
                                return "Data not found in provisioning database";
                        case "62":
                                return "Invalid SSL certificate";
                        case "200":
                                return "Host Busy";
                        case "201":
                                return "Host Unavailable";
                        case "202":
                                return "Host Connect Error";
                        case "203":
                                return "Host Drop";
                        case "204":
                                return "Host Comm Error";
                        case "205":
                                return "No Response";
                        case "206":
                                return "Host Send Error";
                        case "405":
                                return "Secure Transport Timeout";
                        case "505":
                                return "Network Error";
                }
                return "Unrecognized";
        }

        function _svdot_errmsg($rc)
        {
                switch ($rc) {
                        case "00":
                                return "Completed OK.";
                        case "01":
                                return "Insufficient funds.";
                        case "02":
                                return "Account closed. The account was closed, probably because the account balance was $0.00.";
                        case "03":
                                return "Unknown account. The account could not be located in the account table.";
                        case "04":
                                return "Inactive account. The account has not been activated by an approved location.";
                        case "05":
                                return "Expired card. The card’s expiration date has been exceeded.";
                        case "06":
                                return "Invalid transaction code. This card or terminal is not permitted to perform this transaction, or the transaction code is invalid.";
                        case "07":
                                return "Invalid merchant. The merchant is not in the merchant database or the merchant is not permitted to use this particular card.";
                        case "08":
                                return "Already active. The card is already active and does not need to be reactivated";
                        case "09":
                                return "System error. There is a problem with the host processing system. Call your help desk or operations support.";
                        case "10":
                                return "Lost or stolen card. The transaction could not be completed because the account was previously reported as lost or stolen.";
                        case "11":
                                return "Not lost or stolen. The replacement transaction could not be completed because the account was not previously marked as lost/stolen.";
                        case "12":
                                return "Invalid transaction format. There is a transaction format problem.";
                        case "15":
                                return "Bad mag stripe. The mag stripe could not be parsed for account information.";
                        case "16":
                                return "Incorrect location. There was a problem with the merchant location.";
                        case "17":
                                return "Max balance exceeded. The transaction, if completed, would cause the account balance to be exceeded by the max_balance as specified in the promotion. Some merchants set the max_balance to a value twice the max transaction amount.";
                        case "18":
                                return "Invalid amount. There was a problem with the amount field in the transaction format – more or less than min/max amounts specified in the promotion for that transaction.";
                        case "19":
                                return "Invalid clerk. The clerk field was either missing, when required, or the content did not match the requirements.";
                        case "20":
                                return "Invalid password. The user password was invalid.";
                        case "21":
                                return "Invalid new password. The new password does not meet the minimum security criteria.";
                        case "22":
                                return "Exceeded account reloads. The clerk/user/location was only permitted to reload some number of accounts. That number was exceeded. (See your Business Managerin order to extend this limit.)";
                        case "23":
                                return "Password retry exceeded. The user account has been frozen because the user attempted access and was denied. Seek management assistance.";
                        case "26":
                                return "Incorrect transaction version or format number for POS transactions.";
                        case "27":
                                return "Request not permitted by this account.";
                        case "28":
                                return "Request not permitted by this merchant location.";
                        case "29":
                                return "Bad_replay_date.";
                        case "30":
                                return "Bad checksum. The checksum provided is incorrect.";
                        case "31":
                                return "Balance not available (denial). Due to an internal First Data Closed Loop Gift Card (CLGC) issue, information from this account could not be retrieved. ";
                        case "32":
                                return "Account locked. ";
                        case "33":
                                return "No previous transaction. The void or reversal transaction could not be matched to a previous (original) transaction. In the case of a pre-auth redemption, the corresponding locking transaction could not be identified. ";
                        case "34":
                                return "Already reversed. ";
                        case "35":
                                return "Generic denial. An error was produced which has no other corresponding response code for the provided version/format. ";
                        case "36":
                                return "Bad authorization code. The authorization code test failed. ";
                        case "37":
                                return "Too many transactions requested. See SVdot Transaction Manual Appendix B for Transaction History limits by detail record format. ";
                        case "38":
                                return "No transactions available/no more transactions available. There are no transactions for this account or there are no transactions as determined by the specified first transaction number. ";
                        case "39":
                                return "Transaction history not available. The history could not be provided. ";
                        case "40":
                                return "New password required. ";
                        case "41":
                                return "Invalid status change. The status change requested (e.g. lost/stolen, freeze active card) cannot be performed. ";
                        case "42":
                                return "Void of activation after account activity. ";
                        case "43":
                                return "No phone service. Attempted a calling card transaction on an account which is not configured for calling card activity. ";
                        case "44":
                                return "Internet access disabled. This account may no longer use transactions in which an EAN is required. ";
                        case "45":
                                return "Invalid EAN. The EAN is not correct for the provided account number. ";
                        case "46":
                                return "Invalid merchant key. The merchant key block provided is invalid. (e.g.  The working key provided in an Assign Merchant Working Key transaction). ";
                        case "47":
                                return "Promotions for Internet Virtual and Physical cards do not match. When enabling a physical card to a virtual card, both must be from the same promotion. Cards for bulk activation request must be from the same promotion. ";
                        case "48":
                                return "Invalid transaction source. The provided source (field EA) is not valid for this transaction. ";
                        case "49":
                                return "Account already linked. (e.g. Response when enabling a physical card, when the two provided accounts have already been linked together.) ";
                        case "50":
                                return "Account not in inactive state. (e.g. Response when enabling a physical card, when the physical card in not in an inactive state.) ";
                        case "51":
                                return "First Data Voice Services returns this response on Internet transactions where the interface input parameter is not valid. ";
                        case "52":
                                return "First Data Voice Services returns this response on Internet transactions where they did not receive a response from CLGC. ";
                        case "53":
                                return "First Data Voice Services returns this response on Internet transactions where the client certificate is invalid. ";
                        case "54":
                                return "Merchant not configured as International although the account requires it. (e.g. The account allows currency conversion but the merchant is not configured for International.) ";
                        case "55":
                                return "Invalid currency. The provided currency is invalid. ";
                        case "56":
                                return "Request not International. Merchant configured to require currency information for each financial transaction, however none was sent.";
                        case "57":
                                return "Currency conversion error. Internal CLGC system error.";
                        case "58":
                                return "Invalid Expiration Date. Expiration date provided is not valid.";
                        case "59":
                                return "The terminal transaction number did not match (on a void or reversal).";
                        case "60":
                                return "First Data Voice Services added a layer of validation that checks the data they receive from CLGC to make sure it is HTML friendly (i.e. no binary data). First Data Voice Services will return this response on Internet transactions if the check fails (the data is not HTML friendly).";
                        case "67":
                                return "Target Embossed Card entered and Transaction Count entered are mismatched.";
                        case "68":
                                return "No Account Link.";
                        case "69":
                                return "Invalid Timezone.";
                        case "70":
                                return "Account On Hold.";
                        case "71":
                                return "Fraud Count Exceeded.";
                        case "72":
                                return "Promo Location Restricted.";
                        case "73":
                                return "Invalid BIN.";
                        case "74":
                                return "Product Code(s) Restricted.";
                        case "75":
                                return "Bad Post Date. The Post Date is not a valid date.";
                        case "76":
                                return "Account Status is Void Lock.";
                        case "77":
                                return "Already active. The card is already active and is reloadable.";
                        case "78":
                                return "Account is Purged. The Account record was purged from the database.";
                        case "79":
                                return "Deny duplicate transaction.";
                        case "80":
                                return "Bulk Activation Error.";
                        case "81":
                                return "Bulk Activation Unattempted Error.";
                        case "82":
                                return "Bulk Activation Package Amount Error.";
                        case "83":
                                return "Store Location Zero Not Allowed.";
                        case "84":
                                return "Account Row Locked.";
                        case "85":
                                return "Accepted but not yet processed.";
                        case "86":
                                return "Incorrect PVC.";
                        case "87":
                                return "Provisioning limit exceeded, currently 10.";
                        case "88":
                                return "De-provisioning limit reached, current count is 0.";
                        case "89":
                                return "The EAN TYPE is not mentioned in manufacture_fa table.";
                        case "90":
                                return "Field SCV is required in the transaction.";
                        case "91":
                                return "Promo Code is not compatible for consortium code.";
                        case "92":
                                return "Product Restricted Declined.";
                        case "94":
                                return "Account notlinked error.";
                        case "95":
                                return "Account is in Watch status.";
                        case "96":
                                return "Account is in Fraud status.";
                }
                return "Unrecognized response code";
        }

        function _set_garble($msg)
        {
                $this->result['errmsg'] = $msg;
                $this->result['errnum'] = SVP_ERRNUM_GARBLE;
                return false;
        }

        function _check_garble($what, $val_actual, $val_good)
        {
                if ($val_actual === $val_good)
                        return true;
                return $this->_set_garble("Response " . $what . " is " . $val_actual . ", should be " . $val_good);
        }

        // Implementation based on "SVdot Appendix B.pdf"
        function _FDCD_decode(&$z, $name, $type, $len, &$val)
        {
                //echo("FDCD_decode: ".print_r($name,1)."\n"); //pgar
                if ($name == "Amount Sign") {
                        // special case, "Amount Sign" is not encoded
                        if (strlen($z) < $len)
                                return $this->_set_garble("FDCD garbled @" . $name . " " . $z);
                        $w = substr($z, 0, 1);
                        $z = substr($z, 1);
                        $val[$name] = $w;
                        return true;
                }
                $fdcd_len = intval(($len + 1) / 2);
                //echo("FDCD_decode len= ".print_r($len,1)." fdcd_len=".print_r($fdcd_len,1)." strlen(z)=".strlen($z)."\n"); //pgar
                if (strlen($z) < $fdcd_len)
                        return $this->_set_garble("FDCD garbled @" . $name . " " . $z);
                $ww = "";
                for ($i = 0; $i < $fdcd_len; $i++) {
                        $w = substr($z, 0, 1);
                        $z = substr($z, 1);
                        $ww .= sprintf("%02d", ord($w) - 32);
                }
                //echo("FDCD_decode: ww=".print_r($ww,1)."\n"); //pgar
                // remove zero-padding on left
                while ((strlen($ww) > 1) && (substr($ww, 0, 1) == "0"))
                        $ww = substr($ww, 1);
                $len = strlen($ww);
                if (!$this->_unpack($ww, $type, $len, $len, $len, $xval))
                        return false;
                $val[$name] = $xval;
                return true;
        }

        function _FDCD_decode_datetime(&$z, &$val)
        {
                if (!$this->_FDCD_decode($z, "Month", "N", 2, $val))
                        return false;
                if (!$this->_FDCD_decode($z, "Day", "N", 2, $val))
                        return false;
                if (!$this->_FDCD_decode($z, "Century", "N", 2, $val))
                        return false;
                if (!$this->_FDCD_decode($z, "Year", "N", 2, $val))
                        return false;
                if (!$this->_FDCD_decode($z, "Hour", "N", 2, $val))
                        return false;
                if (!$this->_FDCD_decode($z, "Minutes", "N", 2, $val))
                        return false;
                return true;
        }

        function _FDCD_unpack($z, &$val)
        {
                $history_format = $this->parameters['history_format'];
                //echo("FDCD_unpack: history_format=".print_r($history_format,1)."\n"); //pgar

                // only 8-bit decoding is implemented
                if (intval($history_format / 10) != 8) {
                        $this->result['errmsg'] = "Cannot handle history format " . $history_format;
                        $this->result['errnum'] = SVP_ERRNUM_INTERNAL;
                        return false;
                }

                // header
                if (!$this->_FDCD_decode($z, "Account status indicator", "AN", 2, $val)) return false;
                if (!$this->_FDCD_decode($z, "Detail version format indicator", "N", 2, $val)) return false;
                if (!$this->_FDCD_decode($z, "Base Currency Code", "N", 3, $val)) return false;

                // records
                $val['Detail Records'][] = array();
                $nrec = 0;
                while (strlen($z)) {
                        $rec = "";
                        switch ($history_format) {
                                case "81":
                                        if (!$this->_FDCD_decode($z, "Primary Merchant Number", "N", 11, $rec)) return false;
                                        if (!$this->_FDCD_decode($z, "Request Code", "N", 4, $rec)) return false;
                                        if (!$this->_FDCD_decode($z, "Amount Sign", "AN", 1, $rec)) return false;
                                        if (!$this->_FDCD_decode($z, "Transaction Amount in Base", "N", 12, $rec)) return false;
                                        if (!$this->_FDCD_decode($z, "Local Lock Amount", "N", 12, $rec)) return false;
                                        if (!$this->_FDCD_decode_datetime($z, $rec)) return false;
                                        break;
                                case "82":
                                        if (!$this->_FDCD_decode($z, "Alternate Merchant Number", "N", 11, $rec)) return false;
                                        if (!$this->_FDCD_decode($z, "Request Code", "N", 4, $rec)) return false;
                                        if (!$this->_FDCD_decode($z, "Amount Sign", "AN", 1, $rec)) return false;
                                        if (!$this->_FDCD_decode($z, "Transaction Amount in Base", "N", 12, $rec)) return false;
                                        if (!$this->_FDCD_decode($z, "Account Balance in Base", "N", 12, $rec)) return false;
                                        if (!$this->_FDCD_decode($z, "Lock Amount", "N", 12, $rec)) return false;
                                        if (!$this->_FDCD_decode_datetime($z, $rec)) return false;
                                        break;
                                case "83":
                                        if (!$this->_FDCD_decode($z, "Alternate Merchant Number", "N", 11, $rec)) return false;
                                        if (!$this->_FDCD_decode($z, "Terminal Number", "N", 4, $rec)) return false;
                                        if (!$this->_FDCD_decode($z, "Request Code", "N", 4, $rec)) return false;
                                        if (!$this->_FDCD_decode($z, "Amount Sign", "AN", 1, $vv)) return false;
                                        if (!$this->_FDCD_decode($z, "Transaction Amount in Base", "N", 12, $rec)) return false;
                                        if (!$this->_FDCD_decode($z, "Account Balance in Base", "N", 12, $rec)) return false;
                                        if (!$this->_FDCD_decode($z, "Local Lock Amount", "N", 12, $rec)) return false;
                                        if (!$this->_FDCD_decode_datetime($z, $rec)) return false;
                                        break;
                                case "84":
                                        if (!$this->_FDCD_decode($z, "Alternate Merchant Number", "N", 8, $rec)) return false;
                                        if (!$this->_FDCD_decode($z, "Terminal Number", "N", 4, $rec)) return false;
                                        if (!$this->_FDCD_decode($z, "Request Code", "N", 4, $rec)) return false;
                                        if (!$this->_FDCD_decode($z, "Amount Sign", "AN", 1, $rec)) return false;
                                        if (!$this->_FDCD_decode($z, "Transaction Amount in Base", "N", 12, $rec)) return false;
                                        if (!$this->_FDCD_decode($z, "Account Balance in Base", "N", 12, $rec)) return false;
                                        if (!$this->_FDCD_decode($z, "Local Lock Amount", "N", 12, $rec)) return false;
                                        if (!$this->_FDCD_decode_datetime($z, $rec)) return false;
                                        break;
                                case "85":
                                        if (!$this->_FDCD_decode($z, "Primary Merchant Number", "N", 11, $rec)) return false;
                                        if (!$this->_FDCD_decode($z, "Request Code", "N", 4, $rec)) return false;
                                        if (!$this->_FDCD_decode($z, "Amount Sign", "AN", 1, $rec)) return false;
                                        if (!$this->_FDCD_decode($z, "Local Currency Code", "N", 3, $rec)) return false;
                                        if (!$this->_FDCD_decode($z, "Local Amount", "N", 12, $rec)) return false;
                                        if (!$this->_FDCD_decode($z, "Transaction Amount in Base", "N", 12, $rec)) return false;
                                        if (!$this->_FDCD_decode($z, "Account Balance in Base", "N", 12, $rec)) return false;
                                        if (!$this->_FDCD_decode($z, "Local Lock Amount", "N", 12, $rec)) return false;
                                        if (!$this->_FDCD_decode_datetime($z, $rec)) return false;
                                        break;
                                case "86":
                                        if (!$this->_FDCD_decode($z, "Alternate Merchant Number", "N", 11, $rec)) return false;
                                        if (!$this->_FDCD_decode($z, "Request Code", "N", 4, $rec)) return false;
                                        if (!$this->_FDCD_decode($z, "Amount Sign", "AN", 1, $rec)) return false;
                                        if (!$this->_FDCD_decode($z, "Local Currency Code", "N", 3, $rec)) return false;
                                        if (!$this->_FDCD_decode($z, "Local Amount", "N", 12, $rec)) return false;
                                        if (!$this->_FDCD_decode($z, "Transaction Amount in Base", "N", 12, $rec)) return false;
                                        if (!$this->_FDCD_decode($z, "Account Balance in Base", "N", 12, $rec)) return false;
                                        if (!$this->_FDCD_decode($z, "Local Lock Amount", "N", 12, $rec)) return false;
                                        if (!$this->_FDCD_decode_datetime($z, $rec)) return false;
                                        break;
                                case "87":
                                        if (!$this->_FDCD_decode($z, "Alternate Merchant Number", "N", 11, $rec)) return false;
                                        if (!$this->_FDCD_decode($z, "Terminal Number", "N", 4, $rec)) return false;
                                        if (!$this->_FDCD_decode($z, "Request Code", "N", 4, $rec)) return false;
                                        if (!$this->_FDCD_decode($z, "Amount Sign", "AN", 1, $rec)) return false;
                                        if (!$this->_FDCD_decode($z, "Local Currency Code", "N", 3, $rec)) return false;
                                        if (!$this->_FDCD_decode($z, "Local Amount", "N", 12, $rec)) return false;
                                        if (!$this->_FDCD_decode($z, "Transaction Amount in Base", "N", 12, $rec)) return false;
                                        if (!$this->_FDCD_decode($z, "Account Balance in Base", "N", 12, $rec)) return false;
                                        if (!$this->_FDCD_decode($z, "Local Lock Amount", "N", 12, $rec)) return false;
                                        if (!$this->_FDCD_decode_datetime($z, $rec)) return false;
                                        break;
                                case "88":
                                        if (!$this->_FDCD_decode($z, "Alternate Merchant Number", "N", 8, $rec)) return false;
                                        if (!$this->_FDCD_decode($z, "Terminal Number", "N", 4, $rec)) return false;
                                        if (!$this->_FDCD_decode($z, "Request Code", "N", 4, $rec)) return false;
                                        if (!$this->_FDCD_decode($z, "Amount Sign", "AN", 1, $rec)) return false;
                                        if (!$this->_FDCD_decode($z, "Local Currency Code", "N", 3, $rec)) return false;
                                        if (!$this->_FDCD_decode($z, "Local Amount", "N", 12, $rec)) return false;
                                        if (!$this->_FDCD_decode($z, "Transaction Amount in Base", "N", 12, $rec)) return false;
                                        if (!$this->_FDCD_decode($z, "Account Balance in Base", "N", 12, $rec)) return false;
                                        if (!$this->_FDCD_decode($z, "Local Lock Amount", "N", 12, $rec)) return false;
                                        if (!$this->_FDCD_decode_datetime($z, $rec)) return false;
                                        break;
                                default:
                                        $this->result['errmsg'] = "Cannot handle history format " . $history_format;
                                        $this->result['errnum'] = SVP_ERRNUM_INTERNAL;
                                        return false;
                        }
                        $val['Detail Records'][$nrec++] = $rec;
                }
                return true;
        }

        function _unpack(&$x, $type, $minlen, $maxlen, $len, &$val)
        {
                if ((strlen($x) < $minlen) || (($maxlen != -1) && (strlen($x) > $maxlen)))
                        return $this->_set_garble("type=" . $type . " minlen=" . $minlen . " maxlen=" . $maxlen . " x='" . $x . "' bad length");
                $z = substr($x, 0, $len);
                if ($type == "AN") {
                        $z = rtrim($z, " ");
                        $val = $z;
                } else if ($type == "HT") {
                        $val = $z;
                } else if (($type == "N") || ($type == "SN")) {
                        $z = ltrim($z, " ");
                        $val = $z;
                } else if ($type == "ZN") { // "N" but zero-filled
                        $z = ltrim($z, "0");
                        if (!strlen($z))
                                $z = "0";
                        $val = $z;
                } else if ($type == "FDCD") {
                        if (!$this->_FDCD_unpack($z, $val))
                                return false;
                } else {
                        return $this->_set_garble("Unknown type " . $type);
                }
                $x = substr($x, $len);
                if ($this->parameters['debug'])
                        $this->_log("unpack type=" . $type . " minlen=" . $minlen . " maxlen=" . $maxlen . " val=" . print_r($val, 1) . "= z=" . $z . "=");
                return true;
        }

        function _parse_response($response)
        {
                $x = $response;
                if (!$this->_unpack($x, "N", 1, -1, 1, $version_number))
                        return false;
                if (!$this->_check_garble("version_number", $version_number, $this->svdot_version_number))
                        return false;
                if (!$this->_unpack($x, "N", 1, -1, 1, $format_number))
                        return false;
                if (!$this->_check_garble("format_number", $format_number, $this->svdot_format_number))
                        return false;
                if (!$this->_unpack($x, "AN", 1, -1, 1, $field_separator))
                        return false;
                if (!$this->_check_garble("field_separator", $field_separator, $this->field_separator))
                        return false;
                // response indicator code
                if (!$this->_unpack($x, "ZN", 4, -1, 4, $message_type_identifier))
                        return false;
                if (!$this->_check_garble("message_type_identifier", $message_type_identifier, "900"))
                        return false;
                if (!$this->_unpack($x, "AN", 1, -1, 1, $field_separator))
                        return false;
                if (!$this->_check_garble("field_separator", $field_separator, $this->field_separator))
                        return false;

                $a = explode($this->field_separator, $x);
                // loop over fields ...
                foreach ($a as $val) {
                        $fldnum = substr($val, 0, 2);
                        $x = substr($val, 2);
                        $this->_dbg("fldnum=" . $fldnum . " x=" . $x . "=");
                        $z = "";
                        $len = strlen($x);

                        sscanf($fldnum, "%02x", $fld);
                        if (isset($this->field_table[$fld])) {
                                $t = $this->field_table[$fld];
                                if (!$this->_unpack($x, $t[1], $t[2], $t[3], $len, $z))
                                        return false;
                        }

                        /*
                        switch ($fldnum) {
                        case "11": // System Trace Number
                                if (!$this->_unpack($x, "N", 6, 6, $len, $z))
                                        return false;
                                break;
                        case "15": // Terminal Transaction Number
                                if (!$this->_unpack($x, "AN", 1, 17, $len, $z))
                                        return false;
                                break;
                        case "32": // SCV
                                if (!$this->_unpack($x, "N", 8, 8, $len, $z))
                                        return false;
                                break;
                        case "34": // EAN
                                if (!$this->_unpack($x, "AN", 32, 32, $len, $z)) // type?
                                        return false;
                                break;
                        case "35": // Track II Data
                                if (!$this->_unpack($x, "AN", 37, 37, $len, $z))
                                        return false;
                                break;
                        case "38": //Auth Code
                                if (!$this->_unpack($x, "AN", 6, 8, $len, $z))
                                        return false;
                                break;
                        case "39": // Response Code
                                if (!$this->_unpack($x, "N", 2, 4, $len, $z))
                                        return false;
                                break;
                        case "70": // Embossed Card Number
                                if (!$this->_unpack($x, "N", 13, 19, $len, $z))
                                        return false;
                                break;
                        case "76": // New Balance
                                if (!$this->_unpack($x, "N", 1, 12, $len, $z))
                                        return false;
                                break;
                        case "D0": // Transaction History Detail, for getHistory()
                                if (!$this->_unpack($x, "FDCD", 1, 512, $len, $z))
                                        return false;
                                break;
                        case "EB": // Count, for getHistory()
                                if (!$this->_unpack($x, "N", 1, 4, $len, $z))
                                        return false;
                                break;
                        case "EC": // Transaction Record Length, for getHistory()
                                if (!$this->_unpack($x, "N", 1, 3, $len, $z))
                                        return false;
                                break;
                        }
*/
                        $this->response_field[$fldnum] = $z;
                }
                if (!isset($this->response_field["39"]))
                        return $this->_set_garble("No response code");
                $response_code = $this->response_field["39"];
                $this->_dbg("response_code=" . $response_code);
                if ($response_code != "00" && $response_code != '38') { //RB: 46111198 FIX 12-9-2019 ... TO fix history throwing an error when no history
                        $this->result['svperrnum'] = $response_code;
                        $this->result['errmsg'] = $this->_svdot_errmsg($response_code);
                        $this->result['errnum'] = SVP_ERRNUM_REJECTED;
                        /*
                        switch ($response_code) {
                        case "02":
                        case "03":
                        case "05":
                        case "10":
                                $this->result['errnum'] = SVP_ERRNUM_REJECTED;
                                break;
                        }
*/
                        return false;
                }
                return true;
        }

        function _socket_error($sock)
        {
                $this->result['errmsg'] = socket_strerror(socket_last_error($sock));
                $this->result['errnum'] = SVP_ERRNUM_CONNECTION;
                socket_close($sock);
                return false;
        }

        // replace all card # digits except last 4 with X
        function _sanitize_field($fld, $msg)
        {
                $t = $this->field_table[$fld];
                $minlen = $t[2];
                $maxlen = $t[3];
                $regex = sprintf("/%s%02X([0-9]+)%s/", $this->field_separator, $fld, $this->field_separator);
                if (preg_match($regex, $msg, $match)) {
                        $XX = str_repeat("X", strlen($match[1]) - 4);
                        $lastfour = substr($match[1], -4);
                        $old = $match[0];
                        $new = sprintf("/%s%02X%s%s%s/", $this->field_separator, $fld, $XX, $lastfour, $this->field_separator);
                        $msg = str_replace($old, $new, $msg);
                }
                return $msg;
        }

        function _sanitize($msg)
        {
                $msg = $this->_sanitize_field(0x70, $msg);
                $msg = $this->_sanitize_field(0xED, $msg);
                return $msg;
        }

        function _req_do_raw($cmd, $req, &$vxnapi_error)
        {
                $vxnapi_error = 0;
                $this->result['svperrnum'] = "";
                $this->result['errmsg'] = "";
                $this->result['errnum'] = SVP_ERRNUM_NONE;

                $this->benchmark->mark("Firstdata_" . $cmd . "_start");
                /*
                $sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
                //socket_set_option($sock, getprotobyname('tcp'), TCP_NODELAY, 1); // disable nagel
                if (!@socket_connect($sock, $this->vxnapid_host, $this->vxnapid_port))
                        return $this->_socket_error($sock);
                if ($this->parameters['simulate_timeout']) {
                        $msg = "simulate_timeout\n";
                } else {
                        $msg = "transaction\n";
                        $msg .= $this->parameters['SVCID']."\n";
                        $msg .= $this->parameters['MID']."\n";
                        $msg .= $this->parameters['TID']."\n";
                        $msg .= $this->parameters['DID']."\n";
                        $msg .= $req."\n";
                        if (!empty($this->parameters['force_vxn_response_code']))
                                $msg .= $this->parameters['force_vxn_response_code']."\n";
                }
                $msg .= "\n\n";
                $this->_log("Parameters: ".print_r($this->parameters,1));
                $this->_log("Request to vxnapid: ".$req);
                logInfo("Request to vxnapid: ".$this->_sanitize($req));

                if (!socket_send($sock, $msg, strlen($msg), 0))
                        return $this->_socket_error($sock);

                $output = socket_read($sock, 2048, PHP_BINARY_READ);
                if ($output === FALSE)
                        return $this->_socket_error($sock);
                socket_close($sock);
                */

                $this->benchmark->mark("fsockopen_start");
                $fp = @fsockopen($this->vxnapid_host, $this->vxnapid_port, $errno, $errmsg, $this->parameters['connection_timeout']);
                $this->benchmark->mark("fsockopen_end");

                if (!$fp) {
                                logInfo("Socket Err Msg: " . print_r($errmsg, true));
                                $this->result['errmsg'] = "Problem with " . $this->vxnapid_host . " - " . $errmsg . " (" . $errno . ")";
                                $this->result['errnum'] = SVP_ERRNUM_CONNECTION;
                                return false;
                        }

                stream_set_timeout($fp, $this->parameters['read_timeout']);

                if ($this->parameters['simulate_timeout']) {
                                $msg = "simulate_timeout\n";
                        } else {
                                $msg = "transaction\n";
                                $msg .= $this->parameters['SVCID'] . "\n";
                                $msg .= $this->parameters['MID'] . "\n";
                                $msg .= $this->parameters['TID'] . "\n";
                                $msg .= $this->parameters['DID'] . "\n";
                                $msg .= $req . "\n";
                                if (!empty($this->parameters['force_vxn_response_code']))
                                        $msg .= $this->parameters['force_vxn_response_code'] . "\n";
                        }
                $msg .= "\n\n";

                $this->_log("Parameters: " . print_r($this->parameters, 1));
                $this->_log("Request to vxnapid: " . $req);
                logInfo("Request to vxnapid: " . $this->_sanitize($req));

                $this->benchmark->mark("fwrite_start");
                @fwrite($fp, $msg);
                $this->benchmark->mark("fwrite_end");
                $this->benchmark->mark("fread_start");
                $output = '';
                while (!feof($fp)) {

                                // stream_set_timeout does not work with SSL, see http://bugs.php.net/bug.php?id=47929
                                $r[] = $fp;
                                $w = array();
                                $e = array();
                                if (!stream_select($r, $w, $e, $this->parameters['read_timeout'])) {
                                                $this->result['errmsg'] = "Problem with " . $this->vxnapid_host . " - Timed out after " . $this->parameters['read_timeout'] . "sec";
                                                $this->result['errnum'] = SVP_ERRNUM_CONNECTION;
                                                return false;
                                        }

                                $output .= @fread($fp, 2048);
                                $meta = stream_get_meta_data($fp);
                                //$this->_dump("meta", $meta);
                                if ($meta['timed_out']) {
                                                $this->result['errmsg'] = "Problem with " . $this->vxnapid_host . " - Timed out after " . $this->parameters['read_timeout'] . "sec";
                                                $this->result['errnum'] = SVP_ERRNUM_CONNECTION;
                                                return false;
                                        }
                        }
                @fclose($fp);
                $this->benchmark->mark("fread_end");

                $this->benchmark->mark("Firstdata_" . $cmd . "_end");
                $this->_log("Response from vxnapid: " . $output);
                logInfo("Response from vxnapid: " . $this->_sanitize($output));
                if (!preg_match('/^VXN: (.*)$/Usm', $output, $regs)) {
                        // indeterminate vxnapi_client error
                        $this->result['errmsg'] = $output;
                        $this->result['errnum'] = SVP_ERRNUM_INTERNAL;
                        return false;
                }
                $vxn_status = $regs[1];
                if ($vxn_status) {
                        // vxnapi error
                        $vxnapi_error = 1;
                        $this->result['svperrnum'] = $vxn_status;
                        $this->result['errmsg'] = 'Firstdata/VXN: ' . $vxn_status . ' - ' . $this->_vxn_errmsg($vxn_status);
                        $this->result['errnum'] = SVP_ERRNUM_CONNECTION;
                        return false;
                }
                if (!preg_match('/^Response: (.*)$/Usm', $output, $regs)) {
                        $this->result['errmsg'] = 'Unrecognizable response';
                        $this->result['errnum'] = SVP_ERRNUM_GARBLE;
                        return false;
                }
                $response = $regs[1];
                if (!$this->_parse_response($response))
                        return false;
                return true;
        }

        function _req_do_cooked($cmd, $ntry, $reversal_required)
        {
                // From http://php.net/manual/en/function.set-time-limit.php:
                //
                // Note: The set_time_limit() function and the configuration directive max_execution_time
                // only affect the execution time of the script itself. Any time spent on activity that
                // happens outside the execution of the script such as system calls using system(), stream
                // operations, database queries, etc. is not included when determining the maximum time
                // that the script has been running. This is not true on Windows where the measured time
                // is real.
                //
                // We set a limit that exceeds our worst case expected run time.
                //set_time_limit(intval(5+$ntry*($this->parameters['connection_timeout']+$this->parameters['read_timeout']+10)+0.5));
                $req = $this->request_header . join("", $this->request_field);

                for ($tryi = 0; $tryi < $ntry; $tryi++) {
                        $ret = $this->_req_do_raw($cmd, $req, $vxnapi_error);
                        if ($ret)
                                return $ret;
                        $this->_logInfo("Try " . $tryi . " " . $cmd . " " . $this->result['errmsg']);
                        if (!$vxnapi_error && isset($this->result['svperrnum']))
                                break;
                }
                // no response after $nry tries
                if ($vxnapi_error && $reversal_required) {
                        // FD4016 p24
                        $saved_result = $this->result;
                        $cmd = "Timeout Reversal";
                        $this->parameters['simulate_timeout'] = 0;
                        $this->parameters['force_vxn_response_code'] = "";
                        $original_request_field = $this->request_field;
                        if (!$this->_req_header(704))
                                return $this->result;
                        if (!$this->_pack_field_F6($this->transaction_request_code))
                                return $this->result;
                        $req = $this->request_header . join("", $this->request_field) . join("", $original_request_field);
                        for ($tryi = 0; $tryi < 3; $tryi++) {
                                $ret = $this->_req_do_raw($cmd, $req, $vxnapi_error);
                                if ($ret)
                                        break; // reversal went through
                                if (!$vxnapi_error && isset($this->result['svperrnum']))
                                        break; // error response, "probably nothing to reverse" or "already reversed"
                                $this->_logInfo("Try " . $tryi . " " . $cmd . " " . $this->result['errmsg']);
                        }
                        $this->result = $saved_result;
                }
                //4-25-2012 --- special case if account closed aka 0 balance for a while, just say 0 balance rather than an error
                //if ($this->result['svperrnum'] === '02' && $cmd === 'Inquire Current Balance') { //Commented this out RB: 48022538
                if ($this->result['svperrnum'] === '02' && ($cmd === 'Inquire Current Balance' || stripos($cmd,'Transaction History') !== false)) { //Added the stripos so it would match the 3 types of history statements RB: 48022538
                        $this->response_field["76"] = 0;
                        return true;
                }
                //END 4-25-2012
                //$msg = "Failed ".$cmd;
                //$msg .= " parameters=".print_r($this->parameters,1);
                //$this->_logInfo($msg);

                $msg = "Failed " . $cmd;
                $msg .= " - " . $this->result['errnum'];
                $msg .= " - Firstdata/SVdot error message and code: " . $this->result['errmsg'];
                if (isset($this->result['svperrnum']))
                        $msg .= " - " . $this->result['svperrnum'];
                switch ($cmd) {
                        case 'Inquire Current Balance':
                                $this->_logInfo($msg);
                                break;
                        case 'Inquire Transaction History':
                                //$this->_logInfo($msg); //don't log this because we get a lot of these that are not really errors because we get it 3 times
                                break;
                        default:
                                $this->_logInfo($msg);
                                break;
                }
                return false;
        }

        // like _req_do_loop_on_client_id() but uses the card's actual client_id (F7) to minimize looping
        // avoids ValueLink $TXNLOG_MAX_FAIL_COUNT limit (more than this many errors per second will produce error 09)
        /*
        function _req_do_smart_client_id($cmd, $ntry, $reversal_required) {
                $TXNLOG_MAX_FAIL_COUNT = 3;
                if (empty($this->parameters['client_id']))
                        return $this->_req_do_cooked($cmd, $ntry, $reversal_required);
                $a = explode(",", $this->parameters['client_id']);
                if (count($a) > 1)
                        logInfo("multiconsortium smart ".$this->parameters['client_id']);
                $n = 0;
                foreach ($a as $client_id) {
                        $this->parameters['client_id'] = $client_id;
                        if (!$this->_pack_field_CA())
                                return false;
                        $n++;
                        if ($n >= $TXNLOG_MAX_FAIL_COUNT)
                                $t0 = microtime(true);
                        if ($this->_req_do_cooked($cmd, $ntry, $reversal_required))
                                return true;
                        // request failed
                        //if ($this->result['svperrnum'] !== "07")
                        //      return false; // give up on any error status other than 07 - "Invalid merchant..."
                        if (isset($this->response_field["F7"])) {
                                // if present, field F7 contains the correct client_id
                                $client_id = $this->response_field["F7"];
                                logInfo("actual client_id ".$client_id);
                                if (array_search($client_id, $a) === false)
                                        return false; // this card's actual client_id doesn't match any we expect
                                $this->parameters['client_id'] = $client_id;
                                if (!$this->_pack_field_CA())
                                        return false;
                                return $this->_req_do_cooked($cmd, $ntry, $reversal_required);
                        }
                        if ($n >= $TXNLOG_MAX_FAIL_COUNT) {
                                // avoid ValueLink $TXNLOG_MAX_FAIL_COUNT limit
                                $us_wait = 1.1 * 1000000; // total time to wait
                                $us_used = round(microtime(true)-$t0); // time already spent on request/response
                                $us = round($us_wait - $us_used);
                                if ($us > 0) {
                                        logInfo("TXNLOG_MAX_FAIL_COUNT delay ".$us);
                                        usleep($us_wait - $us_used);
                                }
                        }
                }
                return false; // all consortium values produced error, return last
        }
*/

        // like _req_do_cooked() but brute force loop over all client_id
        /*
        function _req_do_loop_on_client_id($cmd, $ntry, $reversal_required) {
                if (empty($this->parameters['client_id']))
                        return $this->_req_do_cooked($cmd, $ntry, $reversal_required);
                $a = explode(",", $this->parameters['client_id']);
                if (count($a) > 1)
                        logInfo("multiconsortium loop ".$this->parameters['client_id']);
                foreach ($a as $client_id) {
                        $this->parameters['client_id'] = $client_id;
                        if (!$this->_pack_field_CA())
                                return false;
                        if ($this->_req_do_cooked($cmd, $ntry, $reversal_required))
                                return true;
                }
                return false; // all consortium values produced error, return last
        }
*/

        // like _req_do_cooked() but simply checks that returned client_id matches one we expect
        // replaces _req_do_smart_client_id() and _req_do_loop_on_client_id()
        function _req_do_simple_client_id($cmd, $ntry, $reversal_required)
        {
                if (empty($this->parameters['client_id']))
                        return $this->_req_do_cooked($cmd, $ntry, $reversal_required);
                $client_id_list = $this->parameters['client_id'];
                logInfo("multiconsortium simple " . $client_id_list);
                if (!$this->_req_do_cooked($cmd, $ntry, $reversal_required))
                        return false;
                if (!isset($this->response_field["F7"])) {
                        $errmsg = "response field F7 is not set.";
                        logError($errmsg);
                        $this->result['errmsg'] = $errmsg;
                        $this->result['errnum'] = SVP_ERRNUM_INTERNAL;
                        return false;
                }
                // verify the actual client_id matches one in our parameter list
                $client_id = $this->response_field["F7"];
                $a = explode(",", $client_id_list);
                if (array_search($client_id, $a) === false) {
                        $errmsg = "response field F7 " . $client_id . " doesn't match client_id " . $client_id_list;
                        logError($errmsg);
                        $errmsg = "response field F7 doesn't match client_id";
                        $this->result['errmsg'] = $errmsg;
                        $this->result['errnum'] = SVP_ERRNUM_INTERNAL;
                        return false;
                }
                logInfo("actual client_id " . $client_id);
                return true;
        }

        function _req_do($cmd, $ntry, $reversal_required)
        {
                //return $this->_req_do_loop_on_client_id($cmd, $ntry, $reversal_required); // force all transactions to loop on client_id
                return $this->_req_do_cooked($cmd, $ntry, $reversal_required); // ignore client_id
        }

        function _set_parameters($parameters)
        {
        $parameters = $this->parameterOverrides($parameters);
                $this->parameters = array_merge($this->default_parameters, $parameters);

                $cc = $this->parameters['currency_code'];
                if (isset($this->currency_code_map[$cc]))
                        $this->parameters['currency_code'] = $this->currency_code_map[$cc];

                foreach ($this->parameters as $key => $val) {
                        if (is_null($val)) {
                                $errmsg = "Firstdata parameter " . $key . " is not set.";
                                logError($errmsg);
                                $this->result['errmsg'] = $errmsg;
                                $this->result['errnum'] = SVP_ERRNUM_INTERNAL;
                                return false;
                        }
                }
                //echo("<pre>OUT parameters=".print_r($parameters,1)."</pre>");
                //echo("<pre>DEF parameters=".print_r($this->default_parameters,1)."</pre>");
                //echo("<pre>ALL parameters=".print_r($this->parameters,1)."</pre>");
                return true;
        }

        function _preamble($parameters, $card)
        {
                date_default_timezone_set("UTC"); // affects return value of date() calls
                $this->time = time();
                if (!$this->_set_parameters($parameters))
                        return false;
                $this->_dbg(date("Y-m-d\TH:i:sP") . "\n");
                $this->_dbg("parameters=" . print_r($parameters, 1) . "\n");
                if (isset($card['msg_id']))
                        $this->vdata['msg_id'] = $card['msg_id'];
                if (isset($card['merchant_id']))
                        $this->vdata['merchant_id'] = $card['merchant_id'];

                //$this->_pack_transaction_id(); //Added 1-19-2019 to support sending transaction id over
                $this->_pack_order_details(); //Added 6/5/2018 overrides mistaken requirement to send transaction id
                $this->_pack_user_details(); //Added 8-2-2019 -- RB 44566887
                $this->_pack_mid_details(); //ENG-2087
                return true;
        }

    private function parameterOverrides($parameters) {
        $overrides = $this->svp_model->getOverrides();
        if (!empty($overrides)) {
            logInfo('Applying parameter overrides');
            $parameters = array_merge($parameters, $overrides);
        }
        $this->svp_model->setOverrides(null);
        return $parameters;
    }

        function getBalance($parameters, $vdata, &$amt)
        {
                $this->vdata = $vdata;
                $card = $this->vdata['recipient']['card'];

                if (!$this->_preamble($parameters, $card))
                        return $this->result;
                $cmd = 'Inquire Current Balance';
                if (!$this->_req_header(2400))
                        return $this->result;
                if (!$this->_pack_field_09())
                        return $this->result;
                if (!$this->_pack_field_10())
                        return $this->result;
                if (!$this->_pack_field_12())
                        return $this->result;
                if (!$this->_pack_field_13())
                        return $this->result;
                if (!$this->_pack_card($card))
                        return (isset($this->parameters['use_std_error']) && $this->parameters['use_std_error']) ? $this->_getStdErr($this->result) : $this->result;
                if (!$this->_pack_field_F3())
                        return $this->result;
                if (!$this->_pack_field_42())
                        return $this->result;
                if (!$this->_pack_field_EA())
                        return $this->result;
                if (!$this->_pack_field_15())
                        return $this->result;
                if (!$this->_pack_field_53())
                        return $this->result;
                if (!$this->_pack_field_C0())
                        return $this->result;
                if (!$this->_pack_field_44())
                        return $this->result;
                //if (!$this->_req_do($cmd, 1, 0))
                if (!$this->_req_do_simple_client_id($cmd, 1, 0))
                        return (isset($this->parameters['use_std_error']) && $this->parameters['use_std_error']) ? $this->_getStdErr($this->result) : $this->result;
                if (!isset($this->response_field["76"])) {
                        $this->_set_garble("No amount");
                        return (isset($this->parameters['use_std_error']) && $this->parameters['use_std_error']) ? $this->_getStdErr($this->result) : $this->result;
                }

        // save client_id here, regardless if we have the parameter set
        $this->svp_model->setClientId($this->response_field["F7"]);
                $amt = $this->response_field["76"] / 100.0;
                return true;
        }

        function getHistory($parameters, $vdata, &$history)
        {
                $history = array();
                //2-19-2019 - JC
                //Run a normal balance first that supports client_id locking and if the balance
                //works then run the history. RB #40281026
                $amt = 0;
                $bc_result = $this->getBalance($parameters, $vdata, $amt);
                if (is_array($bc_result) || $bc_result !== true) {
                                $history = null; //JC - Added 9-30-2019  -- RB: 45441403  | Added this because if still an empty array then it assumes successful, so we need to set it to null, to force error condition.
                                logError('Enhanced Balance:  Running standard balance first failed, so not running enhanced balance.');
                                return $bc_result;
                        }
                if (!empty($parameters['HIS_MID']) && strlen($parameters['HIS_MID']) > 1) {
                                logInfo('Enhanced Balance:  Found HS_MID set so using that for the enhanced balance.');
                                $this->parameters['MID'] = $parameters['HIS_MID'];
                        }
                //End 2-19-2019
                $max_loops = 3; //return 30 results max
                $step = 10;
                //ok now do some work
                $offset = 10;
                $new_block = array();
                for ($counter = 0; $counter < $max_loops; $counter++) {
            $result = $this->_getHistory($parameters, $vdata, $offset, $new_block);
            //RB - 46111198  -- 11-21-2019 -- Return a balance if card is good, but has no history
            if (isset($new_block[0]['no_history']) && $new_block[0]['no_history'] === true) {
                if (count($history) == 0) {
                    $history = $new_block;
                }
                // else just return the history we have
                return true;
            } //END RB 46111198
            //see if we are ok or not
            if ($result !== true) {
                //logInfo('FD: '.print_r($result,true));
                if (isset($result['svperrnum']) && $result['svperrnum'] == '38' && count($history) > 0) { //38 means no more history...not really an error
                    return true;
                }
                $history = null;
                return $result; //we did not get 38 so there is some other type of error
            }
            //have to do this so the oldest blocks of history are first in the array
            $temp = $history;
            $history = array();
            foreach ($new_block as $item)
                $history[] = $item;
            foreach ($temp as $item)
                $history[] = $item;

            $new_block = array();
            //Keep Going!
            $offset = $offset + $step;
        }
                return true;
        }

        function _getHistory($parameters, $vdata, $offset, &$history)
        {
        $this->load->library('firstdata');

                //$history = array();
                $this->vdata = &$vdata;
                $card = $this->vdata['recipient']['card'];
                if (!$this->_preamble($parameters, $card))
                        return $this->result;
                $history_code = $this->parameters['history_code'];
                $cmd = 'Inquire Mystery History';
                if ($history_code == "2410") // complete but more of a burden on FD's database
                        $cmd = 'Inquire Transaction History';
                else if ($history_code == "2411") // only reports transactions in the past 6 months
                        $cmd = 'Inquire Recent Transaction History';
                else if ($history_code == "2413") // not yet tested
                        $cmd = 'Inquire Activation/Reload Transaction History';
                if (!$this->_req_header($history_code))
                        return $this->result;
                if (!$this->_pack_field_09())
                        return $this->result;
                if (!$this->_pack_field_10())
                        return $this->result;
                if (!$this->_pack_field_12())
                        return $this->result;
                if (!$this->_pack_field_13())
                        return $this->result;
                if (!$this->_pack_card($card))
                        return $this->result;
                if (!$this->_pack_field_F3())
                        return $this->result;
                if (!$this->_pack_field_42())
                        return $this->result;
                if (!$this->_pack_field_EA())
                        return $this->result;
                if (!$this->_pack_field_15())
                        return $this->result;
                if (!$this->_pack_field_53())
                        return $this->result;
                if (!$this->_pack_field_44())
                        return $this->result;
                if (!$this->_pack_field_CE($offset))
                        return $this->result;
                if (!$this->_pack_field_CF())
                        return $this->result;
                if (!$this->_pack_field_E8())
                        return $this->result;
                if (!$this->_req_do($cmd, 1, 0))
                        return $this->result;
                if (!isset($this->response_field["D0"])) {
                        //$this->_set_garble("No history"); //JC commented out 4-9-2013
                        //return $this->result; //JC commented out 4-9-2013

                        $h['no_history'] = true;
                        $h['balance'] = $this->response_field["76"] / 100.0;
                        $history[] = $h;
                        return true;
                        //END JC Additions 4-9-2013
                }

                // FIXME: some work may be required here
                // FirstData data comes back in $this->response_field["D0"] looking something like this:
                /*
    [D0] => Array (
            [Account status indicator] => 2
            [Detail version format indicator] => 84
            [Base Currency Code] => 840
            [Detail Records] => Array (
                    [0] => Array (
                            [Alternate Merchant Number] => 0
                            [Terminal Number] => 9999
                            [Request Code] => 2102
                            [Amount Sign] => +
                            [Transaction Amount in Base] => 10000
                            [Account Balance in Base] => 10000
                            [Local Lock Amount] => 0
                            [Month] => 8
                            [Day] => 23
                            [Century] => 20
                            [Year] => 12
                            [Hour] => 16
                            [Minutes] => 14
                        )
                    [1] => Array (
                            [Alternate Merchant Number] => 0
                            [Terminal Number] => 9999
                            [Request Code] => 2300
                            [Amount Sign] => +
                            [Transaction Amount in Base] => 2500
                            [Account Balance in Base] => 12500
                            [Local Lock Amount] => 0
                            [Month] => 8
                            [Day] => 23
                            [Century] => 20
                            [Year] => 12
                            [Hour] => 16
                            [Minutes] => 14
                        )
                    [2] => Array (
                            [Alternate Merchant Number] => 0
                            [Terminal Number] => 9999
                            [Request Code] => 2300
                            [Amount Sign] => +
                            [Transaction Amount in Base] => 2500
                            [Account Balance in Base] => 15000
                            [Local Lock Amount] => 0
                            [Month] => 8
                            [Day] => 23
                            [Century] => 20
                            [Year] => 12
                            [Hour] => 16
                            [Minutes] => 14
                        )
                )
        )
*/
                // this is what we're trying to produce:
                // $history = array('purchase_amount' => '0.00', 'purchase_date' => '1/1/1970', 'purchase_store_number' => '0000' );
                //echo("response_field: <pre>".print_r($this->response_field,1)."</pre>\n"); //pgar
                //file_put_contents('/tmp/enhanced_bc.txt',print_r($this->response_field,true));

                $D0 = $this->response_field["D0"];
                foreach ($D0['Detail Records'] as $rec) {
                        if (true) //do advanced lookup
                                {
                    $type = $this->firstdata->historyRequestCodeString($rec['Request Code']);
                    /*
                                        $type = '';
                                        switch ($rec['Request Code']) {
                                                case 28:
                                                        $type = 'Activation Reloadable';
                                                        break;
                                                case 18: //Added for Red Robin activate the cards directly through VLBC -- 4-16-2015
                                                case 100:
                                                case 101:
                                                case 102:
                                                case 103:
                                                case 104:
                                                case 2100:
                                                case 2101:
                                                case 2102:
                                                case 2103:
                                                case 2104:
                                                case 6100:
                                                case 6101:
                                                        $type = 'Activation';
                                                        break;
                                                case 400:
                                                case 401:
                                                case 402:
                                                case 403:
                                                case 2400:
                                                case 2401:
                                                        $type = 'Balance Inquiry';
                                                        break;
                                                case 200:
                                                case 202:
                                                case 201:
                                                case 2201:
                                                case 2202:
                                                case 2204:
                                                        $type = 'Redemption';
                                                        break;
                                                case 300:
                                                case 301:
                                                case 2300:
                                                case 2301:
                                                case 6300:
                                                case 6301:
                                                        $type = 'Reload';
                                                        break;
                                                case 2700:
                                                        $type = 'Refund';
                                                        break;
                                                case 3460:
                                                case 460:
                                                        $type = 'Adjustment';
                                                        break;
                                                case 600:
                                                        $type = 'Cash Out';
                                                        break;
                                                default: //not sure what the code is here
                                                        $type = 'System (' . $rec['Request Code'] . ')';
                                                        //continue;
                                                        break;
                                        }
                    */
                                        $h['purchase_amount'] = $rec['Transaction Amount in Base'] / 100.0;
                                        $h['purchase_date'] = $rec['Month'] . '/' . $rec['Day'] . '/' . $rec['Century'] . sprintf("%02d", $rec['Year']);
                                        $h['purchase_store_number'] = $rec['Alternate Merchant Number'];
                                        //$h['purchase_store_number'] = $rec['Terminal Number'];
                                        //JC adding history params that have more logical names -- but leaving the old stuff in so it does not kill other stuff
                                        $h['amount'] = money_format('%i', $h['purchase_amount']);
                                        $h['sign'] = $rec['Amount Sign'];
                                        $h['date'] = $h['purchase_date'];
                                        $h['store'] = $h['purchase_store_number'];
                                        $h['type'] = $type;
                                        $h['balance'] = $rec['Account Balance in Base'] / 100.0;
                    $h['request_code'] = $rec['Request Code'];
                                        $history[] = $h;
                                } else {
                                        if ($rec['Request Code'] != 2201) // Redemption (there are other types too)
                                                continue;
                                        $h['purchase_amount'] = $rec['Transaction Amount in Base'] / 100.0;
                                        $h['purchase_date'] = $rec['Month'] . '/' . $rec['Day'] . '/' . $rec['Century'] . sprintf("%02d", $rec['Year']);
                                        $h['purchase_store_number'] = $rec['Alternate Merchant Number'];
                                        //$h['purchase_store_number'] = $rec['Terminal Number'];
                                        //JC adding history params that have more logical names -- but leaving the old stuff in so it does not kill other stuff
                                        $h['amount'] = $h['purchase_amount'];
                                        $h['date'] = $h['purchase_date'];
                                        $h['store'] = $h['purchase_store_number'];
                                        $history[] = $h;
                                }
                }
                //echo("history: ".print_r($history,1)."\n"); //pgar
                return true;
        }

        function _getStdErr($result)
        {
                $std_error_map = array(
                        "invalid bin" => "Invalid Card Number", "invalid source_code value" => "Invalid Request", "invalid merchant." => "Invalid Request", "authentication failed" => "Authorization Failed", "42 - http error" => "Authorization Failed"
                );

                if (strlen($result['errmsg'])) {
                                $std_err = 0;
                                foreach ($std_error_map as $err_msg => $std_error) {
                                                if (strpos(strtolower($result['errmsg']), $err_msg) !== false) {
                                                                $std_err = 1;
                                                                $result['errmsg'] = $std_error;
                                                        }
                                        }

                                if (!$std_err) {
                                                $result['errmsg'] = "Temporary Error";
                                        }
                        }

                return $result;
        }

        function incBalance($parameters, $card, $amt)
        {
                if (!$this->_preamble($parameters, $card))
                        return $this->result;
                $cmd = 'Reload';
                if (!isset($this->vdata['msg_id']))
                        logInfo("incBalance no msg_id ENG-3117");

                if (in_array(CLUSTER, array('dev', 'test'))) {
                                if (isset($parameters['force_error_percentage']) && $parameters['force_error_percentage'] != 0) {
                                                $random_number = rand(1, 100);

                                                if (is_numeric($parameters['force_error_percentage']) && $parameters['force_error_percentage'] > 0 && $parameters['force_error_percentage'] <= 100) {
                                                                if ($random_number <= $parameters['force_error_percentage']) {
                                                                                logError('Reload FORCE error percentage hit!');
                                                                                return array(
                                                                                        'errmsg' => "TEST: Reload force error hit. Error percent setting: {$parameters['force_error_percentage']}%.",
                                                                                        'errnum' => SVP_ERRNUM_INTERNAL
                                                                                );
                                                                        }
                                                        } else {
                                                                logError('FirstData model "force_error_percentage" must be numeric and in the range of 0-100. value: ' . $parameters['force_error_percentage']);
                                                        }
                                        }

                                if (isset($parameters['force_error_card_number'])) {
                                                $error_card_numbers = explode(',', $parameters['force_error_card_number']);

                                                if (in_array($card['account_number'], $error_card_numbers)) {
                                                                logError("Reload FORCE error card number hit!  Card number: {$card['account_number']}");
                                                                return array(
                                                                        'errmsg' => "TEST: Reload force error card hit. Card number: {$card['account_number']}.",
                                                                        'errnum' => SVP_ERRNUM_INTERNAL
                                                                );
                                                        }
                                        }
                        }

                if (!$this->_req_header(2300))
                        return $this->result;
                if (!$this->_pack_field_04($amt))
                        return $this->result;
                if (!$this->_pack_field_09())
                        return $this->result;
                if (!$this->_pack_field_10())
                        return $this->result;
                if (!$this->_pack_field_12())
                        return $this->result;
                if (!$this->_pack_field_13())
                        return $this->result;
                if (!$this->_pack_card($card))
                        return $this->result;
                if (!$this->_pack_field_F3())
                        return $this->result;
                if (!$this->_pack_field_42())
                        return $this->result;
                if (!$this->_pack_field_EA())
                        return $this->result;
                if (!$this->_pack_field_15())
                        return $this->result;
                if (!$this->_pack_field_53())
                        return $this->result;
                if (!$this->_pack_field_C0())
                        return $this->result;
                if (!$this->_pack_field_44())
                        return $this->result;
                if (!$this->_req_do($cmd, 1, 1))
                        return $this->result;
                return true;
        }

        /* not tested/certified */
        function decBalance($parameters, $card, $amt)
        {
                if (!$this->_preamble($parameters, $card))
                        return $this->result;
                $cmd = 'Redemption Unlock';
                if (!$this->_req_header(2201))
                        return $this->result;
                if (!$this->_pack_field_04($amt))
                        return $this->result;
                if (!$this->_pack_field_09())
                        return $this->result;
                if (!$this->_pack_field_10())
                        return $this->result;
                if (!$this->_pack_field_12())
                        return $this->result;
                if (!$this->_pack_field_13())
                        return $this->result;
                if (!$this->_pack_card($card))
                        return $this->result;
                if (!$this->_pack_field_F3())
                        return $this->result;
                if (!$this->_pack_field_42())
                        return $this->result;
                if (!$this->_pack_field_EA())
                        return $this->result;
                if (!$this->_pack_field_15())
                        return $this->result;
                if (!$this->_pack_field_53())
                        return $this->result;
                if (!$this->_pack_field_C0())
                        return $this->result;
                if (!$this->_pack_field_44())
                        return $this->result;
                if (!$this->_req_do($cmd, 1, 1))
                        return $this->result;
                return true;
        }

        function _activateVirtualCard(&$card, $amt)
        {
                $cmd = "Activate Virtual Card";
                if (!$this->_req_header(2102))
                        return $this->result;
                if (!$this->_pack_field_04($amt))
                        return $this->result;
                if (!$this->_pack_field_09())
                        return $this->result;
                if (!$this->_pack_field_10())
                        return $this->result;
                if (!$this->_pack_field_12())
                        return $this->result;
                if (!$this->_pack_field_13())
                        return $this->result;
                if (!$this->_pack_field_42())
                        return $this->result;
                if (!$this->_pack_field_F3())
                        return $this->result;
                if (!$this->_pack_field_EA())
                        return $this->result;
                if (!$this->_pack_field_F2())
                        return $this->result;
                if (!$this->_pack_field_15())
                        return $this->result;
                if (!$this->_pack_field_53())
                        return $this->result;
                if (!$this->_pack_field_C0())
                        return $this->result;
                if (!$this->_pack_field_44())
                        return $this->result;
                if (!$this->_req_do($cmd, 1, 1))
                        return $this->result;
                if (!isset($this->response_field["70"])) {
                        $this->_set_garble("No card number");
                        return $this->result;
                }
                //echo "Response: <pre>" . print_r($this->response_field, TRUE) . "</pre>";
                $card['account_number'] = $this->response_field["70"];
                if (isset($this->response_field["34"])) {
                        // decrypt EAN if present
                        $ean = $this->response_field["34"];
                        $ean = trim($ean);
                        if (!empty($ean)) {
                                $cmd = "firstdata_crypt decrypt_ean " . $this->parameters['mwk'] . " " . $ean;
                                exec($cmd, $outraw, $status);
                                $output = join("\n", $outraw);
                                if ($status != 0) {
                                        $msg = "EAN decryption failed ($cmd): " . $output;
                                        $this->result['errmsg'] = $msg;
                                        $this->result['errnum'] = SVP_ERRNUM_INTERNAL;
                                        return $this->result;
                                }
                                $card['reference_number'] = $card['pin'] = trim($output);
                                logInfo('Activated with First Data, PIN: ' . $card['reference_number']);
                        }
                }

                if (isset($this->response_field['A0'])) {
                                $expiration_year = substr($this->response_field['A0'], 4);
                                if ($expiration_year < 3000) {
                                                $card['extra_data'] = http_build_query(array(
                                                        'ExpirationDate'     => $this->response_field['A0']
                                                ));
                                        }
                        }

                if ($this->parameters["cardextra"]) {
                                $card["card_extra"] = array();
                                $card["card_extra"]["txn_auth_code"] = $this->response_field["38"];
                                $card["card_extra"]["txn_resp_code"] = $this->response_field["39"];
                                $card["card_extra"]["txn_req_code"] = "2102";
                                //logInfo("card record with extra data is ".print_r($card,TRUE));
                        }
                return true;
        }

        function _activatePhysicalCard($card, $amt)
        {
                $cmd = "Activate Physical Card";
                if (!$this->_req_header(2104))
                        return $this->result;
                if (!$this->_pack_field_04($amt))
                        return $this->result;
                if (!$this->_pack_field_09())
                        return $this->result;
                if (!$this->_pack_field_10())
                        return $this->result;
                if (!$this->_pack_field_12())
                        return $this->result;
                if (!$this->_pack_field_13())
                        return $this->result;
                if (!$this->_pack_card($card))
                        return $this->result;
                if (!$this->_pack_field_F3())
                        return $this->result;
                if (!$this->_pack_field_42())
                        return $this->result;
                if (!$this->_pack_field_EA())
                        return $this->result;
                if (!$this->_pack_field_15())
                        return $this->result;
                if (!$this->_pack_field_53())
                        return $this->result;
                if (!$this->_pack_field_C0())
                        return $this->result;
                if (!$this->_pack_field_44())
                        return $this->result;
                if ($this->parameters['promotion_code'] != "0")
                        if (!$this->_pack_field_F2())
                                return $this->result;
                if (!$this->_req_do($cmd, 1, 1))
                        return $this->result;

                if (isset($this->response_field['A0'])) {
                                $expiration_year = substr($this->response_field['A0'], 4);
                                if ($expiration_year < 3000) {
                                                $card['extra_data'] = http_build_query(array(
                                                        'ExpirationDate'     => $this->response_field['A0']
                                                ));
                                        }
                        }

                if ($this->parameters["cardextra"]) {
                                $card["card_extra"] = array();
                                $card["card_extra"]["txn_auth_code"] = $this->response_field["38"];
                                $card["card_extra"]["txn_resp_code"] = $this->response_field["39"];
                                $card["card_extra"]["txn_req_code"] = "2104";
                        }
                return true;
        }

        function activateCard($parameters, &$card, $amt)
        {
                if (!empty($parameters['activation_code'])) {
                        switch ($parameters['activation_code']) {
                                case "2101":
                                        return $this->t_2101($parameters, $card, $amt);
                                case "2107":
                                        return $this->t_2107($parameters, $card, $amt);
                                case "2108":
                                        return $this->t_2108($parameters, $card, $amt);
                        }
                }
                if (!$this->_preamble($parameters, $card))
                        return $this->result;
                if (!isset($this->vdata['msg_id']))
                        logInfo("activateCard no msg_id ENG-3117");
                if (empty($card['account_number']))
                        return $this->_activateVirtualCard($card, $amt);
                return $this->_activatePhysicalCard($card, $amt);
        }

        function voidCard($parameters, $card, $amt)
        {
                $local_result = $this->voidActivation($parameters, $card, $amt);
                if ($local_result !== true)
                        return $this->cashoutCard($parameters, $card);
                else
                        return $local_result;
        }

        function cashoutCard($parameters, $card)
        {
                if (!$this->_preamble($parameters, $card))
                        return $this->result;
                $cmd = "Cash Out";
                if (!$this->_req_header(2600))
                        return $this->result;
                if (!$this->_pack_field_09())
                        return $this->result;
                if (!$this->_pack_field_10())
                        return $this->result;
                if (!$this->_pack_field_12())
                        return $this->result;
                if (!$this->_pack_field_13())
                        return $this->result;
                if (!$this->_pack_card($card))
                        return $this->result;
                if (!$this->_pack_field_F3())
                        return $this->result;
                if (!$this->_pack_field_42())
                        return $this->result;
                if (!$this->_pack_field_EA())
                        return $this->result;
                if (!$this->_pack_field_15())
                        return $this->result;
                if (!$this->_pack_field_53())
                        return $this->result;
                if (!$this->_pack_field_C0())
                        return $this->result;
                if (!$this->_pack_field_44())
                        return $this->result;
                if (!$this->_req_do($cmd, 1, 1))
                        return $this->result;
                return true;
        }

        function voidRedemption($parameters, $card, $amt)
        {
                if (!$this->_preamble($parameters, $card))
                        return $this->result;
                $cmd = "Void Of Redemption";
                if (!$this->_req_header(2800))
                        return $this->result;
                if (!$this->_pack_field_04($amt))
                        return $this->result;
                if (!$this->_pack_field_09())
                        return $this->result;
                if (!$this->_pack_field_10())
                        return $this->result;
                if (!$this->_pack_field_12())
                        return $this->result;
                if (!$this->_pack_field_13())
                        return $this->result;
                if (!$this->_pack_card($card))
                        return $this->result;
                if (!$this->_pack_field_F3())
                        return $this->result;
                if (!$this->_pack_field_42())
                        return $this->result;
                if (!$this->_pack_field_EA())
                        return $this->result;
                if (!$this->_pack_field_15())
                        return $this->result;
                if (!$this->_pack_field_53())
                        return $this->result;
                if (!$this->_pack_field_C0())
                        return $this->result;
                if (!$this->_pack_field_44())
                        return $this->result;
                if (!$this->_req_do($cmd, 1, 1))
                        return $this->result;
                return true;
        }

        function voidActivation($parameters, $card, $amt)
        {
                if (!$this->_preamble($parameters, $card))
                        return $this->result;
                $cmd = "Void Of Activation";
                if (!$this->_req_header(2802))
                        return $this->result;
                if (!$this->_pack_field_04($amt))
                        return $this->result;
                if (!$this->_pack_field_09())
                        return $this->result;
                if (!$this->_pack_field_10())
                        return $this->result;
                if (!$this->_pack_field_12())
                        return $this->result;
                if (!$this->_pack_field_13())
                        return $this->result;
                if (!$this->_pack_card($card))
                        return $this->result;
                if (!$this->_pack_field_F3())
                        return $this->result;
                if (!$this->_pack_field_42())
                        return $this->result;
                if (!$this->_pack_field_EA())
                        return $this->result;
                if (!$this->_pack_field_15())
                        return $this->result;
                if (!$this->_pack_field_53())
                        return $this->result;
                if (!$this->_pack_field_C0())
                        return $this->result;
                if (!$this->_pack_field_44())
                        return $this->result;
                if (!$this->_req_do($cmd, 1, 1))
                        return $this->result;
                return true;
        }

        function reverseCard($parameters, $card, $amt)
        {
                return $this->voidActivation($parameters, $card, $amt);
        }

        // copied, not tested
        function deactivateCard($card_id, $msg_id = null)
        {
                //send email with information that card must be deactivated
                $this->load->library('email');
                $this->email->from('do-not-reply@wgiftcard.com', 'TW Card Management');
                $this->email->to('nchammas@gmail.com, codesjones@gmail.com');
                //$this->email->cc('nchammas@gmail.com, codesjones@gmail.com');

                //check if test server
                //CLUSTERFIX
                $test_server = (trim(file_get_contents("/var/tw/cluster")) == 'test') ? ' (Test Server)' : null;
                $this->email->subject('Card management necessary ' . $test_server);
                $message = 'Card ID: ' . $card_id . ' needs to be deactivated.  The SVP is Accent.';
                $this->email->message($message);
                $this->email->send();

                if (!is_null($msg_id)) {
                                $this->load->library('msg_library');
                                //create message event log
                                $info = 'Card deactivation email sent for card id: ' . $card_id . '.';
                                $this->msg_library->event_timestamp($msg_id, MSG_EVENT_TYPE_CARD_DEACTIVATION_EMAIL_SENT, null, $info);
                        }
        }

        function workingKey($parameters)
        {
                $card = array();
                if (!$this->_preamble($parameters, $card))
                        return $this->result;
                $cmd = 'Assign Merchant Working Key';
                if (!$this->_req_header(2010))
                        return $this->result;
                if (!$this->_pack_field_09())
                        return $this->result;
                if (!$this->_pack_field_10())
                        return $this->result;
                if (!$this->_pack_field_12())
                        return $this->result;
                if (!$this->_pack_field_13())
                        return $this->result;
                if (!$this->_pack_field_42())
                        return $this->result;
                if (!$this->_pack_field_63())
                        return $this->result;
                if (!$this->_pack_field_EA())
                        return $this->result;
                if (!$this->_pack_field_F3())
                        return $this->result;
                if (!$this->_pack_field_15())
                        return $this->result;
                if (!$this->_pack_field_53())
                        return $this->result;
                if (!$this->_pack_field_44())
                        return $this->result;
                if (!$this->_req_do($cmd, 1, 0))
                        return $this->result;
                return true;
        }

        function voidReload($parameters, $card, $amt)
        {
                if (!$this->_preamble($parameters, $card))
                        return $this->result;
                $cmd = "Void Of Reload/Refund";
                if (!$this->_req_header(2801))
                        return $this->result;
                if (!$this->_pack_field_04($amt))
                        return $this->result;
                if (!$this->_pack_field_09())
                        return $this->result;
                if (!$this->_pack_field_10())
                        return $this->result;
                if (!$this->_pack_field_12())
                        return $this->result;
                if (!$this->_pack_field_13())
                        return $this->result;
                if (!$this->_pack_card($card))
                        return $this->result;
                if (!$this->_pack_field_F3())
                        return $this->result;
                if (!$this->_pack_field_42())
                        return $this->result;
                if (!$this->_pack_field_EA())
                        return $this->result;
                if (!$this->_pack_field_15())
                        return $this->result;
                if (!$this->_pack_field_53())
                        return $this->result;
                if (!$this->_pack_field_C0())
                        return $this->result;
                if (!$this->_pack_field_44())
                        return $this->result;
                if (!$this->_req_do($cmd, 1, 1))
                        return $this->result;
                return true;
        }

        function reverseInc($parameters, $card, $amt)
        {
                return $this->voidReload($parameters, $card, $amt);
        }

        function test_call()
        {
                echo __FUNCTION__ . "\n";
        }

        // Agg17 testing
        // 2107 Virtual Card Activation with SCV and Provisioning
        // 2108 (EAN) Virtual Card Activation with Provisioning
        // 2301 Reload Merchant Specific
        // 2495 Mobile Wallet Provisioning
        // 2496 Mobile Wallet Remove Provisioning
        // 2202 Redeem No NSF (should already be certified to corresponding void 2800)
        // 2408 Inquire Multi-Lock
        // 2208 Redeem Unlock
        // 2808 Void Redeem Multi Lock

        function t_2107($parameters, &$card, $amt)
        {
                $tcode = 2107;
                $cmd = $tcode . " SCV Virtual Activation";
                if (!$this->_preamble($parameters, $card))
                        return $this->result;
                if (!$this->_req_header($tcode))
                        return $this->result;

                // required
                if (!$this->_pack_field_12())
                        return $this->result;
                if (!$this->_pack_field_13())
                        return $this->result;
                if (!$this->_pack_field_42())
                        return $this->result;
                if (!$this->_pack_field_EA())
                        return $this->result;
                if (!$this->_pack_field_F2())
                        return $this->result;

                // suggested
                if (!$this->_pack_field_15())
                        return $this->result;
                if (!$this->_pack_field_53())
                        return $this->result;

                // optional
                if (!$this->_pack_field_04($amt))
                        return $this->result;
                if (!$this->_pack_field_09())
                        return $this->result;
                if (!$this->_pack_field_10())
                        return $this->result;
                if (!$this->_pack_field_44())
                        return $this->result;
                if (!$this->_pack_field_C0())
                        return $this->result;

                if (!$this->_req_do($cmd, 1, 1))
                        return $this->result;
                if (!isset($this->response_field["70"])) {
                        $this->_set_garble("No card number");
                        return $this->result;
                }
                $card['account_number'] = $this->response_field["70"];
                $card['reference_number'] = $card['pin'] = $this->response_field["32"];
                if ($this->parameters["cardextra"]) {
                        $card["card_extra"] = array();
                        $card["card_extra"]["track_ii"] = $this->response_field["35"];
                        $card["card_extra"]["txn_auth_code"] = $this->response_field["38"];
                        $card["card_extra"]["txn_resp_code"] = $this->response_field["39"];
                        $card["card_extra"]["txn_req_code"] = (string)$tcode;
                }
                return true;
        }

        function t_2108($parameters, &$card, $amt)
        {
                $tcode = 2108;
                $cmd = $tcode . " EAN Virtual Activation";
                if (!$this->_preamble($parameters, $card))
                        return $this->result;
                if (!$this->_req_header($tcode))
                        return $this->result;

                // required
                if (!$this->_pack_field_12())
                        return $this->result;
                if (!$this->_pack_field_13())
                        return $this->result;
                if (!$this->_pack_field_42())
                        return $this->result;
                if (!$this->_pack_field_EA())
                        return $this->result;
                if (!$this->_pack_field_F2())
                        return $this->result;

                // suggested
                if (!$this->_pack_field_15())
                        return $this->result;
                if (!$this->_pack_field_53())
                        return $this->result;

                // optional
                if (!$this->_pack_field_04($amt))
                        return $this->result;
                if (!$this->_pack_field_09())
                        return $this->result;
                if (!$this->_pack_field_10())
                        return $this->result;
                if (!$this->_pack_field_44())
                        return $this->result;
                if (!$this->_pack_field_C0())
                        return $this->result;
                if (!$this->_pack_field_F3())
                        return $this->result;

                if (!$this->_req_do($cmd, 1, 1))
                        return $this->result;
                if (!isset($this->response_field["70"])) {
                        $this->_set_garble("No card number");
                        return $this->result;
                }
                $card['account_number'] = $this->response_field["70"];
                if (isset($this->response_field["34"])) {
                        // decrypt EAN if present
                        $ean = $this->response_field["34"];
                        $ean = trim($ean);
                        if (!empty($ean)) {
                                $cmd = "firstdata_crypt decrypt_ean " . $this->parameters['mwk'] . " " . $ean;
                                exec($cmd, $outraw, $status);
                                $output = join("\n", $outraw);
                                if ($status != 0) {
                                        $msg = "EAN decryption failed ($cmd): " . $output;
                                        $this->result['errmsg'] = $msg;
                                        $this->result['errnum'] = SVP_ERRNUM_INTERNAL;
                                        return $this->result;
                                }
                                $card['reference_number'] = $card['pin'] = trim($output);
                                //logInfo('Activated with First Data, PIN: '.$card['reference_number']);
                        }
                }
                if ($this->parameters["cardextra"]) {
                        $card["card_extra"] = array();
                        $card["card_extra"]["track_ii"] = $this->response_field["35"];
                        $card["card_extra"]["txn_auth_code"] = $this->response_field["38"];
                        $card["card_extra"]["txn_resp_code"] = $this->response_field["39"];
                        $card["card_extra"]["txn_req_code"] = (string)$tcode;
                }
                return true;
        }

        function t_2301($parameters, $card, $amt)
        {
                $tcode = 2301;
                $cmd = $tcode . " Reload Merchant Specific";
                if (!$this->_preamble($parameters, $card))
                        return $this->result;
                if (!$this->_req_header($tcode))
                        return $this->result;

                // required
                if (!$this->_pack_field_04($amt))
                        return $this->result;
                if (!$this->_pack_field_12())
                        return $this->result;
                if (!$this->_pack_field_13())
                        return $this->result;
                if (!$this->_pack_card($card))
                        return $this->result;
                if (!$this->_pack_field_42())
                        return $this->result;
                if (!$this->_pack_field_EA())
                        return $this->result;

                // suggested
                if (!$this->_pack_field_15())
                        return $this->result;
                if (!$this->_pack_field_53())
                        return $this->result;
                if (!$this->_pack_field_C0())
                        return $this->result;

                // optional
                if (!$this->_pack_field_09())
                        return $this->result;
                if (!$this->_pack_field_10())
                        return $this->result;
                if (!$this->_pack_field_44())
                        return $this->result;
                if (!$this->_pack_field_F3())
                        return $this->result;

                if (!$this->_req_do($cmd, 1, 1))
                        return $this->result;
                return true;
        }

        /* Note:
         * Using source_code=31 (field EA, no EAN) with transaction type 2495 will produce error 12, "Invalid transaction format..."
         * Use force_pin="00000000" (SCV, field 32) to suppress this error.
         */
        function provisionCard($parameters, &$card, &$track_ii)
        {
                $tcode = 2495;
                $cmd = $tcode . " Mobile Wallet Provisioning";
                if (!$this->_preamble($parameters, $card))
                        return $this->result;
                if (!$this->_req_header($tcode))
                        return $this->result;

                // required
                if (!$this->_pack_field_12())
                        return $this->result;
                if (!$this->_pack_field_13())
                        return $this->result;
                if (!$this->_pack_field_42())
                        return $this->result;
                if (!$this->_pack_card($card))
                        return $this->result;

                // special case - ensure pin present (EAN/SCV), as per discussion w/Paul Dattilo 7 June 2016
                if (!isset($this->field_table[0x32]) && !isset($this->field_table[0x34])) {
                        $this->result['errmsg'] = 'Provisioning must use a pin';
                        $this->result['errnum'] = SVP_ERRNUM_GARBLE;
                        return $this->result;
                }

                // suggested
                if (!$this->_pack_field_15())
                        return $this->result;
                if (!$this->_pack_field_53())
                        return $this->result;

                // optional
                if (!$this->_pack_field_09())
                        return $this->result;
                if (!$this->_pack_field_10())
                        return $this->result;
                if (!$this->_pack_field_44())
                        return $this->result;
                if (!$this->_pack_field_C0())
                        return $this->result;
                if (!$this->_pack_field_EA())
                        return $this->result;
                if (!$this->_pack_field_F3())
                        return $this->result;

                if (!$this->_req_do_simple_client_id($cmd, 1, 1))
                        return $this->result;

                if ($this->response_field["39"] === "00" && !empty($this->response_field["35"])) {
                                $track_ii = $this->response_field["35"];
                                if ($this->parameters["cardextra"]) {
                                        $card["card_extra"] = array();
                                        $card["card_extra"]["txn_auth_code"] = $this->response_field["38"];
                                        $card["card_extra"]["txn_resp_code"] = $this->response_field["39"];
                                        $card["card_extra"]["txn_req_code"] = (string)$tcode;
                                }
                                return true;
                        } else {
                                $this->result['errnum'] = $this->response_field["39"];
                                $this->result['errmsg'] = $this->_svdot_errmsg($this->response_field["39"]);
                                return $this->result;
                        }
        }

        function unprovisionCard($parameters, $card)
        {
                $tcode = 2496;
                $cmd = $tcode . " Mobile Wallet Remove Provisioning";
                if (!$this->_preamble($parameters, $card))
                        return $this->result;
                if (!$this->_req_header($tcode))
                        return $this->result;

                // required
                if (!$this->_pack_field_12())
                        return $this->result;
                if (!$this->_pack_field_13())
                        return $this->result;
                if (!$this->_pack_field_42())
                        return $this->result;
                if (!$this->_pack_card($card))
                        return $this->result;

                // suggested
                if (!$this->_pack_field_15())
                        return $this->result;
                if (!$this->_pack_field_53())
                        return $this->result;

                // optional
                if (!$this->_pack_field_09())
                        return $this->result;
                if (!$this->_pack_field_10())
                        return $this->result;
                if (!$this->_pack_field_44())
                        return $this->result;
                if (!$this->_pack_field_C0())
                        return $this->result;
                if (!$this->_pack_field_EA())
                        return $this->result;
                if (!$this->_pack_field_F3())
                        return $this->result;

                // 2496 response does not include F7, can't check it
                //if (!$this->_req_do_simple_client_id($cmd, 1, 1))
                if (!$this->_req_do($cmd, 1, 0))
                        return $this->result;

                if ($this->response_field["39"] === "00") {
                                return true;
                        } else {
                                $this->result['errnum'] = $this->response_field["39"];
                                $this->result['errmsg'] = $this->_svdot_errmsg($this->response_field["39"]);
                                return $this->result;
                        }
        }

        // returns amount taken by redemption
        function t_2202($parameters, &$card, &$amt)
        {
                $tcode = 2202;
                $cmd = $tcode . " Redemption, NSF";
                if (!$this->_preamble($parameters, $card))
                        return $this->result;
                if (!$this->_req_header($tcode))
                        return $this->result;

                // required
                if (!$this->_pack_field_04($amt))
                        return $this->result;
                if (!$this->_pack_field_12())
                        return $this->result;
                if (!$this->_pack_field_13())
                        return $this->result;
                if (!$this->_pack_card($card))
                        return $this->result;
                if (!$this->_pack_field_42())
                        return $this->result;
                if (!$this->_pack_field_EA())
                        return $this->result;

                // suggested
                if (!$this->_pack_field_15())
                        return $this->result;
                if (!$this->_pack_field_53())
                        return $this->result;
                if (!$this->_pack_field_C0())
                        return $this->result;

                // optional
                if (!$this->_pack_field_09())
                        return $this->result;
                if (!$this->_pack_field_10())
                        return $this->result;
                if (!$this->_pack_field_44())
                        return $this->result;
                if (!$this->_pack_field_F3())
                        return $this->result;

                if (!$this->_req_do($cmd, 1, 1))
                        return $this->result;
                $previous_balance = $this->response_field["75"];
                $new_balance = $this->response_field["76"];
                $taken = $previous_balance - $new_balance;
                $amt = $taken / 100.0;
                if ($this->parameters["cardextra"]) {
                        $card["card_extra"] = array();
                        $card["card_extra"]["txn_auth_code"] = $this->response_field["38"];
                        $card["card_extra"]["txn_resp_code"] = $this->response_field["39"];
                        $card["balance"] = $new_balance;
                        $card["card_extra"]["txn_req_code"] = (string)$tcode;
                }
                return true;
        }

        // returns $lockid to be passed to 2808
        function t_2408($parameters, &$card, $amt, &$lockid)
        {
                $tcode = 2408;
                $cmd = $tcode . " Inquire Current Balance w/Multi-Lock";
                if (!$this->_preamble($parameters, $card))
                        return $this->result;
                if (!$this->_req_header($tcode))
                        return $this->result;

                // required
                if (!$this->_pack_field_12())
                        return $this->result;
                if (!$this->_pack_field_13())
                        return $this->result;
                if (!$this->_pack_card($card))
                        return $this->result;
                if (!$this->_pack_field_42())
                        return $this->result;
                if (!$this->_pack_field_EA())
                        return $this->result;

                // suggested
                if (!$this->_pack_field_04($amt))
                        return $this->result;
                if (!$this->_pack_field_15())
                        return $this->result;
                if (!$this->_pack_field_53())
                        return $this->result;
                if (!$this->_pack_field_C0())
                        return $this->result;

                // optional
                if (!$this->_pack_field_09())
                        return $this->result;
                if (!$this->_pack_field_10())
                        return $this->result;
                if (!$this->_pack_field_44())
                        return $this->result;
                if (!$this->_pack_field_F3())
                        return $this->result;

                if (!$this->_req_do($cmd, 1, 1))
                        return $this->result;
                $lockid = $this->response_field["79"];
                if ($this->parameters["cardextra"]) {
                        $card["card_extra"] = array();
                        $card["card_extra"]["txn_auth_code"] = $this->response_field["38"];
                        $card["card_extra"]["txn_resp_code"] = $this->response_field["39"];
                        $card["card_extra"]["txn_req_code"] = (string)$tcode;
                }
                return true;
        }

        function t_2208($parameters, &$card, $amt, $lockid)
        {
                $tcode = 2208;
                $cmd = $tcode . " Redemption Unlock w/Multi-Lock";
                if (!$this->_preamble($parameters, $card))
                        return $this->result;
                if (!$this->_req_header($tcode))
                        return $this->result;

                // required
                if (!$this->_pack_field_04($amt))
                        return $this->result;
                if (!$this->_pack_field_12())
                        return $this->result;
                if (!$this->_pack_field_13())
                        return $this->result;
                if (!$this->_pack_card($card))
                        return $this->result;
                if (!$this->_pack_field_42())
                        return $this->result;
                if (!$this->_pack_field_79($lockid))
                        return $this->result;
                if (!$this->_pack_field_EA())
                        return $this->result;

                // suggested
                if (!$this->_pack_field_15())
                        return $this->result;
                if (!$this->_pack_field_53())
                        return $this->result;
                if (!$this->_pack_field_C0())
                        return $this->result;

                // optional
                if (!$this->_pack_field_09())
                        return $this->result;
                if (!$this->_pack_field_10())
                        return $this->result;
                if (!$this->_pack_field_44())
                        return $this->result;
                if (!$this->_pack_field_F3())
                        return $this->result;

                if (!$this->_req_do($cmd, 1, 1))
                        return $this->result;
                if ($this->parameters["cardextra"]) {
                        $card["card_extra"] = array();
                        $card["card_extra"]["txn_auth_code"] = $this->response_field["38"];
                        $card["card_extra"]["txn_resp_code"] = $this->response_field["39"];
                        $card["card_extra"]["txn_req_code"] = (string)$tcode;
                }
                return true;
        }

        function t_2808($parameters, &$card, $amt, $lockid)
        {
                $tcode = 2808;
                $cmd = $tcode . " Void Of Redemption w/Multi-Lock";
                if (!$this->_preamble($parameters, $card))
                        return $this->result;
                if (!$this->_req_header($tcode))
                        return $this->result;

                // required
                if (!$this->_pack_field_04($amt))
                        return $this->result;
                if (!$this->_pack_field_12())
                        return $this->result;
                if (!$this->_pack_field_13())
                        return $this->result;
                if (!$this->_pack_card($card))
                        return $this->result;
                if (!$this->_pack_field_42())
                        return $this->result;
                if (!$this->_pack_field_79($lockid))
                        return $this->result;
                if (!$this->_pack_field_EA())
                        return $this->result;

                // suggested
                if (!$this->_pack_field_15())
                        return $this->result;
                if (!$this->_pack_field_53())
                        return $this->result;
                if (!$this->_pack_field_C0())
                        return $this->result;

                // optional
                if (!$this->_pack_field_09())
                        return $this->result;
                if (!$this->_pack_field_10())
                        return $this->result;
                if (!$this->_pack_field_44())
                        return $this->result;
                if (!$this->_pack_field_F3())
                        return $this->result;

                if (!$this->_req_do($cmd, 1, 1))
                        return $this->result;
                if ($this->parameters["cardextra"]) {
                        $card["card_extra"] = array();
                        $card["card_extra"]["txn_auth_code"] = $this->response_field["38"];
                        $card["card_extra"]["txn_resp_code"] = $this->response_field["39"];
                        $card["card_extra"]["txn_req_code"] = (string)$tcode;
                }
                return true;
        }

        // ALL FUNCTIONS BELOW THIS LINE HAVE NOT YET BEEN TESTED/CERTIFIED
        // as per request by Paul Dattilo, Cindy Windomaker via email 14 Apr 2016:
        // 2202 Redeem No NSF (should already be certified to corresponding void 2800)
        // 2408 Inquire Multi-Lock (corresponding void is 2808)
        // 2808 Redeem Unlock and Void

        // as per request by Paul Dattilo via email 24 Dec 2015:
        // Near Term
        // 2107 Virtual Card Activation with SCV and Provisioning
        // 2108 (EAN) Virtual Card Activation with Provisioning
        // 2121 NRTM Card Registration
        // 2301 Reload Merchant Specific
        // 2495 Mobile Wallet Provisioning
        // 2496 Mobile Wallet Remove Provisioning
        // 2821 NRTM Card De-Registration
        // 7400 Application Check

        function t_2121($parameters, &$card)
        {
                $tcode = 2121;
                $cmd = $tcode . " NRTM Card Registration";
                if (!$this->_preamble($parameters, $card))
                        return $this->result;
                if (!$this->_req_header($tcode))
                        return $this->result;

                // required
                if (!$this->_pack_field_12())
                        return $this->result;
                if (!$this->_pack_field_13())
                        return $this->result;
                if (!$this->_pack_field_70($card)) // TODO maybe use $this->_pack_card($card) instead?
                        return $this->result;
                if (!$this->_pack_field_42())
                        return $this->result;
                if (!$this->_pack_field_EA())
                        return $this->result;

                // suggested
                if (!$this->_pack_field_15())
                        return $this->result;
                if (!$this->_pack_field_53())
                        return $this->result;

                // optional
                if (!$this->_pack_field_09())
                        return $this->result;
                if (!$this->_pack_field_10())
                        return $this->result;
                if (!$this->_pack_field_44())
                        return $this->result;

                if (!$this->_req_do($cmd, 1, 1))
                        return $this->result;
                if ($this->parameters["cardextra"]) {
                        $card["card_extra"] = array();
                        $card["card_extra"]["txn_auth_code"] = $this->response_field["38"];
                        $card["card_extra"]["txn_resp_code"] = $this->response_field["39"];
                        $card["card_extra"]["txn_req_code"] = (string)$tcode;
                }
                return true;
        }

        function t_2821($parameters, &$card)
        {
                $tcode = 2821;
                $cmd = $tcode . " NRTM Card De-Registration";
                if (!$this->_preamble($parameters, $card))
                        return $this->result;
                if (!$this->_req_header($tcode))
                        return $this->result;

                // required
                if (!$this->_pack_field_12())
                        return $this->result;
                if (!$this->_pack_field_13())
                        return $this->result;
                if (!$this->_pack_field_42())
                        return $this->result;
                if (!$this->_pack_field_70($card)) // TODO maybe use $this->_pack_card($card) instead?
                        return $this->result;
                if (!$this->_pack_field_EA())
                        return $this->result;

                // suggested
                if (!$this->_pack_field_15())
                        return $this->result;
                if (!$this->_pack_field_53())
                        return $this->result;

                // optional
                if (!$this->_pack_field_09())
                        return $this->result;
                if (!$this->_pack_field_10())
                        return $this->result;
                if (!$this->_pack_field_44())
                        return $this->result;

                if (!$this->_req_do($cmd, 1, 1))
                        return $this->result;
                if ($this->parameters["cardextra"]) {
                        $card["card_extra"] = array();
                        $card["card_extra"]["txn_auth_code"] = $this->response_field["38"];
                        $card["card_extra"]["txn_resp_code"] = $this->response_field["39"];
                        $card["card_extra"]["txn_req_code"] = (string)$tcode;
                        //logInfo("card record with extra data is ".print_r($card,TRUE));
                }
                return true;
        }

        function t_7400($parameters)
        {
                $tcode = 7400;
                $cmd = $tcode . " CLGC Application Check";
                $card = array();
                if (!$this->_preamble($parameters, $card))
                        return $this->result;
                if (!$this->_req_header($tcode))
                        return $this->result;

                // required
                if (!$this->_pack_field_12())
                        return $this->result;
                if (!$this->_pack_field_13())
                        return $this->result;
                if (!$this->_pack_field_42())
                        return $this->result;
                if (!$this->_pack_field_44())
                        return $this->result;

                // optional
                if (!$this->_pack_field_15())
                        return $this->result;
                if (!$this->_pack_field_EA())
                        return $this->result;

                if (!$this->_req_do($cmd, 1, 0))
                        return $this->result;
                return true;
        }

        // as per request by Paul Dattilo via email 24 Dec 2015:
        // Medium Term
        // 2100 Activate card Without EAN
        // 2103 Enable Physical Card
        // 2150 Disable Internet Usage
        // 2151 Merchant Disable EAN/SCV
        // 2152 Merchant Enable EAN/SCV
        // 2201 Redemption Unlock
        // 2204 Product Redemption, No NSF
        // 2208 Redemption Unlock w/Multi-Lock
        // 2401 Inquire Current Balance w/Lock
        // 2402 Inquire Current Balance w/Partial Lock
        // 2408 Inquire Current Balance w/Multi-Lock
        // 2410 Inquire Transaction History
        // 2413 Inquire Activation/Reload Transaction History
        // 2464 Set Reference
        // 2700 Refund
        // 2808 Void of Redemption w/Multi-Lock

        // as per request by Paul Dattilo via email 19 Sep 2017:
        // 2413, 2464, 2503
        // 2403

        function t_2413($parameters, $vdata, &$history)
        {
                $parameters['history_code'] = 2413;
                return $this->getHistory($parameters, $vdata, $history);
        }

        function t_2464($parameters, &$card, $refnum)
        {
                $tcode = 2464;
                $cmd = $tcode . " Set Reference";
                if (!$this->_preamble($parameters, $card))
                        return $this->result;
                if (!$this->_req_header($tcode))
                        return $this->result;

                // required
                if (!$this->_pack_field_08($refnum))
                        return $this->result;
                if (!$this->_pack_field_12())
                        return $this->result;
                if (!$this->_pack_field_13())
                        return $this->result;
                if (!$this->_pack_field_42())
                        return $this->result;
                if (!$this->_pack_field_44())
                        return $this->result;
                if (!$this->_pack_card($card))
                        return $this->result;
                if (!$this->_pack_field_EA())
                        return $this->result;

                // suggested
                if (!$this->_pack_field_15())
                        return $this->result;
                if (!$this->_pack_field_53())
                        return $this->result;

                // optional
                if (!$this->_pack_field_09())
                        return $this->result;
                if (!$this->_pack_field_10())
                        return $this->result;
                if (!$this->_pack_field_F3())
                        return $this->result;

                if (!$this->_req_do($cmd, 1, 1))
                        return $this->result;
                if ($this->parameters["cardextra"]) {
                        $card["card_extra"] = array();
                        $card["card_extra"]["txn_auth_code"] = $this->response_field["38"];
                        $card["card_extra"]["txn_resp_code"] = $this->response_field["39"];
                        $card["card_extra"]["txn_req_code"] = (string)$tcode;
                        //logInfo("card record with extra data is ".print_r($card,TRUE));
                }
                return true;
        }

        function t_2503($parameters, &$card, &$amt)
        {
                $tcode = 2503;
                $cmd = $tcode . " Set Fraud/Watch/Restricted Status";
                if (!$this->_preamble($parameters, $card))
                        return $this->result;
                if (!$this->_req_header($tcode))
                        return $this->result;

                // required
                if (!$this->_pack_field_12()) // Local Transaction Time
                        return $this->result;
                if (!$this->_pack_field_13()) // Local Transaction Date
                        return $this->result;
                if (!$this->_pack_card($card))
                        return $this->result;
                if (!$this->_pack_field_42()) // Merchant & Terminal ID
                        return $this->result;
                if (!$this->_pack_field_EA()) // Source Code
                        return $this->result;
                if (!$this->_pack_field_F4()) // Fraud/Watch/Restricted Status
                        return $this->result;

                // suggested
                if (!$this->_pack_field_15())
                        return $this->result;
                if (!$this->_pack_field_53())
                        return $this->result;

                // optional
                if (!$this->_pack_field_09()) // User 1
                        return $this->result;
                if (!$this->_pack_field_10()) // User 2
                        return $this->result;
                if (!$this->_pack_field_44()) // Alternate Merchant Number
                        return $this->result;
                // 55 Transaction Postal Code
                // 62 Clerk ID
                // AA Account Origin
                // AC Foreign Access Code
                if (!$this->_pack_field_F3()) // Merchant Key ID
                        return $this->result;
                // 7F Echo Back
                // 0A Device ID
                // 0B IP Address
                // 0C Originating IP Address
                // 0D System ID

                if (!$this->_req_do($cmd, 1, 1))
                        return $this->result;

                // RESPONSE required
                // 11 System Trace Number
                // 39 Response Code
                // 75 Previous Balance
                // 76 New Balance
                // 78 Lock Amount
                // 70 Embossed Card Number
                // 42 Merchant & Terminal ID
                // 44 Alternate Merchant Number
                // F6 Original Transaction Request
                // B0 Card Class
                // C0 Local Currency
                // F4 Fraud/Watch/Restricted Status

                // RESPONSE optional
                // 08 Reference Number
                // 15 Terminal Transaction Number
                // 7F Echo Back
                // E0 Absolute Expiration Date
                // F2 Promotion Code

                $previous_balance = $this->response_field["75"];
                $new_balance = $this->response_field["76"];
                $taken = $previous_balance - $new_balance;
                $amt = $taken / 100.0;
                if ($this->parameters["cardextra"]) {
                        $card["card_extra"] = array();
                        //$card["card_extra"]["txn_auth_code"] = $this->response_field["38"];
                        $card["card_extra"]["txn_resp_code"] = $this->response_field["39"];
                        $card["balance"] = $new_balance;
                        $card["card_extra"]["txn_req_code"] = (string)$tcode;
                }
                return true;
        }

        function cardStatus($parameters, $vdata, &$amt, &$status)
        {
                return $this->t_2403($parameters, $vdata, $amt, $status);
        }

        function t_2403($parameters, $vdata, &$amt, &$cardstatus)
        {
                $tcode = 2403;
                $cmd = $tcode . " Card Status Notification";

                $this->vdata = $vdata;
                $card = $this->vdata['recipient']['card'];

                if (!$this->_preamble($parameters, $card))
                        return $this->result;
                if (!$this->_req_header($tcode))
                        return $this->result;

                // required
                if (!$this->_pack_field_12())
                        return $this->result;
                if (!$this->_pack_field_13())
                        return $this->result;
                if (!$this->_pack_card($card))
                        return $this->result;
                if (!$this->_pack_field_42())
                        return $this->result;
                if (!$this->_pack_field_EA())
                        return $this->result;

                // suggested
                if (!$this->_pack_field_15())
                        return $this->result;
                if (!$this->_pack_field_53())
                        return $this->result;

                // optional
                if (!$this->_pack_field_09())
                        return $this->result;
                if (!$this->_pack_field_10())
                        return $this->result;
                if (!$this->_pack_field_44())
                        return $this->result;
                if (!$this->_pack_field_F3())
                        return $this->result;
                // Passing field CO can produce error status 55 - "Invalid currency. The provided currency is invalid."
                // Email from Ken.Jennings@firstdata.com on 9 Oct 2017 explains why:
                //
                // 1) The card examined is not capable of currency conversion.
                //    If the currency code field is sent in a request then it must match the card’s base currency.
                // 2) The GYFT MID is configured for International use (good).
                //    However, the base currency on the card examined is NOT part of that configuration.
                // 3) Currencies associated with the particular corporation are maintained in CORPORATION_CURRENCY table.
                //    So, you need that setup configuration updated to include the base currency of this card.
                //
                // We currently prevent this error by never passing C0.
                // An alternative would be to uncomment the following lines and set currency_code=notset when
                // base currency has not been configured as per Ken's explanation.
                //
                //if ($this->parameters['currency_code'] !== "notset")
                //      if (!$this->_pack_field_C0())
                //              return $this->result;

                if (!$this->_req_do($cmd, 1, 0))
                        return $this->result;

                if (!isset($this->response_field["76"])) {
                        $this->_set_garble("No amount (field 76)");
                        return $this->result;
                }
                $amt = $this->response_field["76"] / 100.0;

                if (!isset($this->response_field["E2"])) {
                        $this->_set_garble("No cardstatus (field E2)");
                        return $this->result;
                }
                $cardstatus = $this->response_field["E2"];

                return true;
        }

        function isValid($parameters, $vdata, &$amt)
        {
                //1.  Figure out which isValid function we should use.
                if (!empty($parameters['is_valid_use']) && trim($parameters['is_valid_use']) === '2403')
                        return $this->_isValid2403($parameters, $vdata, $amt);

                //2. Just use the default balance method
                return $this->_isValid2400($parameters, $vdata, $amt);
        }

        private function _isValid2403($parameters, $vdata, &$amt)
        {
                logInfo('Using: isValid2403');
                //1. Run Card Status
                $result = $this->cardStatus($parameters, $vdata, $amt, $status);
                if (is_array($result))
                        return $result;
                logInfo("isValid2403: Response = ($status)");
                switch ($status) {
                        case '0': //Account Inactive
                                return VALID_INACTIVE_CARD;
                                break;
                        case '2': //Account Active
                                return VALID_ACTIVE_CARD;
                                break;
                        case '3': //Account Closed
                        case '4': //Card Lost/Stolen
                        case '5': //Card Replaced
                        case '6': //Account Frozen
                        case '7': //Card Missing
                        case '8': //Card Dead
                        case '9': //Account Alias
                        case '10': //On Hold
                        case '11': //Void Lock
                        case '12': //On HOld Activation
                        case '13': //Conversion Destroyed
                        case '14': //Account Dormant
                        case '15': //Account Enabled
                        case '16': //Watch
                        case '17': //Fraud
                        default:
                                return NOT_VALID_CARD;
                                break;
                }
                //Cannot really get here, but just put here for completeness
                return NOT_VALID_CARD;
        }

        private function _isValid2400($parameters, $vdata, &$amt)
        {
                logInfo('Using: isValid2400');
                //1. Run balance
                $result = $this->getBalance($parameters, $vdata, $amt);
                //2.  We actually want an error case for FD to help us determine the state of the card
                if (is_array($result)) {
                                if (empty($result['svperrnum'])) {
                                                logError('First Data:  svperrnum is missing returning with NOT_VALID_CARD');
                                                return NOT_VALID_CARD; //Catchall
                                        }

                                switch ($result['svperrnum']) {
                                        case '04': //Inactive account. The account has not been activated by an approved location.
                                                return VALID_INACTIVE_CARD;
                                                break;
                                        case '03': //Unknown account. The account could not be located in the account table.
                                                return NOT_VALID_CARD;
                                                break;
                                        default:
                                                logError('First Data:  isValid error | ' . print_r($result, true));
                                                return $result;
                                                break;
                                }
                        }
                //3.  We got a balance so return
                return VALID_ACTIVE_CARD;
        }

        // added by Paul Gardner - 14 Nov 2018
        function t_2101($parameters, &$card, $amt)
        {
                $tcode = 2101;
                $cmd = $tcode . " Activate Virtual Card w/SCV";
                if (!$this->_preamble($parameters, $card))
                        return $this->result;
                if (!$this->_req_header($tcode))
                        return $this->result;

                // required
                if (!$this->_pack_field_12())
                        return $this->result;
                if (!$this->_pack_field_13())
                        return $this->result;
                if (!$this->_pack_field_42())
                        return $this->result;
                if (!$this->_pack_field_EA())
                        return $this->result;
                if (!$this->_pack_field_F2())
                        return $this->result;

                // suggested
                if (!$this->_pack_field_15())
                        return $this->result;
                if (!$this->_pack_field_53())
                        return $this->result;

                // optional
                if (!$this->_pack_field_04($amt))
                        return $this->result;
                if (!$this->_pack_field_09())
                        return $this->result;
                if (!$this->_pack_field_10())
                        return $this->result;
                if (!$this->_pack_field_44())
                        return $this->result;
                if (!$this->_pack_field_C0())
                        return $this->result;

                if (!$this->_req_do($cmd, 1, 1))
                        return $this->result;
                if (!isset($this->response_field["70"])) {
                        $this->_set_garble("No card number");
                        return $this->result;
                }
                $card['account_number'] = $this->response_field["70"];
                $card['reference_number'] = $card['pin'] = $this->response_field["32"];
                if ($this->parameters["cardextra"]) {
                        $card["card_extra"] = array();
                        if (!empty($this->response_field["35"])) {
                                $card["card_extra"]["track_ii"] = $this->response_field["35"];
                        }
                        if (!empty($this->response_field["38"])) {
                                $card["card_extra"]["txn_auth_code"] = $this->response_field["38"];
                        }
                        if (!empty($this->response_field["39"])) {
                                $card["card_extra"]["txn_resp_code"] = $this->response_field["39"];
                        }
                        $card["card_extra"]["txn_req_code"] = (string)$tcode;
                }
                return true;
        }

        // converse of 2016 Unfreeze Active Card
        function t_2003($parameters, &$card, &$amt)
        {
                $tcode = 2003;
                $cmd = $tcode . " Freeze Active Card";
                if (!$this->_preamble($parameters, $card))
                        return $this->result;
                if (!$this->_req_header($tcode))
                        return $this->result;

                // required
                if (!$this->_pack_field_12()) // Local Transaction Time
                        return $this->result;
                if (!$this->_pack_field_13()) // Local Transaction Date
                        return $this->result;
                if (!$this->_pack_field_42()) // Merchant & Terminal ID
                        return $this->result;
                if (!$this->_pack_card($card))
                        return $this->result;
                if (!$this->_pack_field_EA()) // Source Code
                        return $this->result;

                // optional
                if (!$this->_pack_field_09()) // User 1
                        return $this->result;
                if (!$this->_pack_field_10()) // User 2
                        return $this->result;
                if (!$this->_pack_field_15()) // 15 Terminal Transaction Number
                        return $this->result;
                if (!$this->_pack_field_44()) // Alternate Merchant Number
                        return $this->result;
                // 53 Post Date
                // 55 Transaction Postal Code
                // 62 Clerk ID
                // 0A Device ID
                // 0B IP Address
                // 0C Originating IP Address
                // 0D System ID
                // 7F Echo Back
                // AA Account Origin
                // AC Foreign Access Code
                if (!$this->_pack_field_F3()) // Merchant Key ID
                        return $this->result;

                if (!$this->_req_do($cmd, 1, 1))
                        return $this->result;

                // RESPONSE required
                // 11 System Trace Number
                // 38 Authorization Code
                // 39 Response Code
                // 42 Merchant & Terminal ID
                // 44 Alternate Merchant Number
                // 70 Embossed Card Number
                // 75 Previous Balance
                // 76 New Balance
                // 78 Lock Amount
                // A0 Expiration Date
                // B0 Card Class
                // C0 Local Currency
                // F6 Original Transaction Request

                // RESPONSE optional
                // 08 Reference Number
                // F2 Promotion Code

                $previous_balance = $this->response_field["75"];
                $new_balance = $this->response_field["76"];
                $taken = $previous_balance - $new_balance;
                $amt = $taken / 100.0;
                if ($this->parameters["cardextra"]) {
                        $card["card_extra"] = array();
                        $card["card_extra"]["txn_auth_code"] = $this->response_field["38"];
                        $card["card_extra"]["txn_resp_code"] = $this->response_field["39"];
                        $card["balance"] = $new_balance;
                        $card["card_extra"]["txn_req_code"] = (string)$tcode;
                }
                return true;
        }

        function t_2004($parameters, &$card, &$amt)
        {
                $tcode = 2004;
                $cmd = $tcode . " Close Account";
                if (!$this->_preamble($parameters, $card))
                        return $this->result;
                if (!$this->_req_header($tcode))
                        return $this->result;

                // required
                if (!$this->_pack_field_12()) // Local Transaction Time
                        return $this->result;
                if (!$this->_pack_field_13()) // Local Transaction Date
                        return $this->result;
                if (!$this->_pack_field_42()) // Merchant & Terminal ID
                        return $this->result;
                if (!$this->_pack_card($card))
                        return $this->result;
                if (!$this->_pack_field_EA()) // Source Code
                        return $this->result;

                // optional
                if (!$this->_pack_field_09()) // User 1
                        return $this->result;
                if (!$this->_pack_field_10()) // User 2
                        return $this->result;
                if (!$this->_pack_field_15()) // 15 Terminal Transaction Number
                        return $this->result;
                if (!$this->_pack_field_44()) // Alternate Merchant Number
                        return $this->result;
                // 53 Post Date
                // 55 Transaction Postal Code
                // 62 Clerk ID
                // 0A Device ID
                // 0B IP Address
                // 0C Originating IP Address
                // 0D System ID
                // 7F Echo Back
                // AA Account Origin
                // AC Foreign Access Code
                if (!$this->_pack_field_F3()) // Merchant Key ID
                        return $this->result;
                if (!$this->_pack_field_FA()) // Remove Balance
                        return $this->result;

                if (!$this->_req_do($cmd, 1, 1))
                        return $this->result;

                // RESPONSE required
                // 11 System Trace Number
                // 38 Authorization Code
                // 39 Response Code
                // 42 Merchant & Terminal ID
                // 44 Alternate Merchant Number
                // 70 Embossed Card Number
                // 75 Previous Balance
                // 76 New Balance
                // 78 Lock Amount
                // A0 Expiration Date
                // B0 Card Class
                // C0 Local Currency
                // F6 Original Transaction Request

                // RESPONSE optional
                // 08 Reference Number
                // F2 Promotion Code

                $previous_balance = $this->response_field["75"];
                $new_balance = $this->response_field["76"];
                $taken = $previous_balance - $new_balance;
                $amt = $taken / 100.0;
                if ($this->parameters["cardextra"]) {
                        $card["card_extra"] = array();
                        $card["card_extra"]["txn_auth_code"] = $this->response_field["38"];
                        $card["card_extra"]["txn_resp_code"] = $this->response_field["39"];
                        $card["balance"] = $new_balance;
                        $card["card_extra"]["txn_req_code"] = (string)$tcode;
                }
                return true;
        }

        function t_2009($parameters, &$card, &$amt)
        {
                $tcode = 2009;
                $cmd = $tcode . " Freeze Inactive Card";
                if (!$this->_preamble($parameters, $card))
                        return $this->result;
                if (!$this->_req_header($tcode))
                        return $this->result;

                // required
                if (!$this->_pack_field_12()) // Local Transaction Time
                        return $this->result;
                if (!$this->_pack_field_13()) // Local Transaction Date
                        return $this->result;
                if (!$this->_pack_field_42()) // Merchant & Terminal ID
                        return $this->result;
                if (!$this->_pack_card($card))
                        return $this->result;
                if (!$this->_pack_field_EA()) // Source Code
                        return $this->result;

                // optional
                if (!$this->_pack_field_09()) // User 1
                        return $this->result;
                if (!$this->_pack_field_10()) // User 2
                        return $this->result;
                if (!$this->_pack_field_15()) // 15 Terminal Transaction Number
                        return $this->result;
                if (!$this->_pack_field_44()) // Alternate Merchant Number
                        return $this->result;
                // 53 Post Date
                // 55 Transaction Postal Code
                // 62 Clerk ID
                // 0A Device ID
                // 0B IP Address
                // 0C Originating IP Address
                // 0D System ID
                // 7F Echo Back
                // AA Account Origin
                // AC Foreign Access Code
                if (!$this->_pack_field_F3()) // Merchant Key ID
                        return $this->result;

                if (!$this->_req_do($cmd, 1, 1))
                        return $this->result;

                // RESPONSE required
                // 11 System Trace Number
                // 38 Authorization Code
                // 39 Response Code
                // 42 Merchant & Terminal ID
                // 44 Alternate Merchant Number
                // 70 Embossed Card Number
                // 75 Previous Balance
                // 76 New Balance
                // 78 Lock Amount
                // A0 Expiration Date
                // B0 Card Class
                // C0 Local Currency
                // F6 Original Transaction Request

                // RESPONSE optional
                // 08 Reference Number
                // F2 Promotion Code

                $previous_balance = $this->response_field["75"];
                $new_balance = $this->response_field["76"];
                $taken = $previous_balance - $new_balance;
                $amt = $taken / 100.0;
                if ($this->parameters["cardextra"]) {
                        $card["card_extra"] = array();
                        $card["card_extra"]["txn_auth_code"] = $this->response_field["38"];
                        $card["card_extra"]["txn_resp_code"] = $this->response_field["39"];
                        $card["balance"] = $new_balance;
                        $card["card_extra"]["txn_req_code"] = (string)$tcode;
                }
                return true;
        }

        function t_2501($parameters, &$card, &$amt)
        {
                $tcode = 2501;
                $cmd = $tcode . " Report Lost/Stolen";
                if (!$this->_preamble($parameters, $card))
                        return $this->result;
                if (!$this->_req_header($tcode))
                        return $this->result;

                // required
                if (!$this->_pack_field_12()) // Local Transaction Time
                        return $this->result;
                if (!$this->_pack_field_13()) // Local Transaction Date
                        return $this->result;
                if (!$this->_pack_field_42()) // Merchant & Terminal ID
                        return $this->result;
                if (!$this->_pack_card($card))
                        return $this->result;
                if (!$this->_pack_field_EA()) // Source Code
                        return $this->result;

                // optional
                if (!$this->_pack_field_09()) // User 1
                        return $this->result;
                if (!$this->_pack_field_10()) // User 2
                        return $this->result;
                if (!$this->_pack_field_15()) // 15 Terminal Transaction Number
                        return $this->result;
                if (!$this->_pack_field_44()) // Alternate Merchant Number
                        return $this->result;
                // 53 Post Date
                // 55 Transaction Postal Code
                // 62 Clerk ID
                // 0A Device ID
                // 0B IP Address
                // 0C Originating IP Address
                // 0D System ID
                // 7F Echo Back
                // AA Account Origin
                // AC Foreign Access Code
                if (!$this->_pack_field_F3()) // Merchant Key ID
                        return $this->result;

                if (!$this->_req_do($cmd, 1, 1))
                        return $this->result;

                // RESPONSE required
                // 11 System Trace Number
                // 38 Authorization Code
                // 39 Response Code
                // 42 Merchant & Terminal ID
                // 44 Alternate Merchant Number
                // 70 Embossed Card Number
                // 75 Previous Balance
                // 76 New Balance
                // 78 Lock Amount
                // A0 Expiration Date
                // B0 Card Class
                // C0 Local Currency
                // F6 Original Transaction Request

                // RESPONSE optional
                // 08 Reference Number
                // F2 Promotion Code

                $previous_balance = $this->response_field["75"];
                $new_balance = $this->response_field["76"];
                $taken = $previous_balance - $new_balance;
                $amt = $taken / 100.0;
                if ($this->parameters["cardextra"]) {
                        $card["card_extra"] = array();
                        $card["card_extra"]["txn_auth_code"] = $this->response_field["38"];
                        $card["card_extra"]["txn_resp_code"] = $this->response_field["39"];
                        $card["balance"] = $new_balance;
                        $card["card_extra"]["txn_req_code"] = (string)$tcode;
                }
                return true;
        }

        function t_2502($parameters, &$card, &$amt)
        {
                $tcode = 2502;
                $cmd = $tcode . " Report Missing";
                if (!$this->_preamble($parameters, $card))
                        return $this->result;
                if (!$this->_req_header($tcode))
                        return $this->result;

                // required
                if (!$this->_pack_field_12()) // Local Transaction Time
                        return $this->result;
                if (!$this->_pack_field_13()) // Local Transaction Date
                        return $this->result;
                if (!$this->_pack_field_42()) // Merchant & Terminal ID
                        return $this->result;
                if (!$this->_pack_card($card))
                        return $this->result;
                if (!$this->_pack_field_EA()) // Source Code
                        return $this->result;

                // optional
                if (!$this->_pack_field_09()) // User 1
                        return $this->result;
                if (!$this->_pack_field_10()) // User 2
                        return $this->result;
                if (!$this->_pack_field_15()) // 15 Terminal Transaction Number
                        return $this->result;
                if (!$this->_pack_field_44()) // Alternate Merchant Number
                        return $this->result;
                // 53 Post Date
                // 55 Transaction Postal Code
                // 62 Clerk ID
                // 0A Device ID
                // 0B IP Address
                // 0C Originating IP Address
                // 0D System ID
                // 7F Echo Back
                // AA Account Origin
                // AC Foreign Access Code
                if (!$this->_pack_field_F3()) // Merchant Key ID
                        return $this->result;

                if (!$this->_req_do($cmd, 1, 1))
                        return $this->result;

                // RESPONSE required
                // 11 System Trace Number
                // 38 Authorization Code
                // 39 Response Code
                // 42 Merchant & Terminal ID
                // 44 Alternate Merchant Number
                // 70 Embossed Card Number
                // 75 Previous Balance
                // 76 New Balance
                // 78 Lock Amount
                // A0 Expiration Date
                // B0 Card Class
                // C0 Local Currency
                // F6 Original Transaction Request

                // RESPONSE optional
                // 08 Reference Number
                // F2 Promotion Code

                $previous_balance = $this->response_field["75"];
                $new_balance = $this->response_field["76"];
                $taken = $previous_balance - $new_balance;
                $amt = $taken / 100.0;
                if ($this->parameters["cardextra"]) {
                        $card["card_extra"] = array();
                        $card["card_extra"]["txn_auth_code"] = $this->response_field["38"];
                        $card["card_extra"]["txn_resp_code"] = $this->response_field["39"];
                        $card["balance"] = $new_balance;
                        $card["card_extra"]["txn_req_code"] = (string)$tcode;
                }
                return true;
        }

        function t_0460($parameters, &$card, &$amt)
        {
                $tcode = 0460;
                $cmd = $tcode . " Balance Adjustment";
                if (!$this->_preamble($parameters, $card))
                        return $this->result;
                if (!$this->_req_header($tcode))
                        return $this->result;

                // required
                if (!$this->_pack_field_05($amt)) // Adjustment Amount
                        return $this->result;
                if (!$this->_pack_field_12()) // Local Transaction Time
                        return $this->result;
                if (!$this->_pack_field_13()) // Local Transaction Date
                        return $this->result;
                if (!$this->_pack_card($card)) // 70, 32/34
                        return $this->result;
                if (!$this->_pack_field_42()) // Merchant & Terminal ID
                        return $this->result;
                if (!$this->_pack_field_44()) // Alternate Merchant Number
                        return $this->result;

                // suggested
                if (!$this->_pack_field_53()) // Post Date
                        return $this->result;
                if (!$this->_pack_field_15()) // Terminal Transaction Number
                        return $this->result;
                if (!$this->_pack_field_C0()) // Local Currency
                        return $this->result;
                // 62 Clerk ID

                // optional
                if (!$this->_pack_field_09()) // User 1
                        return $this->result;
                if (!$this->_pack_field_10()) // User 2
                        return $this->result;
                if (!$this->_pack_field_EA()) // Source Code
                        return $this->result;
                if (!$this->_pack_field_F3()) // Merchant Key ID
                        return $this->result;
                // 06 Card Cost
                // 07 Escheatable Transaction
                // 18 SIC Code
                // AA Account Origin
                // 7F Echo Back

                if (!$this->_req_do($cmd, 1, 1))
                        return $this->result;

                // RESPONSE required
                // 11 System Trace Number
                // 38 Authorization Code
                // 39 Response Code
                // 42 Merchant & Terminal ID
                // 44 Alternate Merchant Number
                // 70 Embossed Card Number
                // 75 Previous Balance
                // 76 New Balance
                // 78 Lock Amount
                // A0 Expiration Date
                // B0 Card Class
                // C0 Local Currency
                // F6 Original Transaction Request

                // RESPONSE optional
                // 08 Reference Number
                // 15 Terminal Transaction Number
                // BC Base Currency
                // 80 Base Previous Balance
                // 81 Base New Balance
                // 82 Base Lock Amount
                // E9 Exchange Rate
                // 7F Echo Back

                $previous_balance = $this->response_field["75"];
                $new_balance = $this->response_field["76"];
                $taken = $previous_balance - $new_balance;
                $amt = $taken / 100.0;
                if ($this->parameters["cardextra"]) {
                        $card["card_extra"] = array();
                        $card["card_extra"]["txn_auth_code"] = $this->response_field["38"];
                        $card["card_extra"]["txn_resp_code"] = $this->response_field["39"];
                        $card["balance"] = $new_balance;
                        $card["card_extra"]["txn_req_code"] = (string)$tcode;
                }
                return true;
        }

        function cardInquiry($parameters, &$card) {
                $tcode = 2409;
                $cmd = $tcode . " Card Inquiry";
                if (!$this->_preamble($parameters, $card))
                        return $this->result;
                if (!$this->_req_header($tcode))
                        return $this->result;

                // required
                if (!$this->_pack_field_12()) // Local Transaction Time
                        return $this->result;
                if (!$this->_pack_field_13()) // Local Transaction Date
                        return $this->result;
                if (!$this->_pack_field_42()) // Merchant & Terminal ID
                        return $this->result;
                if (!$this->_pack_card($card)) // 70, 32/34
                        return $this->result;
                if (!$this->_pack_field_EA())
                        return $this->result;

                // optional
                if (!$this->_pack_field_09())
                        return $this->result;
                if (!$this->_pack_field_10())
                        return $this->result;
                if (!$this->_pack_field_44())
                        return $this->result;


                if (!$this->_req_do($cmd, 1, 1))
                        return $this->result;
                if (!isset($this->response_field["01"])) {
                        $this->_set_garble("No card data");
                        return $this->result;
                } else {
                        $cardExtraTitles = array('consortium_name', 'promo_code', 'currency_code', 'status', 'reloadable',
                        'max_balance', 'min_reload_amount', 'max_reload_amount', 'load_limit', 'card_balance',
                        'lock_balance', 'fund_hold', 'fund_hold_timer', 'future_availability', 'future_availability_date', 'enabled_hot',
                        'security_code',' expiration_type', 'expiration_date_time', 'bonus_value', 'discount', 'denominated',
                        'promoprotect', 'product_restricted', 'old_product_restricted', 'single_use', 'prevent_balance_transfer_merge',
                        'is_close_on_zero');
                }

                if (!isset($this->response_field["70"])) {
                        $this->_set_garble("No card number");
                        return $this->result;
                }
                $card['account_number'] = $this->response_field["70"];
                if ($this->parameters["cardextra"]) {
                                $card["card_extra"] = array();
                                $cardExtraValues = explode("|", $this->response_field["01"]);
                                $idx = 0;
                                foreach ($cardExtraTitles as $title) {
                                        $card["card_extra"]['extra'][$title] = $cardExtraValues[$idx++];
                                }
                                $card["card_extra"]["txn_auth_code"] = $this->response_field["38"];
                                $card["card_extra"]["txn_resp_code"] = $this->response_field["39"];
                                $card["card_extra"]['merchant_terminal_id'] = $this->response_field["42"];
                                $card["card_extra"]['alternate_merchant_number'] = $this->response_field["44"];
                                $card["card_extra"]['card_class'] = $this->response_field["B0"];
                                $card["card_extra"]['original_transaction_request'] = $this->response_field["F6"];
                                $card["card_extra"]["txn_req_code"] = (string)$tcode;
                        }
                        return true;
        }

        // added by Paul Gardner - 18 Nov 2020
        function balanceMerge($parameters, &$src_card, &$dst_card, $amt = "")
        {
                $tcode = 2420;
                $cmd = $tcode . " Balance Merge";
                if (!$this->_preamble($parameters, $src_card))
                        return $this->result;
                if (!$this->_req_header($tcode))
                        return $this->result;

                // required
                if (!$this->_pack_field_12())
                        return $this->result;
                if (!$this->_pack_field_13())
                        return $this->result;
                if (!$this->_pack_field_42())
                        return $this->result;
                if (!$this->_pack_card_plus($src_card, 0x70, 0x40, 0x40))
                        return $this->result;
                if (!$this->_pack_field_EA())
                        return $this->result;
                if (!$this->_pack_card_plus($dst_card, 0xED, 0x41, 0x41))
                        return $this->result;

                // suggested
                if (!$this->_pack_field_15())
                        return $this->result;
                if (!$this->_pack_field_53())
                        return $this->result;

                // optional
                if ($amt != "") {
                        if (!$this->_pack_field_04($amt))
                                return $this->result;
                        if (!$this->_pack_field_C0())
                                return $this->result;
                }
                if (!$this->_pack_field_09())
                        return $this->result;
                if (!$this->_pack_field_10())
                        return $this->result;
                if (!$this->_pack_field_44())
                        return $this->result;
                if (!$this->_pack_field_F3())
                        return $this->result;

                if (!$this->_req_do($cmd, 1, 1))
                        return $this->result;
                return true;
        }

        // added by Paul Gardner - 18 Nov 2020
        function voidBalanceMerge($parameters, &$src_card, &$dst_card, $amt = "")
        {
                $tcode = 2806;
                $cmd = $tcode . " Void of Balance Merge";
                if (!$this->_preamble($parameters, $src_card))
                        return $this->result;
                if (!$this->_req_header($tcode))
                        return $this->result;

                // required
                if (!$this->_pack_field_12())
                        return $this->result;
                if (!$this->_pack_field_13())
                        return $this->result;
                if (!$this->_pack_field_42())
                        return $this->result;
                if (!$this->_pack_card_plus($src_card, 0x70, 0x40, 0x40))
                        return $this->result;
                if (!$this->_pack_field_EA())
                        return $this->result;
                if (!$this->_pack_card_plus($dst_card, 0xED, 0x41, 0x41))
                        return $this->result;

                // suggested
                if (!$this->_pack_field_15())
                        return $this->result;
                if (!$this->_pack_field_53())
                        return $this->result;

                // optional
                if ($amt != "") {
                        if (!$this->_pack_field_04($amt))
                                return $this->result;
                        if (!$this->_pack_field_C0())
                                return $this->result;
                }
                if (!$this->_pack_field_09())
                        return $this->result;
                if (!$this->_pack_field_10())
                        return $this->result;
                if (!$this->_pack_field_44())
                        return $this->result;
                if (!$this->_pack_field_F3())
                        return $this->result;

                if (!$this->_req_do($cmd, 1, 1))
                        return $this->result;
                return true;
        }

        // converse of 2003 Freeze Active Card
        // added by Paul Gardner - 8 Sep 2021
        function t_2016($parameters, &$card, &$amt)
        {
                $tcode = 2016;
                $cmd = $tcode . " Unfreeze Active Card";
                if (!$this->_preamble($parameters, $card))
                        return $this->result;
                if (!$this->_req_header($tcode))
                        return $this->result;

                // required
                if (!$this->_pack_field_12()) // Local Transaction Time
                        return $this->result;
                if (!$this->_pack_field_13()) // Local Transaction Date
                        return $this->result;
                if (!$this->_pack_field_42()) // Merchant & Terminal ID
                        return $this->result;
                if (!$this->_pack_card($card))
                        return $this->result;
                if (!$this->_pack_field_EA()) // Source Code
                        return $this->result;

                // optional
                if (!$this->_pack_field_09()) // User 1
                        return $this->result;
                if (!$this->_pack_field_10()) // User 2
                        return $this->result;
                if (!$this->_pack_field_15()) // 15 Terminal Transaction Number
                        return $this->result;
                if (!$this->_pack_field_44()) // Alternate Merchant Number
                        return $this->result;
                // 53 Post Date
                // 55 Transaction Postal Code
                // 62 Clerk ID
                // 0A Device ID
                // 0B IP Address
                // 0C Originating IP Address
                // 0D System ID
                // 7F Echo Back
                // AA Account Origin
                // AC Foreign Access Code
                if (!$this->_pack_field_F3()) // Merchant Key ID
                        return $this->result;

                if (!$this->_req_do($cmd, 1, 1))
                        return $this->result;

                // RESPONSE required
                // 11 System Trace Number
                // 38 Authorization Code
                // 39 Response Code
                // 42 Merchant & Terminal ID
                // 44 Alternate Merchant Number
                // 70 Embossed Card Number
                // 75 Previous Balance
                // 76 New Balance
                // 78 Lock Amount
                // A0 Expiration Date
                // B0 Card Class
                // C0 Local Currency
                // F6 Original Transaction Request

                // RESPONSE optional
                // 08 Reference Number
                // F2 Promotion Code

                $previous_balance = $this->response_field["75"];
                $new_balance = $this->response_field["76"];
                $taken = $previous_balance - $new_balance;
                $amt = $taken / 100.0;
                if ($this->parameters["cardextra"]) {
                        $card["card_extra"] = array();
                        $card["card_extra"]["txn_auth_code"] = $this->response_field["38"];
                        $card["card_extra"]["txn_resp_code"] = $this->response_field["39"];
                        $card["balance"] = $new_balance;
                        $card["card_extra"]["txn_req_code"] = (string)$tcode;
                }
                return true;
        }

/*
        // delayed activation (v1 only) step 1
        function default_enHodl($parameters, &$card, $amt)
        {
                return true; // no-op
        }

        // delayed activation (v1 only) step 2
        function default_unHodl($parameters, &$card, $amt)
        {
                return $this->activateCard($parameters, $card, $amt);
        }

        if (is_callable(array($ctsvp['_model'], "enHodl"), true))
                $ctsvp['_model']->enHodl($parameters, $card, $amt);
        else
                default_enHodl($parameters, $card, $amt);

        if (is_callable(array($ctsvp['_model'], "unHodl"), true))
                $ctsvp['_model']->unHodl($parameters, $card, $amt);
        else
                default_unHodl($parameters, $card, $amt);

*/

        // delayed activation (v1 or v2) step 1
        function enHodl($parameters, &$card, $amt)
        {
                if (!$this->_preamble($parameters, $card))
                        return $this->result;
                $x = $this->parameters['delayed_activation'];
                if ($x == "v1")
                        return true; // no-op
                if ($x == "v2") {
                        $status = $this->activateCard($parameters, $card, $amt);
                        if (!$status && ($this->response_field["39"] != "08"))
                                return false; // error other than: "Already active. The card is already active and does not need to be reactivated"
                        $status = $this->t_2003($parameters, $card, $amt);
                        if (!$status && ($this->response_field["39"] != "10"))
                                return false; // error other than: "Lost or stolen card. The transaction could not be completed because the account was previously reported as lost or stolen."
                        return true; // card already frozen

                }
                $this->result['errmsg'] = "'".$x."' is not a valid setting for delayed_activation.";
                $this->result['errnum'] = SVP_ERRNUM_INTERNAL;
                return false;
        }

        // could the opt-in to v2 simply refuse to work if there are any outstanding v1 orders?
        // b2c cards in v1 mode will "always" be in flight, thwart this ^^^ check

        // delayed activation (v1 or v2) step 2
        function unHodl($parameters, &$card, $amt)
        {
                if (!$this->_preamble($parameters, $card))
                        return $this->result;
                $x = $this->parameters['delayed_activation'];
                if ($x == "v1")
                        return $this->activateCard($parameters, $card, $amt);
                if ($x == "v2") {
                        return $this->t_2016($parameters, $card, $amt);
/*
to deal with v1 -> v2 transition
                        $status = $this->t_2016($parameters, $card, $amt);
                        if ($status === true)
                                return true;
                        if ($this->response_field["39"] != "04")
                                return false; // error other than: "Inactive account. The account has not been activated by an approved location."
                        // v1 card - never frozen, not yet activated
                        return $this->activateCard($parameters, $card, $amt);
*/
                }
                $this->result['errmsg'] = "'".$x."' is not a valid setting for delayed_activation.";
                $this->result['errnum'] = SVP_ERRNUM_INTERNAL;
                return false;
        }
}
