oot@www0:~/tw/giftcard_admin/install/vxnapi# cat /root/tw/giftcard/source/wgiftcard/application/models/firstdata_model.php -n
     1  <?php
     2
     3  // FD4016 =
     4  // First Data Closed Loop Gift Card Merchant Interface Specifications:
     5  // SVdot Internet Transaction Manual
     6  // Version 4.0 Release 16, August 12 2010
     7
     8  // Provided by Villa, Arnel N Arnel.Villa@firstdata.com via email on 6 Jul 2011.
     9  //
    10  // MID: 99032809997
    11  // TID: 00000000000
    12  //
    13  // Please note that we normally mapped the Datawire TID with the Alt-MID.
    14  //
    15  // Datawire ID: Your test Datawire DID will be obtained when you successfully complete the "Self-Registration" function as outlined in the SecureTransport API provided to you.
    16  //
    17  // Following the instructions provided in the SecureTransport API and as part of your integration testing you will need to configure your application with the following:
    18  //
    19  // Staging URLs
    20  // Primary URL: https://staging1.datawire.net/sd
    21  // Secondary URL: https://staging2.datawire.net/sd
    22  // * For testing purposes only; please use these URLs and assigned parameters noted.
    23  //
    24  // Production URLs
    25  // Primary URL: https://vxn.datawire.net/sd
    26  // Secondary URL: https://vxn1.datawire.net/sd
    27  // * For Production purposes only; please ensure these are defaulted and configurable for production.
    28  //
    29  // Service ID: 104 (A Datawire assigned value to be hard coded in the Application)
    30  //
    31  // App ID: TRWGIFTCARDUNIX2 (The AppID is a Datawire assigned Vendor specific value to be hard coded in the Application.)
    32  //
    33  // Client Ref: Client Ref should be 14 digits set up as follows "tttttttVnnnnnn":
    34  //    "ttttttt" = 7 digit transaction ID that is unique to each transaction for at least a day. Pad with trailing zeros or truncate as required.
    35  //    "V" = the letter "V" for Version. (the version should be dynamic and change along with your app version)
    36  //    "nnnnnn" = the version number of your application, 6 digits long, no periods or spaces, pad with trailing zeros or truncate as required to meet 6
    37
    38  // Test DID=00010743194075661445, obtained 15 Jul 2011.
    39  // Production DID=00017313520045460248, obtained 5 Oct 2011.
    40
    41
    42  class Firstdata_model extends CI_Model
    43  {
    44          var $vxnapid_host = "127.0.0.1";
    45          var $vxnapid_port = "29999";
    46
    47          // currency_code parameter is ISO 4217, translate symbolic to numeric if necessary
    48          var $currency_code_map = array(
    49                  'CAD' => "124",
    50                  'GBP' => "826",
    51                  'EUR' => "978",
    52                  'CHF' => "756",
    53                  'USD' => "840"
    54          );
    55
    56          var $default_parameters = array(
    57                  // VXN (Secure Transport) parameters
    58                  'SVCID' => null,
    59                  'MID' => null,
    60                  'TID' => null,
    61                  'DID' => null,
    62                  // SVdot/CLGC (Closed Loop Gift Card) parameters
    63                  'merchant_number' => null, // identifies Transaction Wireless
    64                  'alternate_merchant_number' => "", // store number
    65                  'source_code' => "30", // Internet With EAN
    66                  //'source_code' => "31", // Internet Without EAN
    67                  'promotion_code' => "0", // Identifies card issuer, including certain card attributes
    68                  // the most important being "demoninated" (fixed value on activation) versus
    69                  // "non-demoninated" (variable value on activation)
    70                  'terminal_id' => "0", // FLD #42
    71                  'currency_code' => "840", // FLD #C0, FIXME: should come from the card or cardtype
    72                  'history_code' => "2410", // transaction type for getHistory()
    73                  'mwk' => null, // for encrypting/decrypting EANs
    74                  'user_1' => "", // FLD #09
    75                  'user_2' => "", // FLD #10
    76                  'client_id' => "", // FLD #CA (see PR15003165 OLTP Aggregator Implementation for VLTW.doc)
    77                  'first_transaction_number' => "-10", // FLD #CE
    78                  'transaction_count' => "10", // FLD #CF
    79                  'history_format' => "84", // FLD #E8
    80                  'mwkid' => "1", // FLD #F3
    81                  'connection_timeout' => 10.0, // in seconds (double)
    82                  'read_timeout' => 31, // in seconds (int)
    83                  'logtofile' => "",
    84                  'debug' => 0,
    85                  'donotpack' => "", // a comma-separated list of fields (eg: "9,15") that won't be packed
    86                  'simulate_timeout' => "0", // for testing reversals
    87                  'force_vxn_response_code' => "", // for testing reversals
    88                  'cardextra' => true,
    89                  'activation_code' => "",
    90                  'stripbin' => "", // a comma-separated list of BINs (eg: "912505,912501") that will be stripped from the card number
    91                  'delayed_activation' => "v1",
    92          );
    93          var $parameters = array();
    94
    95          var $svdot_version_number = "4";
    96          var $svdot_format_number = "0";
    97          var $field_separator;
    98          var $field_table;
    99
   100          var $result; // returned to model caller on error
   101
   102          var $transaction_request_code;
   103          var $request_header;
   104          var $request_field;
   105          var $response_field;
   106
   107          var $time;
   108
   109          // FIXME: for Decline Code Testing
   110          var $force_pin = "";
   111
   112          var $vdata;
   113
   114          function __construct()
   115          {
   116                  parent::__construct();
   117
   118                  $this->benchmark->mark('instantiation_start');
   119
   120                  // REVIEW #ENG-6891
   121                  $this->load->helper('numbers');
   122                  $this->load->model('svp_model'); // for SVP error codes
   123
   124                  $this->result['svperrnum'] = "";
   125                  $this->result['errmsg'] = "";
   126                  $this->result['errnum'] = SVP_ERRNUM_NONE;
   127
   128                  $this->transaction_request_code = "";
   129                  $this->request_header = "";
   130
   131                  /*
   132                  $cluster = @file_get_contents("/var/tw/cluster");
   133                  $cluster = trim($cluster);
   134                  switch ($cluster) {
   135                  case "production":
   136                          break;
   137                  default:
   138                          // test:
   139                          break;
   140                  }
   141  */
   142
   143                  $this->field_separator = chr(0x1C);
   144
   145                  $t = &$this->field_table;
   146                  $t[0x01] = array("Card Inquiry", "AN", 1, 234);
   147                  $t[0x04] = array("Transaction Amount", "N", 1, 12);
   148                  $t[0x05] = array("Adjustment Amount", "SN", 2, 13);
   149                  $t[0x06] = array("Card Cost", "N", 1, 12);
   150                  $t[0x07] = array("Escheatable Transaction", "AN", 1, 1);
   151                  $t[0x08] = array("Reference Number", "AN", 1, 16);
   152                  $t[0x09] = array("User 1", "AN", 1, 20);
   153                  $t[0x10] = array("User 2", "AN", 1, 20);
   154                  $t[0x11] = array("System Trace Number", "N", 6, 6);
   155                  $t[0x12] = array("Local Transaction Time", "N", 6, 6);
   156                  $t[0x13] = array("Local Transaction Date", "N", 8, 8);
   157                  $t[0x15] = array("Terminal Transaction Number", "AN", 1, 17);
   158                  $t[0x16] = array("Card Available for Use Date", "N", 8, 8);
   159                  $t[0x18] = array("SIC Code", "N", 4, 4);
   160                  $t[0x31] = array("Target Security Card Value", "N", 3, 8);
   161                  $t[0x32] = array("Security Card Value (SCV)", "N", 3, 8);
   162                  $t[0x33] = array("Target Extended Account Number", "HT", 32, 32);
   163                  $t[0x34] = array("Extended Account Number (EAN)", "HT", 32, 32);
   164                  $t[0x35] = array("Track II Data", "AN", 37, 37);
   165                  $t[0x36] = array("Foreign Account", "AN", 9, 40);
   166                  $t[0x38] = array("Authorization Code", "N", 6, 6);
   167                  //$t[0x38] = array("Authorization Code", "AN", 8, 8);
   168                  $t[0x39] = array("Response Code", "N", 2, 4);
   169                  $t[0x40] = array("Source Security Code", "HT", 8, 32);
   170                  $t[0x41] = array("Target Security Code", "HT", 8, 32);
   171                  $t[0x42] = array("Merchant & Terminal ID", "N", 15, 15);
   172                  $t[0x44] = array("Alternate Merchant Number", "N", 1, 11);
   173                  $t[0x53] = array("Post Date", "N", 8, 8);
   174                  $t[0x54] = array("Cashback", "N", 1, 12);
   175                  $t[0x62] = array("Clerk ID", "AN", 1, 8);
   176                  $t[0x63] = array("Working Key", "HT", 80, 80);
   177                  //$t[0x70] = array("Embossed Card Number", "N", 13, 19);
   178                  $t[0x70] = array("Embossed Card Number", "ZN", 10, 19);
   179                  $t[0x75] = array("Previous Balance", "N", 1, 12);
   180                  $t[0x76] = array("New Balance", "N", 1, 12);
   181                  $t[0x78] = array("Lock Amount", "N", 1, 12);
   182                  $t[0x79] = array("Local Lock ID", "N", 1, 4);
   183                  $t[0x7F] = array("Echo Back", "AN", 1, 26);
   184                  $t[0x80] = array("Base Previous Balance", "N", 1, 12);
   185                  $t[0x81] = array("Base New Balance", "N", 1, 12);
   186                  $t[0x82] = array("Base Lock Amount", "N", 1, 12);
   187                  $t[0x83] = array("Base Cashback", "N", 1, 12);
   188                  $t[0xAA] = array("Account Origin", "AN", 1, 1);
   189                  $t[0xA0] = array("Expiration Date", "N", 8, 8);
   190                  $t[0xA2] = array("EAN Expiration Date", "N", 13, 13);
   191                  $t[0xAC] = array("Foreign Access Code", "N", 1, 8);
   192                  $t[0xB0] = array("Card Class", "N", 1, 4);
   193                  $t[0xBC] = array("Base Currency", "N", 3, 3);
   194                  $t[0xC0] = array("Local Currency", "N", 3, 3);
   195                  $t[0xCA] = array("Client Identifier", "N", 1, 8);
   196                  $t[0xCE] = array("First Transaction Number", "SN", 1, 5);
   197                  $t[0xCF] = array("Transaction Count", "N", 1, 4);
   198                  $t[0xD0] = array("Transaction History Detail", "FDCD", 1, 512);
   199                  $t[0xE1] = array("Call Trace Information", "AN", 1, 19);
   200                  $t[0xE2] = array("Account Status", "N", 1, 2);
   201                  $t[0xE7] = array("Discounted Amount", "N", 1, 12);
   202                  $t[0xE8] = array("History Format", "N", 1, 2);
   203                  $t[0xE9] = array("Exchange Rate", "N", 3, 18);
   204                  $t[0xEA] = array("Source Code", "N", 2, 3);
   205                  $t[0xEB] = array("Count", "N", 1, 4);
   206                  $t[0xEC] = array("Transaction Record Length", "N", 1, 3);
   207                  $t[0xED] = array("Target Embossed Card Number", "N", 16, 16);
   208                  $t[0xF2] = array("Promotion Code", "N", 1, 8);
   209                  $t[0xF3] = array("Merchant Key ID", "N", 1, 4);
   210                  $t[0xF4] = array("Fraud/Watch/Restricted Status", "N", 1, 2);
   211                  $t[0xF6] = array("Original Transaction Request", "N", 4, 4);
   212                  $t[0xF7] = array("Client Identifier (F7)", "N", 1, 8);
   213                  $t[0xFA] = array("Remove Balance", "AN", 1, 1);
   214
   215                  $this->benchmark->mark('instantiation_end');
   216          }
   217
   218          function _log($msg)
   219          {
   220                  if (isset($this->parameters['logtofile']) && strlen($this->parameters['logtofile'])) {
   221                          $date = date("M j H:i:s");
   222                          $pid = getmypid();
   223                          $msg = $date . " [" . $pid . "]: " . $msg . "\n";
   224                          file_put_contents($this->parameters['logtofile'], $msg, FILE_APPEND);
   225                  }
   226          }
   227
   228          function _dbg($msg)
   229          {
   230                  if ($this->parameters['debug'])
   231                          $this->_log($msg);
   232          }
   233
   234          function _dump($var, $val)
   235          {
   236                  if ($this->parameters['debug']) {
   237                          $msg = $var . "=" . print_r($val, 1);
   238                          $this->_log($msg);
   239                  }
   240          }
   241
   242          function _logInfo($msg)
   243          {
   244                  $this->_log("INFO: " . $msg);
   245                  logInfo($msg);
   246          }
   247
   248          function _logError($msg)
   249          {
   250                  $this->_log("ERROR: " . $msg);
   251                  logError($msg);
   252          }
   253
   254          function _pack_error($msg)
   255          {
   256                  $this->result['errmsg'] = $msg;
   257                  $this->result['errnum'] = SVP_ERRNUM_INTERNAL;
   258                  return false;
   259          }
   260
   261          function _pack(&$data, $name, $type, $minlen, $maxlen, $val)
   262          {
   263                  $x = "";
   264                  if ($type == "AN") {
   265                          $x = str_pad($val, $minlen, " ", STR_PAD_RIGHT); // left justified
   266                  } else if ($type == "HT") {
   267                          $x = $val;
   268                  } else if (($type == "N") || ($type == "SN")) {
   269                          //$x = sprintf("%d", $val);
   270                          //$x = string($val);
   271                          $x = $val;
   272                          $x = str_pad($x, $minlen, " ", STR_PAD_LEFT); // right justified
   273                  } else if ($type == "ZN") { // "N" but zero-filled
   274                          $x = sprintf("%d", $val);
   275                          $x = str_pad($x, $minlen, "0", STR_PAD_LEFT); // right justified
   276                  } else {
   277                          return $this->_pack_error("name=" . $name . " type=" . $type . " minlen=" . $minlen . " maxlen=" . $maxlen . " val='" . $val . "' unknown type");
   278                  }
   279                  if ($this->parameters['debug'])
   280                          $this->_log("type=" . $type . " minlen=" . $minlen . " maxlen=" . $maxlen . " val=" . $val . "= x=" . $x . "=");
   281                  $len = strlen($x);
   282                  if (($len < $minlen) || ($len > $maxlen))
   283                          return $this->_pack_error("name=" . $name . " type=" . $type . " minlen=" . $minlen . " maxlen=" . $maxlen . " val='" . $val . "' bad length");
   284                  $data .= $x;
   285                  return true;
   286          }
   287
   288          function _pack_field($fld, $val)
   289          {
   290                  if (strlen($this->parameters['donotpack'])) {
   291                          // some testing requires certain fields to not be sent
   292                          $donotpack = explode(',', $this->parameters['donotpack']);
   293                          $find = sprintf("%02X", $fld);
   294                          if (in_array($find, $donotpack)) {
   295                                  $this->_dbg("field " . $fld . " not packed.\n");
   296                                  return true;
   297                          }
   298                  }
   299                  if (!isset($this->field_table[$fld]))
   300                          return $this->_pack_error("unknown field=" . $fld);
   301                  $t = $this->field_table[$fld];
   302                  $name = $t[0];
   303                  if ($this->parameters['debug'])
   304                          $this->_log("pack fld=" . sprintf("%02X", $fld) . " name=" . $name);
   305                  $type = $t[1];
   306                  $minlen = $t[2];
   307                  $maxlen = $t[3];
   308                  $data = $this->field_separator;
   309                  $data .= sprintf("%02X", $fld);
   310                  if (!$this->_pack($data, $name, $type, $minlen, $maxlen, $val))
   311                          return false;
   312                  $this->request_field[$fld] = $data;
   313
   314                  return true;
   315          }
   316
   317          function _req_header($transaction_request_code)
   318          {
   319                  $this->request_field = array();
   320                  $this->response_field = array();
   321                  if ($transaction_request_code != 704)
   322                          $this->transaction_request_code = $transaction_request_code;
   323                  $data = "";
   324                  $this->_dbg("pack header");
   325                  if (!$this->_pack($data, "Message Identifier", "AN", 3, 3, "SV."))
   326                          return false;
   327                  if (!$this->_pack($data, "Merchant ID", "N", 11, 11, $this->parameters['merchant_number']))
   328                          return false;
   329                  $data .= $this->field_separator;
   330                  if (!$this->_pack($data, "Version Number", "N", 1, 1, $this->svdot_version_number))
   331                          return false;
   332                  if (!$this->_pack($data, "Format Number", "N", 1, 1, $this->svdot_format_number))
   333                          return false;
   334                  if (!$this->_pack($data, "Transaction Request Code", "ZN", 4, 4, $transaction_request_code))
   335                          return false;
   336                  $this->request_header = $data;
   337                  return true;
   338          }
   339
   340          function _pack_field_04($amt)
   341          {
   342                  return $this->_pack_field(0x04, round($amt * 100));
   343          }
   344
   345          function _pack_field_05($amt)
   346          {
   347                  return $this->_pack_field(0x05, sprintf("%+d", round($amt * 100)));
   348          }
   349
   350          function _pack_field_08($refnum)
   351          {
   352                  return $this->_pack_field(0x08, $refnum);
   353          }
   354
   355          function _pack_field_09()
   356          {
   357                  if (empty($this->parameters['user_1']))
   358                          return true;
   359                  return $this->_pack_field(0x09, $this->parameters['user_1']);
   360          }
   361
   362          function _pack_field_10()
   363          {
   364                  if (empty($this->parameters['user_2']))
   365                          return true;
   366                  return $this->_pack_field(0x10, $this->parameters['user_2']);
   367          }
   368
   369          function _pack_order_details()
   370          {
   371                  if (!empty($this->parameters['send_order_details'])) {
   372                                  $order_details = $this->svp_model->getOrderDetails();
   373                                  if (empty($order_details)) {
   374                                                  logInfo("FirstData:  Param send_order_details is set but no order_details found...returning.");
   375                                                  return true;
   376                                          }
   377                                  if (strlen($order_details) > 20) {
   378                                                  logInfo("FirstData:  Param send_order_details is set but order_details is too long...returning.");
   379                                                  return true;
   380                                          }
   381                                  //Example: user_2 = 123456789;
   382                                  $this->parameters[$this->parameters['send_order_details']] = $order_details;
   383                                  logInfo("FirstData:  Sending transacton_id ($order_details) in field {$this->parameters['send_order_details']}.");
   384                          }
   385                  return true;
   386          }
   387
   388          function _pack_user_details() //Added: RB 44566887
   389          {
   390                  if (!empty($this->parameters['pack_users'])) {
   391                          logInfo("FirstData: pack user details enabled.");
   392                          $user_1 = substr($this->svp_model->getUser1(), 0, 20);
   393              $user_2 = substr($this->svp_model->getUser2(), 0, 20);
   394                          if (strlen($user_1) > 20 || strlen($user_2) > 20) {
   395                                  logInfo("FirstData: param pack_users, user fields larger than 20 characters...returning.");
   396                                  return false;
   397                          }
   398                          $this->parameters['user_1'] = $user_1;
   399                          $this->parameters['user_2'] = $user_2;
   400                  }
   401                  return true;
   402          }
   403
   404          private function _pack_mid_details() // ENG-2087
   405          {
   406                  if (!empty($this->parameters['enable_dynamic_mid'])) {
   407                          logInfo("FirstData: dynamic mid/alt mid enabled.");
   408                          $mid = $this->svp_model->getMid();
   409                          $alt_mid = $this->svp_model->getAltmid();
   410                          //logInfo("FirstData:  Dynamic MID/Alt Mid ($mid) ($alt_mid)");
   411                          if ($mid !== null) {
   412                                  $this->parameters['merchant_number'] = $mid;
   413                          }
   414                          if ($alt_mid !== null) {
   415                                  $this->parameters['alternate_merchant_number'] = $alt_mid;
   416                          }
   417
   418                          $this->svp_model->setMid(null); //ENG-3468
   419                          $this->svp_model->setAltmid(null); //ENG-3468
   420                  }
   421                  return true;
   422          }
   423
   424          //This is initally for SuperValue but can be used for whomever | 1-19-2018
   425          // Parameter Setup: &send_transaction_id=user_2
   426          function _pack_transaction_id()
   427          {
   428                  if (!empty($this->parameters['send_transaction_id'])) {
   429                                  $transaction_id = $this->svp_model->getTransactionId();
   430                                  if (empty($transaction_id)) {
   431                                                  logInfo("FirstData:  Param send_transaction_id is set but no transaction id found...returning.");
   432                                                  return true;
   433                                          }
   434                                  if (strlen($transaction_id) > 20) {
   435                                                  logInfo("FirstData:  Param send_transaction_id is set but transaction id is too long...returning.");
   436                                                  return true;
   437                                          }
   438                                  //Example: user_2 = 123456789;
   439                                  $this->parameters[$this->parameters['send_transaction_id']] = $transaction_id;
   440                                  logInfo("FirstData:  Sending transacton_id ($transaction_id) in field {$this->parameters['send_transaction_id']}.");
   441                          }
   442                  return true;
   443          }
   444
   445          function _pack_field_12()
   446          {
   447                  return $this->_pack_field(0x12, date('His', $this->time));
   448          }
   449
   450          function _pack_field_13()
   451          {
   452                  return $this->_pack_field(0x13, date('mdY', $this->time));
   453          }
   454
   455          function _pack_field_15()
   456          {
   457                  if (isset($this->vdata['msg_id'])) {
   458                          $id = $this->vdata['msg_id'];
   459                  } else {
   460                          // must be unique for 90 days
   461                          $DAY = sprintf("%02d", date('z') % 90); // 2 digits (day of year modulo 90)
   462                          // REVIEW #ENG-6891
   463                          $IX = sprintf("%08d", random_integer(0, 99999999)); //uses Mersenne Twister, should be good
   464                          $id = $DAY . $IX;
   465                  }
   466                  return $this->_pack_field(0x15, $id);
   467          }
   468
   469          function _pack_field_32($card)
   470          {
   471                  if (isset($card['reference_number']) && strlen($card['reference_number']))
   472                          $pin = $card['reference_number'];
   473                  else if (isset($card['pin']) && strlen($card['pin']))
   474                          $pin = $card['pin'];
   475                  if (strlen($this->force_pin))
   476                          $pin = $this->force_pin;
   477                  if (!isset($pin))
   478                          return true; // no pin to specify
   479                  return $this->_pack_field(0x32, $pin);
   480          }
   481
   482          // more generic version of _pack_field_32(), could replace it someday...
   483          // added by Paul Gardner - 18 Nov 2020
   484          function _pack_field_scv($field, $card)
   485          {
   486                  if (isset($card['reference_number']) && strlen($card['reference_number']))
   487                          $pin = $card['reference_number'];
   488                  else if (isset($card['pin']) && strlen($card['pin']))
   489                          $pin = $card['pin'];
   490                  if (strlen($this->force_pin))
   491                          $pin = $this->force_pin;
   492                  if (!isset($pin))
   493                          return true; // no pin to specify
   494                  return $this->_pack_field($field, $pin);
   495          }
   496
   497          function _pack_field_34($card)
   498          {
   499                  if (isset($card['reference_number']) && strlen($card['reference_number']))
   500                          $pin = $card['reference_number'];
   501                  else if (isset($card['pin']) && strlen($card['pin']))
   502                          $pin = $card['pin'];
   503                  if (strlen($this->force_pin))
   504                          $pin = $this->force_pin;
   505                  if (!isset($pin))
   506                          return true; // no pin to specify
   507                  $cmd = "firstdata_crypt encrypt_ean " . $this->parameters['mwk'] . " " . $pin;
   508                  $this->_dbg("exec " . $cmd);
   509                  exec($cmd, $outraw, $status);
   510                  $output = join("\n", $outraw);
   511                  $this->_dbg("status=" . $status . " output=" . $output);
   512                  if ($status != 0) {
   513                          $msg = "EAN encryption failed ($cmd): " . $output;
   514                          $this->result['errmsg'] = $msg;
   515                          $this->result['errnum'] = SVP_ERRNUM_INTERNAL;
   516                          return false;
   517                  }
   518                  $ean = trim($output);
   519                  return $this->_pack_field(0x34, $ean);
   520          }
   521
   522          // more generic version of _pack_field_34(), could replace it someday...
   523          // added by Paul Gardner - 18 Nov 2020
   524          function _pack_field_ean($field, $card)
   525          {
   526                  if (isset($card['reference_number']) && strlen($card['reference_number']))
   527                          $pin = $card['reference_number'];
   528                  else if (isset($card['pin']) && strlen($card['pin']))
   529                          $pin = $card['pin'];
   530                  if (strlen($this->force_pin))
   531                          $pin = $this->force_pin;
   532                  if (!isset($pin))
   533                          return true; // no pin to specify
   534                  $cmd = "firstdata_crypt encrypt_ean " . $this->parameters['mwk'] . " " . $pin;
   535                  $this->_dbg("exec " . $cmd);
   536                  exec($cmd, $outraw, $status);
   537                  $output = join("\n", $outraw);
   538                  $this->_dbg("status=" . $status . " output=" . $output);
   539                  if ($status != 0) {
   540                          $msg = "EAN encryption failed ($cmd): " . $output;
   541                          $this->result['errmsg'] = $msg;
   542                          $this->result['errnum'] = SVP_ERRNUM_INTERNAL;
   543                          return false;
   544                  }
   545                  $ean = trim($output);
   546                  return $this->_pack_field($field, $ean);
   547          }
   548
   549          function _pack_field_42()
   550          {
   551                  $xdata = "";
   552                  if (!$this->_pack($xdata, "-", "N", 11, 11, $this->parameters['merchant_number']))
   553                          return false;
   554                  if (!$this->_pack($xdata, "-", "ZN", 4, 4, $this->parameters['terminal_id']))
   555                          return false;
   556                  return $this->_pack_field(0x42, $xdata);
   557          }
   558
   559          function _pack_field_44()
   560          {
   561                  if (!empty($this->parameters['alternate_merchant_number']))
   562                          return $this->_pack_field(0x44, $this->parameters['alternate_merchant_number']);
   563                  return true;
   564          }
   565
   566          function _pack_field_53()
   567          {
   568                  return $this->_pack_field(0x53, date('mdY', $this->time));
   569          }
   570
   571          function _pack_field_63()
   572          {
   573                  return $this->_pack_field(0x63, $this->parameters['mwk']);
   574          }
   575
   576          function _pack_field_70($card)
   577          {
   578                  return $this->_pack_field(0x70, $card['account_number']);
   579          }
   580
   581          function _pack_field_79($lockid)
   582          {
   583                  return $this->_pack_field(0x79, $lockid);
   584          }
   585
   586          function _pack_field_C0()
   587          {
   588                  return $this->_pack_field(0xC0, $this->parameters['currency_code']);
   589          }
   590
   591          function _pack_field_CA()
   592          {
   593                  if (empty($this->parameters['client_id']))
   594                          return true;
   595                  return $this->_pack_field(0xCA, $this->parameters['client_id']);
   596          }
   597
   598          function _pack_field_CE($offset = '')
   599          { // for getHistory()
   600                  if (strlen($offset) > 0) //JC -- allow us to get more than 10 results
   601                          return $this->_pack_field(0xCE, "-$offset");
   602                  else
   603                          return $this->_pack_field(0xCE, $this->parameters['first_transaction_number']);
   604          }
   605
   606          function _pack_field_CF()
   607          { // for getHistory()
   608                  if (strlen($this->parameters['transaction_count']))
   609                          return $this->_pack_field(0xCF, $this->parameters['transaction_count']);
   610
   611                  return true;
   612          }
   613
   614          function _pack_field_E8()
   615          { // for getHistory()
   616                  return $this->_pack_field(0xE8, $this->parameters['history_format']);
   617          }
   618
   619          function _pack_field_EA()
   620          {
   621                  return $this->_pack_field(0xEA, $this->parameters['source_code']);
   622          }
   623
   624          function _pack_field_ED($card)
   625          {
   626                  return $this->_pack_field(0xED, $card['account_number']);
   627          }
   628
   629
   630          function _pack_field_F2()
   631          {
   632                  return $this->_pack_field(0xF2, $this->parameters['promotion_code']);
   633          }
   634
   635          function _pack_field_F3()
   636          {
   637                  if ($this->parameters['source_code'] == "30") // with EAN
   638                          return $this->_pack_field(0xF3, $this->parameters['mwkid']);
   639                  return true;
   640          }
   641
   642          /*
   643          Used to set the account status to Watch or Fraud or restricted.
   644                  0 – Reverse: Remove the Watch/Fraud/Restricted status (reverts to prior account status).
   645                  1 – Watch status: Suspicious card
   646                  2 – Fraud status: Confirmed fraud
   647                  3 – Restricted status: It restricts a default and configurable internet transaction list maintained at consortium level.
   648           */
   649          function _pack_field_F4()
   650          {
   651                  if (!empty($this->parameters['watch_status'])) {
   652                          $status = (int)$this->parameters['watch_status'];
   653                  } else {
   654                          $status = 2; // default to fraud
   655                  }
   656
   657                  return $this->_pack_field(0xF4, $status);
   658          }
   659
   660          function _pack_field_F6($code)
   661          {
   662                  return $this->_pack_field(0xF6, $code);
   663          }
   664
   665          /*
   666          Used to remove balance when closing an account.
   667                  ‘Y’ - remove balance if non-zero
   668                  ‘N’ - do not remove balance.
   669           */
   670          function _pack_field_FA()
   671          {
   672                  if (!empty($this->parameters['remove_balance'])) {
   673                          $remove = (string)$this->parameters['remove_balance'];
   674                  } else {
   675                          $remove = 'Y'; // default to yes
   676                  }
   677
   678                  return $this->_pack_field(0xFA, $remove);
   679          }
   680
   681          function _pack_card($card)
   682          {
   683                  if (strlen($this->parameters['stripbin']) && strlen($card['account_number']) >= 16) {
   684                          // Winco needs certain BINs to be stripped from the card number
   685                          $stripbin = explode(',', $this->parameters['stripbin']);
   686                          foreach ($stripbin as $bin) {
   687                                  $bin = trim($bin);
   688                                  if (strpos($card['account_number'], $bin) === 0) {
   689                                          $card['account_number'] = substr($card['account_number'], strlen($bin));
   690                                          break;
   691                                  }
   692                          }
   693                  }
   694
   695                  if (!$this->_pack_field_70($card))
   696                          return $this->result;
   697                  if ($this->parameters['source_code'] == "30") {
   698                          if (!$this->_pack_field_34($card))
   699                                  return $this->result;
   700                  } else if ($this->parameters['source_code'] == "31") {
   701                          if (!$this->_pack_field_32($card))
   702                                  return $this->result;
   703                  } else {
   704                          $this->result['errmsg'] = "Invalid source_code value";
   705                          $this->result['errnum'] = SVP_ERRNUM_INTERNAL;
   706                          return false;
   707                  }
   708                  return true;
   709          }
   710
   711          // more generic version of _pack_card(), could replace it someday...
   712          // added by Paul Gardner - 18 Nov 2020
   713          function _pack_card_plus($card, $account_number_field = 0x70, $ean_field = 0x34, $scv_field = 0x32)
   714          {
   715                  if (strlen($this->parameters['stripbin']) && strlen($card['account_number']) >= 16) {
   716                          // Winco needs certain BINs to be stripped from the card number
   717                          $stripbin = explode(',', $this->parameters['stripbin']);
   718                          foreach ($stripbin as $bin) {
   719                                  $bin = trim($bin);
   720                                  if (strpos($card['account_number'], $bin) === 0) {
   721                                          $card['account_number'] = substr($card['account_number'], strlen($bin));
   722                                          break;
   723                                  }
   724                          }
   725                  }
   726
   727                  if (!$this->_pack_field($account_number_field, $card['account_number']))
   728                          return $this->result;
   729                  if ($this->parameters['source_code'] == "30") {
   730                          if (!$this->_pack_field_ean($ean_field, $card))
   731                                  return $this->result;
   732                  } else if ($this->parameters['source_code'] == "31") {
   733                          if (!$this->_pack_field_scv($scv_field, $card))
   734                                  return $this->result;
   735                  } else {
   736                          $this->result['errmsg'] = "Invalid source_code value";
   737                          $this->result['errnum'] = SVP_ERRNUM_INTERNAL;
   738                          return false;
   739                  }
   740                  return true;
   741          }
   742
   743          function _vxn_errmsg($rc)
   744          {
   745                  switch ($rc) {
   746                          case "1":
   747                                  return "Authentication Failed — Invalid ID(s)";
   748                          case "2":
   749                                  return "Invalid Packet ID";
   750                          case "5":
   751                                  return "Invalid Data Length";
   752                          case "6":
   753                                  return "Invalid Session Context";
   754                          case "8":
   755                                  return "Network Error";
   756                          case "9":
   757                                  return "Send Error";
   758                          case "11":
   759                                  return "Timeout Error";
   760                          case "13":
   761                                  return "Authentication Failed";
   762                          case "14":
   763                                  return "Null Query";
   764                          case "30":
   765                                  return "No Memory";
   766                          case "35":
   767                                  return "Invalid Service Name";
   768                          case "40":
   769                                  return "Service Unavailable";
   770                          case "41":
   771                                  return "XML Error";
   772                          case "42":
   773                                  return "HTTP Error";
   774                          case "43":
   775                                  return "Internet Error";
   776                          case "44":
   777                                  return "Incorrect URL(s)";
   778                          case "45":
   779                                  return "No Service";
   780                          case "46":
   781                                  return "XML Parse Error";
   782                          case "47":
   783                                  return "Request Overflow";
   784                          case "48":
   785                                  return "Incorrect Response";
   786                          case "51":
   787                                  return "Response Overflow";
   788                          case "52":
   789                                  return "Internet Timeout";
   790                          case "53":
   791                                  return "Send Error";
   792                          case "54":
   793                                  return "Receive Error";
   794                          case "55":
   795                                  return "Retry Registration";
   796                          case "56":
   797                                  return "Duplicate Registration";
   798                          case "57":
   799                                  return "Registration Failed";
   800                          case "58":
   801                                  return "Access Denied";
   802                          case "59":
   803                                  return "Either MID or TID is not correct";
   804                          case "60":
   805                                  return "Data not found in provisioning database";
   806                          case "62":
   807                                  return "Invalid SSL certificate";
   808                          case "200":
   809                                  return "Host Busy";
   810                          case "201":
   811                                  return "Host Unavailable";
   812                          case "202":
   813                                  return "Host Connect Error";
   814                          case "203":
   815                                  return "Host Drop";
   816                          case "204":
   817                                  return "Host Comm Error";
   818                          case "205":
   819                                  return "No Response";
   820                          case "206":
   821                                  return "Host Send Error";
   822                          case "405":
   823                                  return "Secure Transport Timeout";
   824                          case "505":
   825                                  return "Network Error";
   826                  }
   827                  return "Unrecognized";
   828          }
   829
   830          function _svdot_errmsg($rc)
   831          {
   832                  switch ($rc) {
   833                          case "00":
   834                                  return "Completed OK.";
   835                          case "01":
   836                                  return "Insufficient funds.";
   837                          case "02":
   838                                  return "Account closed. The account was closed, probably because the account balance was $0.00.";
   839                          case "03":
   840                                  return "Unknown account. The account could not be located in the account table.";
   841                          case "04":
   842                                  return "Inactive account. The account has not been activated by an approved location.";
   843                          case "05":
   844                                  return "Expired card. The card’s expiration date has been exceeded.";
   845                          case "06":
   846                                  return "Invalid transaction code. This card or terminal is not permitted to perform this transaction, or the transaction code is invalid.";
   847                          case "07":
   848                                  return "Invalid merchant. The merchant is not in the merchant database or the merchant is not permitted to use this particular card.";
   849                          case "08":
   850                                  return "Already active. The card is already active and does not need to be reactivated";
   851                          case "09":
   852                                  return "System error. There is a problem with the host processing system. Call your help desk or operations support.";
   853                          case "10":
   854                                  return "Lost or stolen card. The transaction could not be completed because the account was previously reported as lost or stolen.";
   855                          case "11":
   856                                  return "Not lost or stolen. The replacement transaction could not be completed because the account was not previously marked as lost/stolen.";
   857                          case "12":
   858                                  return "Invalid transaction format. There is a transaction format problem.";
   859                          case "15":
   860                                  return "Bad mag stripe. The mag stripe could not be parsed for account information.";
   861                          case "16":
   862                                  return "Incorrect location. There was a problem with the merchant location.";
   863                          case "17":
   864                                  return "Max balance exceeded. The transaction, if completed, would cause the account balance to be exceeded by the max_balance as specified in the promotion. Some merchants set the max_balance to a value twice the max transaction amount.";
   865                          case "18":
   866                                  return "Invalid amount. There was a problem with the amount field in the transaction format – more or less than min/max amounts specified in the promotion for that transaction.";
   867                          case "19":
   868                                  return "Invalid clerk. The clerk field was either missing, when required, or the content did not match the requirements.";
   869                          case "20":
   870                                  return "Invalid password. The user password was invalid.";
   871                          case "21":
   872                                  return "Invalid new password. The new password does not meet the minimum security criteria.";
   873                          case "22":
   874                                  return "Exceeded account reloads. The clerk/user/location was only permitted to reload some number of accounts. That number was exceeded. (See your Business Managerin order to extend this limit.)";
   875                          case "23":
   876                                  return "Password retry exceeded. The user account has been frozen because the user attempted access and was denied. Seek management assistance.";
   877                          case "26":
   878                                  return "Incorrect transaction version or format number for POS transactions.";
   879                          case "27":
   880                                  return "Request not permitted by this account.";
   881                          case "28":
   882                                  return "Request not permitted by this merchant location.";
   883                          case "29":
   884                                  return "Bad_replay_date.";
   885                          case "30":
   886                                  return "Bad checksum. The checksum provided is incorrect.";
   887                          case "31":
   888                                  return "Balance not available (denial). Due to an internal First Data Closed Loop Gift Card (CLGC) issue, information from this account could not be retrieved. ";
   889                          case "32":
   890                                  return "Account locked. ";
   891                          case "33":
   892                                  return "No previous transaction. The void or reversal transaction could not be matched to a previous (original) transaction. In the case of a pre-auth redemption, the corresponding locking transaction could not be identified. ";
   893                          case "34":
   894                                  return "Already reversed. ";
   895                          case "35":
   896                                  return "Generic denial. An error was produced which has no other corresponding response code for the provided version/format. ";
   897                          case "36":
   898                                  return "Bad authorization code. The authorization code test failed. ";
   899                          case "37":
   900                                  return "Too many transactions requested. See SVdot Transaction Manual Appendix B for Transaction History limits by detail record format. ";
   901                          case "38":
   902                                  return "No transactions available/no more transactions available. There are no transactions for this account or there are no transactions as determined by the specified first transaction number. ";
   903                          case "39":
   904                                  return "Transaction history not available. The history could not be provided. ";
   905                          case "40":
   906                                  return "New password required. ";
   907                          case "41":
   908                                  return "Invalid status change. The status change requested (e.g. lost/stolen, freeze active card) cannot be performed. ";
   909                          case "42":
   910                                  return "Void of activation after account activity. ";
   911                          case "43":
   912                                  return "No phone service. Attempted a calling card transaction on an account which is not configured for calling card activity. ";
   913                          case "44":
   914                                  return "Internet access disabled. This account may no longer use transactions in which an EAN is required. ";
   915                          case "45":
   916                                  return "Invalid EAN. The EAN is not correct for the provided account number. ";
   917                          case "46":
   918                                  return "Invalid merchant key. The merchant key block provided is invalid. (e.g.  The working key provided in an Assign Merchant Working Key transaction). ";
   919                          case "47":
   920                                  return "Promotions for Internet Virtual and Physical cards do not match. When enabling a physical card to a virtual card, both must be from the same promotion. Cards for bulk activation request must be from the same promotion. ";
   921                          case "48":
   922                                  return "Invalid transaction source. The provided source (field EA) is not valid for this transaction. ";
   923                          case "49":
   924                                  return "Account already linked. (e.g. Response when enabling a physical card, when the two provided accounts have already been linked together.) ";
   925                          case "50":
   926                                  return "Account not in inactive state. (e.g. Response when enabling a physical card, when the physical card in not in an inactive state.) ";
   927                          case "51":
   928                                  return "First Data Voice Services returns this response on Internet transactions where the interface input parameter is not valid. ";
   929                          case "52":
   930                                  return "First Data Voice Services returns this response on Internet transactions where they did not receive a response from CLGC. ";
   931                          case "53":
   932                                  return "First Data Voice Services returns this response on Internet transactions where the client certificate is invalid. ";
   933                          case "54":
   934                                  return "Merchant not configured as International although the account requires it. (e.g. The account allows currency conversion but the merchant is not configured for International.) ";
   935                          case "55":
   936                                  return "Invalid currency. The provided currency is invalid. ";
   937                          case "56":
   938                                  return "Request not International. Merchant configured to require currency information for each financial transaction, however none was sent.";
   939                          case "57":
   940                                  return "Currency conversion error. Internal CLGC system error.";
   941                          case "58":
   942                                  return "Invalid Expiration Date. Expiration date provided is not valid.";
   943                          case "59":
   944                                  return "The terminal transaction number did not match (on a void or reversal).";
   945                          case "60":
   946                                  return "First Data Voice Services added a layer of validation that checks the data they receive from CLGC to make sure it is HTML friendly (i.e. no binary data). First Data Voice Services will return this response on Internet transactions if the check fails (the data is not HTML friendly).";
   947                          case "67":
   948                                  return "Target Embossed Card entered and Transaction Count entered are mismatched.";
   949                          case "68":
   950                                  return "No Account Link.";
   951                          case "69":
   952                                  return "Invalid Timezone.";
   953                          case "70":
   954                                  return "Account On Hold.";
   955                          case "71":
   956                                  return "Fraud Count Exceeded.";
   957                          case "72":
   958                                  return "Promo Location Restricted.";
   959                          case "73":
   960                                  return "Invalid BIN.";
   961                          case "74":
   962                                  return "Product Code(s) Restricted.";
   963                          case "75":
   964                                  return "Bad Post Date. The Post Date is not a valid date.";
   965                          case "76":
   966                                  return "Account Status is Void Lock.";
   967                          case "77":
   968                                  return "Already active. The card is already active and is reloadable.";
   969                          case "78":
   970                                  return "Account is Purged. The Account record was purged from the database.";
   971                          case "79":
   972                                  return "Deny duplicate transaction.";
   973                          case "80":
   974                                  return "Bulk Activation Error.";
   975                          case "81":
   976                                  return "Bulk Activation Unattempted Error.";
   977                          case "82":
   978                                  return "Bulk Activation Package Amount Error.";
   979                          case "83":
   980                                  return "Store Location Zero Not Allowed.";
   981                          case "84":
   982                                  return "Account Row Locked.";
   983                          case "85":
   984                                  return "Accepted but not yet processed.";
   985                          case "86":
   986                                  return "Incorrect PVC.";
   987                          case "87":
   988                                  return "Provisioning limit exceeded, currently 10.";
   989                          case "88":
   990                                  return "De-provisioning limit reached, current count is 0.";
   991                          case "89":
   992                                  return "The EAN TYPE is not mentioned in manufacture_fa table.";
   993                          case "90":
   994                                  return "Field SCV is required in the transaction.";
   995                          case "91":
   996                                  return "Promo Code is not compatible for consortium code.";
   997                          case "92":
   998                                  return "Product Restricted Declined.";
   999                          case "94":
  1000                                  return "Account notlinked error.";
  1001                          case "95":
  1002                                  return "Account is in Watch status.";
  1003                          case "96":
  1004                                  return "Account is in Fraud status.";
  1005                  }
  1006                  return "Unrecognized response code";
  1007          }
  1008
  1009          function _set_garble($msg)
  1010          {
  1011                  $this->result['errmsg'] = $msg;
  1012                  $this->result['errnum'] = SVP_ERRNUM_GARBLE;
  1013                  return false;
  1014          }
  1015
  1016          function _check_garble($what, $val_actual, $val_good)
  1017          {
  1018                  if ($val_actual === $val_good)
  1019                          return true;
  1020                  return $this->_set_garble("Response " . $what . " is " . $val_actual . ", should be " . $val_good);
  1021          }
  1022
  1023          // Implementation based on "SVdot Appendix B.pdf"
  1024          function _FDCD_decode(&$z, $name, $type, $len, &$val)
  1025          {
  1026                  //echo("FDCD_decode: ".print_r($name,1)."\n"); //pgar
  1027                  if ($name == "Amount Sign") {
  1028                          // special case, "Amount Sign" is not encoded
  1029                          if (strlen($z) < $len)
  1030                                  return $this->_set_garble("FDCD garbled @" . $name . " " . $z);
  1031                          $w = substr($z, 0, 1);
  1032                          $z = substr($z, 1);
  1033                          $val[$name] = $w;
  1034                          return true;
  1035                  }
  1036                  $fdcd_len = intval(($len + 1) / 2);
  1037                  //echo("FDCD_decode len= ".print_r($len,1)." fdcd_len=".print_r($fdcd_len,1)." strlen(z)=".strlen($z)."\n"); //pgar
  1038                  if (strlen($z) < $fdcd_len)
  1039                          return $this->_set_garble("FDCD garbled @" . $name . " " . $z);
  1040                  $ww = "";
  1041                  for ($i = 0; $i < $fdcd_len; $i++) {
  1042                          $w = substr($z, 0, 1);
  1043                          $z = substr($z, 1);
  1044                          $ww .= sprintf("%02d", ord($w) - 32);
  1045                  }
  1046                  //echo("FDCD_decode: ww=".print_r($ww,1)."\n"); //pgar
  1047                  // remove zero-padding on left
  1048                  while ((strlen($ww) > 1) && (substr($ww, 0, 1) == "0"))
  1049                          $ww = substr($ww, 1);
  1050                  $len = strlen($ww);
  1051                  if (!$this->_unpack($ww, $type, $len, $len, $len, $xval))
  1052                          return false;
  1053                  $val[$name] = $xval;
  1054                  return true;
  1055          }
  1056
  1057          function _FDCD_decode_datetime(&$z, &$val)
  1058          {
  1059                  if (!$this->_FDCD_decode($z, "Month", "N", 2, $val))
  1060                          return false;
  1061                  if (!$this->_FDCD_decode($z, "Day", "N", 2, $val))
  1062                          return false;
  1063                  if (!$this->_FDCD_decode($z, "Century", "N", 2, $val))
  1064                          return false;
  1065                  if (!$this->_FDCD_decode($z, "Year", "N", 2, $val))
  1066                          return false;
  1067                  if (!$this->_FDCD_decode($z, "Hour", "N", 2, $val))
  1068                          return false;
  1069                  if (!$this->_FDCD_decode($z, "Minutes", "N", 2, $val))
  1070                          return false;
  1071                  return true;
  1072          }
  1073
  1074          function _FDCD_unpack($z, &$val)
  1075          {
  1076                  $history_format = $this->parameters['history_format'];
  1077                  //echo("FDCD_unpack: history_format=".print_r($history_format,1)."\n"); //pgar
  1078
  1079                  // only 8-bit decoding is implemented
  1080                  if (intval($history_format / 10) != 8) {
  1081                          $this->result['errmsg'] = "Cannot handle history format " . $history_format;
  1082                          $this->result['errnum'] = SVP_ERRNUM_INTERNAL;
  1083                          return false;
  1084                  }
  1085
  1086                  // header
  1087                  if (!$this->_FDCD_decode($z, "Account status indicator", "AN", 2, $val)) return false;
  1088                  if (!$this->_FDCD_decode($z, "Detail version format indicator", "N", 2, $val)) return false;
  1089                  if (!$this->_FDCD_decode($z, "Base Currency Code", "N", 3, $val)) return false;
  1090
  1091                  // records
  1092                  $val['Detail Records'][] = array();
  1093                  $nrec = 0;
  1094                  while (strlen($z)) {
  1095                          $rec = "";
  1096                          switch ($history_format) {
  1097                                  case "81":
  1098                                          if (!$this->_FDCD_decode($z, "Primary Merchant Number", "N", 11, $rec)) return false;
  1099                                          if (!$this->_FDCD_decode($z, "Request Code", "N", 4, $rec)) return false;
  1100                                          if (!$this->_FDCD_decode($z, "Amount Sign", "AN", 1, $rec)) return false;
  1101                                          if (!$this->_FDCD_decode($z, "Transaction Amount in Base", "N", 12, $rec)) return false;
  1102                                          if (!$this->_FDCD_decode($z, "Local Lock Amount", "N", 12, $rec)) return false;
  1103                                          if (!$this->_FDCD_decode_datetime($z, $rec)) return false;
  1104                                          break;
  1105                                  case "82":
  1106                                          if (!$this->_FDCD_decode($z, "Alternate Merchant Number", "N", 11, $rec)) return false;
  1107                                          if (!$this->_FDCD_decode($z, "Request Code", "N", 4, $rec)) return false;
  1108                                          if (!$this->_FDCD_decode($z, "Amount Sign", "AN", 1, $rec)) return false;
  1109                                          if (!$this->_FDCD_decode($z, "Transaction Amount in Base", "N", 12, $rec)) return false;
  1110                                          if (!$this->_FDCD_decode($z, "Account Balance in Base", "N", 12, $rec)) return false;
  1111                                          if (!$this->_FDCD_decode($z, "Lock Amount", "N", 12, $rec)) return false;
  1112                                          if (!$this->_FDCD_decode_datetime($z, $rec)) return false;
  1113                                          break;
  1114                                  case "83":
  1115                                          if (!$this->_FDCD_decode($z, "Alternate Merchant Number", "N", 11, $rec)) return false;
  1116                                          if (!$this->_FDCD_decode($z, "Terminal Number", "N", 4, $rec)) return false;
  1117                                          if (!$this->_FDCD_decode($z, "Request Code", "N", 4, $rec)) return false;
  1118                                          if (!$this->_FDCD_decode($z, "Amount Sign", "AN", 1, $vv)) return false;
  1119                                          if (!$this->_FDCD_decode($z, "Transaction Amount in Base", "N", 12, $rec)) return false;
  1120                                          if (!$this->_FDCD_decode($z, "Account Balance in Base", "N", 12, $rec)) return false;
  1121                                          if (!$this->_FDCD_decode($z, "Local Lock Amount", "N", 12, $rec)) return false;
  1122                                          if (!$this->_FDCD_decode_datetime($z, $rec)) return false;
  1123                                          break;
  1124                                  case "84":
  1125                                          if (!$this->_FDCD_decode($z, "Alternate Merchant Number", "N", 8, $rec)) return false;
  1126                                          if (!$this->_FDCD_decode($z, "Terminal Number", "N", 4, $rec)) return false;
  1127                                          if (!$this->_FDCD_decode($z, "Request Code", "N", 4, $rec)) return false;
  1128                                          if (!$this->_FDCD_decode($z, "Amount Sign", "AN", 1, $rec)) return false;
  1129                                          if (!$this->_FDCD_decode($z, "Transaction Amount in Base", "N", 12, $rec)) return false;
  1130                                          if (!$this->_FDCD_decode($z, "Account Balance in Base", "N", 12, $rec)) return false;
  1131                                          if (!$this->_FDCD_decode($z, "Local Lock Amount", "N", 12, $rec)) return false;
  1132                                          if (!$this->_FDCD_decode_datetime($z, $rec)) return false;
  1133                                          break;
  1134                                  case "85":
  1135                                          if (!$this->_FDCD_decode($z, "Primary Merchant Number", "N", 11, $rec)) return false;
  1136                                          if (!$this->_FDCD_decode($z, "Request Code", "N", 4, $rec)) return false;
  1137                                          if (!$this->_FDCD_decode($z, "Amount Sign", "AN", 1, $rec)) return false;
  1138                                          if (!$this->_FDCD_decode($z, "Local Currency Code", "N", 3, $rec)) return false;
  1139                                          if (!$this->_FDCD_decode($z, "Local Amount", "N", 12, $rec)) return false;
  1140                                          if (!$this->_FDCD_decode($z, "Transaction Amount in Base", "N", 12, $rec)) return false;
  1141                                          if (!$this->_FDCD_decode($z, "Account Balance in Base", "N", 12, $rec)) return false;
  1142                                          if (!$this->_FDCD_decode($z, "Local Lock Amount", "N", 12, $rec)) return false;
  1143                                          if (!$this->_FDCD_decode_datetime($z, $rec)) return false;
  1144                                          break;
  1145                                  case "86":
  1146                                          if (!$this->_FDCD_decode($z, "Alternate Merchant Number", "N", 11, $rec)) return false;
  1147                                          if (!$this->_FDCD_decode($z, "Request Code", "N", 4, $rec)) return false;
  1148                                          if (!$this->_FDCD_decode($z, "Amount Sign", "AN", 1, $rec)) return false;
  1149                                          if (!$this->_FDCD_decode($z, "Local Currency Code", "N", 3, $rec)) return false;
  1150                                          if (!$this->_FDCD_decode($z, "Local Amount", "N", 12, $rec)) return false;
  1151                                          if (!$this->_FDCD_decode($z, "Transaction Amount in Base", "N", 12, $rec)) return false;
  1152                                          if (!$this->_FDCD_decode($z, "Account Balance in Base", "N", 12, $rec)) return false;
  1153                                          if (!$this->_FDCD_decode($z, "Local Lock Amount", "N", 12, $rec)) return false;
  1154                                          if (!$this->_FDCD_decode_datetime($z, $rec)) return false;
  1155                                          break;
  1156                                  case "87":
  1157                                          if (!$this->_FDCD_decode($z, "Alternate Merchant Number", "N", 11, $rec)) return false;
  1158                                          if (!$this->_FDCD_decode($z, "Terminal Number", "N", 4, $rec)) return false;
  1159                                          if (!$this->_FDCD_decode($z, "Request Code", "N", 4, $rec)) return false;
  1160                                          if (!$this->_FDCD_decode($z, "Amount Sign", "AN", 1, $rec)) return false;
  1161                                          if (!$this->_FDCD_decode($z, "Local Currency Code", "N", 3, $rec)) return false;
  1162                                          if (!$this->_FDCD_decode($z, "Local Amount", "N", 12, $rec)) return false;
  1163                                          if (!$this->_FDCD_decode($z, "Transaction Amount in Base", "N", 12, $rec)) return false;
  1164                                          if (!$this->_FDCD_decode($z, "Account Balance in Base", "N", 12, $rec)) return false;
  1165                                          if (!$this->_FDCD_decode($z, "Local Lock Amount", "N", 12, $rec)) return false;
  1166                                          if (!$this->_FDCD_decode_datetime($z, $rec)) return false;
  1167                                          break;
  1168                                  case "88":
  1169                                          if (!$this->_FDCD_decode($z, "Alternate Merchant Number", "N", 8, $rec)) return false;
  1170                                          if (!$this->_FDCD_decode($z, "Terminal Number", "N", 4, $rec)) return false;
  1171                                          if (!$this->_FDCD_decode($z, "Request Code", "N", 4, $rec)) return false;
  1172                                          if (!$this->_FDCD_decode($z, "Amount Sign", "AN", 1, $rec)) return false;
  1173                                          if (!$this->_FDCD_decode($z, "Local Currency Code", "N", 3, $rec)) return false;
  1174                                          if (!$this->_FDCD_decode($z, "Local Amount", "N", 12, $rec)) return false;
  1175                                          if (!$this->_FDCD_decode($z, "Transaction Amount in Base", "N", 12, $rec)) return false;
  1176                                          if (!$this->_FDCD_decode($z, "Account Balance in Base", "N", 12, $rec)) return false;
  1177                                          if (!$this->_FDCD_decode($z, "Local Lock Amount", "N", 12, $rec)) return false;
  1178                                          if (!$this->_FDCD_decode_datetime($z, $rec)) return false;
  1179                                          break;
  1180                                  default:
  1181                                          $this->result['errmsg'] = "Cannot handle history format " . $history_format;
  1182                                          $this->result['errnum'] = SVP_ERRNUM_INTERNAL;
  1183                                          return false;
  1184                          }
  1185                          $val['Detail Records'][$nrec++] = $rec;
  1186                  }
  1187                  return true;
  1188          }
  1189
  1190          function _unpack(&$x, $type, $minlen, $maxlen, $len, &$val)
  1191          {
  1192                  if ((strlen($x) < $minlen) || (($maxlen != -1) && (strlen($x) > $maxlen)))
  1193                          return $this->_set_garble("type=" . $type . " minlen=" . $minlen . " maxlen=" . $maxlen . " x='" . $x . "' bad length");
  1194                  $z = substr($x, 0, $len);
  1195                  if ($type == "AN") {
  1196                          $z = rtrim($z, " ");
  1197                          $val = $z;
  1198                  } else if ($type == "HT") {
  1199                          $val = $z;
  1200                  } else if (($type == "N") || ($type == "SN")) {
  1201                          $z = ltrim($z, " ");
  1202                          $val = $z;
  1203                  } else if ($type == "ZN") { // "N" but zero-filled
  1204                          $z = ltrim($z, "0");
  1205                          if (!strlen($z))
  1206                                  $z = "0";
  1207                          $val = $z;
  1208                  } else if ($type == "FDCD") {
  1209                          if (!$this->_FDCD_unpack($z, $val))
  1210                                  return false;
  1211                  } else {
  1212                          return $this->_set_garble("Unknown type " . $type);
  1213                  }
  1214                  $x = substr($x, $len);
  1215                  if ($this->parameters['debug'])
  1216                          $this->_log("unpack type=" . $type . " minlen=" . $minlen . " maxlen=" . $maxlen . " val=" . print_r($val, 1) . "= z=" . $z . "=");
  1217                  return true;
  1218          }
  1219
  1220          function _parse_response($response)
  1221          {
  1222                  $x = $response;
  1223                  if (!$this->_unpack($x, "N", 1, -1, 1, $version_number))
  1224                          return false;
  1225                  if (!$this->_check_garble("version_number", $version_number, $this->svdot_version_number))
  1226                          return false;
  1227                  if (!$this->_unpack($x, "N", 1, -1, 1, $format_number))
  1228                          return false;
  1229                  if (!$this->_check_garble("format_number", $format_number, $this->svdot_format_number))
  1230                          return false;
  1231                  if (!$this->_unpack($x, "AN", 1, -1, 1, $field_separator))
  1232                          return false;
  1233                  if (!$this->_check_garble("field_separator", $field_separator, $this->field_separator))
  1234                          return false;
  1235                  // response indicator code
  1236                  if (!$this->_unpack($x, "ZN", 4, -1, 4, $message_type_identifier))
  1237                          return false;
  1238                  if (!$this->_check_garble("message_type_identifier", $message_type_identifier, "900"))
  1239                          return false;
  1240                  if (!$this->_unpack($x, "AN", 1, -1, 1, $field_separator))
  1241                          return false;
  1242                  if (!$this->_check_garble("field_separator", $field_separator, $this->field_separator))
  1243                          return false;
  1244
  1245                  $a = explode($this->field_separator, $x);
  1246                  // loop over fields ...
  1247                  foreach ($a as $val) {
  1248                          $fldnum = substr($val, 0, 2);
  1249                          $x = substr($val, 2);
  1250                          $this->_dbg("fldnum=" . $fldnum . " x=" . $x . "=");
  1251                          $z = "";
  1252                          $len = strlen($x);
  1253
  1254                          sscanf($fldnum, "%02x", $fld);
  1255                          if (isset($this->field_table[$fld])) {
  1256                                  $t = $this->field_table[$fld];
  1257                                  if (!$this->_unpack($x, $t[1], $t[2], $t[3], $len, $z))
  1258                                          return false;
  1259                          }
  1260
  1261                          /*
  1262                          switch ($fldnum) {
  1263                          case "11": // System Trace Number
  1264                                  if (!$this->_unpack($x, "N", 6, 6, $len, $z))
  1265                                          return false;
  1266                                  break;
  1267                          case "15": // Terminal Transaction Number
  1268                                  if (!$this->_unpack($x, "AN", 1, 17, $len, $z))
  1269                                          return false;
  1270                                  break;
  1271                          case "32": // SCV
  1272                                  if (!$this->_unpack($x, "N", 8, 8, $len, $z))
  1273                                          return false;
  1274                                  break;
  1275                          case "34": // EAN
  1276                                  if (!$this->_unpack($x, "AN", 32, 32, $len, $z)) // type?
  1277                                          return false;
  1278                                  break;
  1279                          case "35": // Track II Data
  1280                                  if (!$this->_unpack($x, "AN", 37, 37, $len, $z))
  1281                                          return false;
  1282                                  break;
  1283                          case "38": //Auth Code
  1284                                  if (!$this->_unpack($x, "AN", 6, 8, $len, $z))
  1285                                          return false;
  1286                                  break;
  1287                          case "39": // Response Code
  1288                                  if (!$this->_unpack($x, "N", 2, 4, $len, $z))
  1289                                          return false;
  1290                                  break;
  1291                          case "70": // Embossed Card Number
  1292                                  if (!$this->_unpack($x, "N", 13, 19, $len, $z))
  1293                                          return false;
  1294                                  break;
  1295                          case "76": // New Balance
  1296                                  if (!$this->_unpack($x, "N", 1, 12, $len, $z))
  1297                                          return false;
  1298                                  break;
  1299                          case "D0": // Transaction History Detail, for getHistory()
  1300                                  if (!$this->_unpack($x, "FDCD", 1, 512, $len, $z))
  1301                                          return false;
  1302                                  break;
  1303                          case "EB": // Count, for getHistory()
  1304                                  if (!$this->_unpack($x, "N", 1, 4, $len, $z))
  1305                                          return false;
  1306                                  break;
  1307                          case "EC": // Transaction Record Length, for getHistory()
  1308                                  if (!$this->_unpack($x, "N", 1, 3, $len, $z))
  1309                                          return false;
  1310                                  break;
  1311                          }
  1312  */
  1313                          $this->response_field[$fldnum] = $z;
  1314                  }
  1315                  if (!isset($this->response_field["39"]))
  1316                          return $this->_set_garble("No response code");
  1317                  $response_code = $this->response_field["39"];
  1318                  $this->_dbg("response_code=" . $response_code);
  1319                  if ($response_code != "00" && $response_code != '38') { //RB: 46111198 FIX 12-9-2019 ... TO fix history throwing an error when no history
  1320                          $this->result['svperrnum'] = $response_code;
  1321                          $this->result['errmsg'] = $this->_svdot_errmsg($response_code);
  1322                          $this->result['errnum'] = SVP_ERRNUM_REJECTED;
  1323                          /*
  1324                          switch ($response_code) {
  1325                          case "02":
  1326                          case "03":
  1327                          case "05":
  1328                          case "10":
  1329                                  $this->result['errnum'] = SVP_ERRNUM_REJECTED;
  1330                                  break;
  1331                          }
  1332  */
  1333                          return false;
  1334                  }
  1335                  return true;
  1336          }
  1337
  1338          function _socket_error($sock)
  1339          {
  1340                  $this->result['errmsg'] = socket_strerror(socket_last_error($sock));
  1341                  $this->result['errnum'] = SVP_ERRNUM_CONNECTION;
  1342                  socket_close($sock);
  1343                  return false;
  1344          }
  1345
  1346          // replace all card # digits except last 4 with X
  1347          function _sanitize_field($fld, $msg)
  1348          {
  1349                  $t = $this->field_table[$fld];
  1350                  $minlen = $t[2];
  1351                  $maxlen = $t[3];
  1352                  $regex = sprintf("/%s%02X([0-9]+)%s/", $this->field_separator, $fld, $this->field_separator);
  1353                  if (preg_match($regex, $msg, $match)) {
  1354                          $XX = str_repeat("X", strlen($match[1]) - 4);
  1355                          $lastfour = substr($match[1], -4);
  1356                          $old = $match[0];
  1357                          $new = sprintf("/%s%02X%s%s%s/", $this->field_separator, $fld, $XX, $lastfour, $this->field_separator);
  1358                          $msg = str_replace($old, $new, $msg);
  1359                  }
  1360                  return $msg;
  1361          }
  1362
  1363          function _sanitize($msg)
  1364          {
  1365                  $msg = $this->_sanitize_field(0x70, $msg);
  1366                  $msg = $this->_sanitize_field(0xED, $msg);
  1367                  return $msg;
  1368          }
  1369
  1370          function _req_do_raw($cmd, $req, &$vxnapi_error)
  1371          {
  1372                  $vxnapi_error = 0;
  1373                  $this->result['svperrnum'] = "";
  1374                  $this->result['errmsg'] = "";
  1375                  $this->result['errnum'] = SVP_ERRNUM_NONE;
  1376
  1377                  $this->benchmark->mark("Firstdata_" . $cmd . "_start");
  1378                  /*
  1379                  $sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
  1380                  //socket_set_option($sock, getprotobyname('tcp'), TCP_NODELAY, 1); // disable nagel
  1381                  if (!@socket_connect($sock, $this->vxnapid_host, $this->vxnapid_port))
  1382                          return $this->_socket_error($sock);
  1383                  if ($this->parameters['simulate_timeout']) {
  1384                          $msg = "simulate_timeout\n";
  1385                  } else {
  1386                          $msg = "transaction\n";
  1387                          $msg .= $this->parameters['SVCID']."\n";
  1388                          $msg .= $this->parameters['MID']."\n";
  1389                          $msg .= $this->parameters['TID']."\n";
  1390                          $msg .= $this->parameters['DID']."\n";
  1391                          $msg .= $req."\n";
  1392                          if (!empty($this->parameters['force_vxn_response_code']))
  1393                                  $msg .= $this->parameters['force_vxn_response_code']."\n";
  1394                  }
  1395                  $msg .= "\n\n";
  1396                  $this->_log("Parameters: ".print_r($this->parameters,1));
  1397                  $this->_log("Request to vxnapid: ".$req);
  1398                  logInfo("Request to vxnapid: ".$this->_sanitize($req));
  1399
  1400                  if (!socket_send($sock, $msg, strlen($msg), 0))
  1401                          return $this->_socket_error($sock);
  1402
  1403                  $output = socket_read($sock, 2048, PHP_BINARY_READ);
  1404                  if ($output === FALSE)
  1405                          return $this->_socket_error($sock);
  1406                  socket_close($sock);
  1407                  */
  1408
  1409                  $this->benchmark->mark("fsockopen_start");
  1410                  $fp = @fsockopen($this->vxnapid_host, $this->vxnapid_port, $errno, $errmsg, $this->parameters['connection_timeout']);
  1411                  $this->benchmark->mark("fsockopen_end");
  1412
  1413                  if (!$fp) {
  1414                                  logInfo("Socket Err Msg: " . print_r($errmsg, true));
  1415                                  $this->result['errmsg'] = "Problem with " . $this->vxnapid_host . " - " . $errmsg . " (" . $errno . ")";
  1416                                  $this->result['errnum'] = SVP_ERRNUM_CONNECTION;
  1417                                  return false;
  1418                          }
  1419
  1420                  stream_set_timeout($fp, $this->parameters['read_timeout']);
  1421
  1422                  if ($this->parameters['simulate_timeout']) {
  1423                                  $msg = "simulate_timeout\n";
  1424                          } else {
  1425                                  $msg = "transaction\n";
  1426                                  $msg .= $this->parameters['SVCID'] . "\n";
  1427                                  $msg .= $this->parameters['MID'] . "\n";
  1428                                  $msg .= $this->parameters['TID'] . "\n";
  1429                                  $msg .= $this->parameters['DID'] . "\n";
  1430                                  $msg .= $req . "\n";
  1431                                  if (!empty($this->parameters['force_vxn_response_code']))
  1432                                          $msg .= $this->parameters['force_vxn_response_code'] . "\n";
  1433                          }
  1434                  $msg .= "\n\n";
  1435
  1436                  $this->_log("Parameters: " . print_r($this->parameters, 1));
  1437                  $this->_log("Request to vxnapid: " . $req);
  1438                  logInfo("Request to vxnapid: " . $this->_sanitize($req));
  1439
  1440                  $this->benchmark->mark("fwrite_start");
  1441                  @fwrite($fp, $msg);
  1442                  $this->benchmark->mark("fwrite_end");
  1443                  $this->benchmark->mark("fread_start");
  1444                  $output = '';
  1445                  while (!feof($fp)) {
  1446
  1447                                  // stream_set_timeout does not work with SSL, see http://bugs.php.net/bug.php?id=47929
  1448                                  $r[] = $fp;
  1449                                  $w = array();
  1450                                  $e = array();
  1451                                  if (!stream_select($r, $w, $e, $this->parameters['read_timeout'])) {
  1452                                                  $this->result['errmsg'] = "Problem with " . $this->vxnapid_host . " - Timed out after " . $this->parameters['read_timeout'] . "sec";
  1453                                                  $this->result['errnum'] = SVP_ERRNUM_CONNECTION;
  1454                                                  return false;
  1455                                          }
  1456
  1457                                  $output .= @fread($fp, 2048);
  1458                                  $meta = stream_get_meta_data($fp);
  1459                                  //$this->_dump("meta", $meta);
  1460                                  if ($meta['timed_out']) {
  1461                                                  $this->result['errmsg'] = "Problem with " . $this->vxnapid_host . " - Timed out after " . $this->parameters['read_timeout'] . "sec";
  1462                                                  $this->result['errnum'] = SVP_ERRNUM_CONNECTION;
  1463                                                  return false;
  1464                                          }
  1465                          }
  1466                  @fclose($fp);
  1467                  $this->benchmark->mark("fread_end");
  1468
  1469                  $this->benchmark->mark("Firstdata_" . $cmd . "_end");
  1470                  $this->_log("Response from vxnapid: " . $output);
  1471                  logInfo("Response from vxnapid: " . $this->_sanitize($output));
  1472                  if (!preg_match('/^VXN: (.*)$/Usm', $output, $regs)) {
  1473                          // indeterminate vxnapi_client error
  1474                          $this->result['errmsg'] = $output;
  1475                          $this->result['errnum'] = SVP_ERRNUM_INTERNAL;
  1476                          return false;
  1477                  }
  1478                  $vxn_status = $regs[1];
  1479                  if ($vxn_status) {
  1480                          // vxnapi error
  1481                          $vxnapi_error = 1;
  1482                          $this->result['svperrnum'] = $vxn_status;
  1483                          $this->result['errmsg'] = 'Firstdata/VXN: ' . $vxn_status . ' - ' . $this->_vxn_errmsg($vxn_status);
  1484                          $this->result['errnum'] = SVP_ERRNUM_CONNECTION;
  1485                          return false;
  1486                  }
  1487                  if (!preg_match('/^Response: (.*)$/Usm', $output, $regs)) {
  1488                          $this->result['errmsg'] = 'Unrecognizable response';
  1489                          $this->result['errnum'] = SVP_ERRNUM_GARBLE;
  1490                          return false;
  1491                  }
  1492                  $response = $regs[1];
  1493                  if (!$this->_parse_response($response))
  1494                          return false;
  1495                  return true;
  1496          }
  1497
  1498          function _req_do_cooked($cmd, $ntry, $reversal_required)
  1499          {
  1500                  // From http://php.net/manual/en/function.set-time-limit.php:
  1501                  //
  1502                  // Note: The set_time_limit() function and the configuration directive max_execution_time
  1503                  // only affect the execution time of the script itself. Any time spent on activity that
  1504                  // happens outside the execution of the script such as system calls using system(), stream
  1505                  // operations, database queries, etc. is not included when determining the maximum time
  1506                  // that the script has been running. This is not true on Windows where the measured time
  1507                  // is real.
  1508                  //
  1509                  // We set a limit that exceeds our worst case expected run time.
  1510                  //set_time_limit(intval(5+$ntry*($this->parameters['connection_timeout']+$this->parameters['read_timeout']+10)+0.5));
  1511                  $req = $this->request_header . join("", $this->request_field);
  1512
  1513                  for ($tryi = 0; $tryi < $ntry; $tryi++) {
  1514                          $ret = $this->_req_do_raw($cmd, $req, $vxnapi_error);
  1515                          if ($ret)
  1516                                  return $ret;
  1517                          $this->_logInfo("Try " . $tryi . " " . $cmd . " " . $this->result['errmsg']);
  1518                          if (!$vxnapi_error && isset($this->result['svperrnum']))
  1519                                  break;
  1520                  }
  1521                  // no response after $nry tries
  1522                  if ($vxnapi_error && $reversal_required) {
  1523                          // FD4016 p24
  1524                          $saved_result = $this->result;
  1525                          $cmd = "Timeout Reversal";
  1526                          $this->parameters['simulate_timeout'] = 0;
  1527                          $this->parameters['force_vxn_response_code'] = "";
  1528                          $original_request_field = $this->request_field;
  1529                          if (!$this->_req_header(704))
  1530                                  return $this->result;
  1531                          if (!$this->_pack_field_F6($this->transaction_request_code))
  1532                                  return $this->result;
  1533                          $req = $this->request_header . join("", $this->request_field) . join("", $original_request_field);
  1534                          for ($tryi = 0; $tryi < 3; $tryi++) {
  1535                                  $ret = $this->_req_do_raw($cmd, $req, $vxnapi_error);
  1536                                  if ($ret)
  1537                                          break; // reversal went through
  1538                                  if (!$vxnapi_error && isset($this->result['svperrnum']))
  1539                                          break; // error response, "probably nothing to reverse" or "already reversed"
  1540                                  $this->_logInfo("Try " . $tryi . " " . $cmd . " " . $this->result['errmsg']);
  1541                          }
  1542                          $this->result = $saved_result;
  1543                  }
  1544                  //4-25-2012 --- special case if account closed aka 0 balance for a while, just say 0 balance rather than an error
  1545                  //if ($this->result['svperrnum'] === '02' && $cmd === 'Inquire Current Balance') { //Commented this out RB: 48022538
  1546                  if ($this->result['svperrnum'] === '02' && ($cmd === 'Inquire Current Balance' || stripos($cmd,'Transaction History') !== false)) { //Added the stripos so it would match the 3 types of history statements RB: 48022538
  1547                          $this->response_field["76"] = 0;
  1548                          return true;
  1549                  }
  1550                  //END 4-25-2012
  1551                  //$msg = "Failed ".$cmd;
  1552                  //$msg .= " parameters=".print_r($this->parameters,1);
  1553                  //$this->_logInfo($msg);
  1554
  1555                  $msg = "Failed " . $cmd;
  1556                  $msg .= " - " . $this->result['errnum'];
  1557                  $msg .= " - Firstdata/SVdot error message and code: " . $this->result['errmsg'];
  1558                  if (isset($this->result['svperrnum']))
  1559                          $msg .= " - " . $this->result['svperrnum'];
  1560                  switch ($cmd) {
  1561                          case 'Inquire Current Balance':
  1562                                  $this->_logInfo($msg);
  1563                                  break;
  1564                          case 'Inquire Transaction History':
  1565                                  //$this->_logInfo($msg); //don't log this because we get a lot of these that are not really errors because we get it 3 times
  1566                                  break;
  1567                          default:
  1568                                  $this->_logInfo($msg);
  1569                                  break;
  1570                  }
  1571                  return false;
  1572          }
  1573
  1574          // like _req_do_loop_on_client_id() but uses the card's actual client_id (F7) to minimize looping
  1575          // avoids ValueLink $TXNLOG_MAX_FAIL_COUNT limit (more than this many errors per second will produce error 09)
  1576          /*
  1577          function _req_do_smart_client_id($cmd, $ntry, $reversal_required) {
  1578                  $TXNLOG_MAX_FAIL_COUNT = 3;
  1579                  if (empty($this->parameters['client_id']))
  1580                          return $this->_req_do_cooked($cmd, $ntry, $reversal_required);
  1581                  $a = explode(",", $this->parameters['client_id']);
  1582                  if (count($a) > 1)
  1583                          logInfo("multiconsortium smart ".$this->parameters['client_id']);
  1584                  $n = 0;
  1585                  foreach ($a as $client_id) {
  1586                          $this->parameters['client_id'] = $client_id;
  1587                          if (!$this->_pack_field_CA())
  1588                                  return false;
  1589                          $n++;
  1590                          if ($n >= $TXNLOG_MAX_FAIL_COUNT)
  1591                                  $t0 = microtime(true);
  1592                          if ($this->_req_do_cooked($cmd, $ntry, $reversal_required))
  1593                                  return true;
  1594                          // request failed
  1595                          //if ($this->result['svperrnum'] !== "07")
  1596                          //      return false; // give up on any error status other than 07 - "Invalid merchant..."
  1597                          if (isset($this->response_field["F7"])) {
  1598                                  // if present, field F7 contains the correct client_id
  1599                                  $client_id = $this->response_field["F7"];
  1600                                  logInfo("actual client_id ".$client_id);
  1601                                  if (array_search($client_id, $a) === false)
  1602                                          return false; // this card's actual client_id doesn't match any we expect
  1603                                  $this->parameters['client_id'] = $client_id;
  1604                                  if (!$this->_pack_field_CA())
  1605                                          return false;
  1606                                  return $this->_req_do_cooked($cmd, $ntry, $reversal_required);
  1607                          }
  1608                          if ($n >= $TXNLOG_MAX_FAIL_COUNT) {
  1609                                  // avoid ValueLink $TXNLOG_MAX_FAIL_COUNT limit
  1610                                  $us_wait = 1.1 * 1000000; // total time to wait
  1611                                  $us_used = round(microtime(true)-$t0); // time already spent on request/response
  1612                                  $us = round($us_wait - $us_used);
  1613                                  if ($us > 0) {
  1614                                          logInfo("TXNLOG_MAX_FAIL_COUNT delay ".$us);
  1615                                          usleep($us_wait - $us_used);
  1616                                  }
  1617                          }
  1618                  }
  1619                  return false; // all consortium values produced error, return last
  1620          }
  1621  */
  1622
  1623          // like _req_do_cooked() but brute force loop over all client_id
  1624          /*
  1625          function _req_do_loop_on_client_id($cmd, $ntry, $reversal_required) {
  1626                  if (empty($this->parameters['client_id']))
  1627                          return $this->_req_do_cooked($cmd, $ntry, $reversal_required);
  1628                  $a = explode(",", $this->parameters['client_id']);
  1629                  if (count($a) > 1)
  1630                          logInfo("multiconsortium loop ".$this->parameters['client_id']);
  1631                  foreach ($a as $client_id) {
  1632                          $this->parameters['client_id'] = $client_id;
  1633                          if (!$this->_pack_field_CA())
  1634                                  return false;
  1635                          if ($this->_req_do_cooked($cmd, $ntry, $reversal_required))
  1636                                  return true;
  1637                  }
  1638                  return false; // all consortium values produced error, return last
  1639          }
  1640  */
  1641
  1642          // like _req_do_cooked() but simply checks that returned client_id matches one we expect
  1643          // replaces _req_do_smart_client_id() and _req_do_loop_on_client_id()
  1644          function _req_do_simple_client_id($cmd, $ntry, $reversal_required)
  1645          {
  1646                  if (empty($this->parameters['client_id']))
  1647                          return $this->_req_do_cooked($cmd, $ntry, $reversal_required);
  1648                  $client_id_list = $this->parameters['client_id'];
  1649                  logInfo("multiconsortium simple " . $client_id_list);
  1650                  if (!$this->_req_do_cooked($cmd, $ntry, $reversal_required))
  1651                          return false;
  1652                  if (!isset($this->response_field["F7"])) {
  1653                          $errmsg = "response field F7 is not set.";
  1654                          logError($errmsg);
  1655                          $this->result['errmsg'] = $errmsg;
  1656                          $this->result['errnum'] = SVP_ERRNUM_INTERNAL;
  1657                          return false;
  1658                  }
  1659                  // verify the actual client_id matches one in our parameter list
  1660                  $client_id = $this->response_field["F7"];
  1661                  $a = explode(",", $client_id_list);
  1662                  if (array_search($client_id, $a) === false) {
  1663                          $errmsg = "response field F7 " . $client_id . " doesn't match client_id " . $client_id_list;
  1664                          logError($errmsg);
  1665                          $errmsg = "response field F7 doesn't match client_id";
  1666                          $this->result['errmsg'] = $errmsg;
  1667                          $this->result['errnum'] = SVP_ERRNUM_INTERNAL;
  1668                          return false;
  1669                  }
  1670                  logInfo("actual client_id " . $client_id);
  1671                  return true;
  1672          }
  1673
  1674          function _req_do($cmd, $ntry, $reversal_required)
  1675          {
  1676                  //return $this->_req_do_loop_on_client_id($cmd, $ntry, $reversal_required); // force all transactions to loop on client_id
  1677                  return $this->_req_do_cooked($cmd, $ntry, $reversal_required); // ignore client_id
  1678          }
  1679
  1680          function _set_parameters($parameters)
  1681          {
  1682          $parameters = $this->parameterOverrides($parameters);
  1683                  $this->parameters = array_merge($this->default_parameters, $parameters);
  1684
  1685                  $cc = $this->parameters['currency_code'];
  1686                  if (isset($this->currency_code_map[$cc]))
  1687                          $this->parameters['currency_code'] = $this->currency_code_map[$cc];
  1688
  1689                  foreach ($this->parameters as $key => $val) {
  1690                          if (is_null($val)) {
  1691                                  $errmsg = "Firstdata parameter " . $key . " is not set.";
  1692                                  logError($errmsg);
  1693                                  $this->result['errmsg'] = $errmsg;
  1694                                  $this->result['errnum'] = SVP_ERRNUM_INTERNAL;
  1695                                  return false;
  1696                          }
  1697                  }
  1698                  //echo("<pre>OUT parameters=".print_r($parameters,1)."</pre>");
  1699                  //echo("<pre>DEF parameters=".print_r($this->default_parameters,1)."</pre>");
  1700                  //echo("<pre>ALL parameters=".print_r($this->parameters,1)."</pre>");
  1701                  return true;
  1702          }
  1703
  1704          function _preamble($parameters, $card)
  1705          {
  1706                  date_default_timezone_set("UTC"); // affects return value of date() calls
  1707                  $this->time = time();
  1708                  if (!$this->_set_parameters($parameters))
  1709                          return false;
  1710                  $this->_dbg(date("Y-m-d\TH:i:sP") . "\n");
  1711                  $this->_dbg("parameters=" . print_r($parameters, 1) . "\n");
  1712                  if (isset($card['msg_id']))
  1713                          $this->vdata['msg_id'] = $card['msg_id'];
  1714                  if (isset($card['merchant_id']))
  1715                          $this->vdata['merchant_id'] = $card['merchant_id'];
  1716
  1717                  //$this->_pack_transaction_id(); //Added 1-19-2019 to support sending transaction id over
  1718                  $this->_pack_order_details(); //Added 6/5/2018 overrides mistaken requirement to send transaction id
  1719                  $this->_pack_user_details(); //Added 8-2-2019 -- RB 44566887
  1720                  $this->_pack_mid_details(); //ENG-2087
  1721                  return true;
  1722          }
  1723
  1724      private function parameterOverrides($parameters) {
  1725          $overrides = $this->svp_model->getOverrides();
  1726          if (!empty($overrides)) {
  1727              logInfo('Applying parameter overrides');
  1728              $parameters = array_merge($parameters, $overrides);
  1729          }
  1730          $this->svp_model->setOverrides(null);
  1731          return $parameters;
  1732      }
  1733
  1734          function getBalance($parameters, $vdata, &$amt)
  1735          {
  1736                  $this->vdata = $vdata;
  1737                  $card = $this->vdata['recipient']['card'];
  1738
  1739                  if (!$this->_preamble($parameters, $card))
  1740                          return $this->result;
  1741                  $cmd = 'Inquire Current Balance';
  1742                  if (!$this->_req_header(2400))
  1743                          return $this->result;
  1744                  if (!$this->_pack_field_09())
  1745                          return $this->result;
  1746                  if (!$this->_pack_field_10())
  1747                          return $this->result;
  1748                  if (!$this->_pack_field_12())
  1749                          return $this->result;
  1750                  if (!$this->_pack_field_13())
  1751                          return $this->result;
  1752                  if (!$this->_pack_card($card))
  1753                          return (isset($this->parameters['use_std_error']) && $this->parameters['use_std_error']) ? $this->_getStdErr($this->result) : $this->result;
  1754                  if (!$this->_pack_field_F3())
  1755                          return $this->result;
  1756                  if (!$this->_pack_field_42())
  1757                          return $this->result;
  1758                  if (!$this->_pack_field_EA())
  1759                          return $this->result;
  1760                  if (!$this->_pack_field_15())
  1761                          return $this->result;
  1762                  if (!$this->_pack_field_53())
  1763                          return $this->result;
  1764                  if (!$this->_pack_field_C0())
  1765                          return $this->result;
  1766                  if (!$this->_pack_field_44())
  1767                          return $this->result;
  1768                  //if (!$this->_req_do($cmd, 1, 0))
  1769                  if (!$this->_req_do_simple_client_id($cmd, 1, 0))
  1770                          return (isset($this->parameters['use_std_error']) && $this->parameters['use_std_error']) ? $this->_getStdErr($this->result) : $this->result;
  1771                  if (!isset($this->response_field["76"])) {
  1772                          $this->_set_garble("No amount");
  1773                          return (isset($this->parameters['use_std_error']) && $this->parameters['use_std_error']) ? $this->_getStdErr($this->result) : $this->result;
  1774                  }
  1775
  1776          // save client_id here, regardless if we have the parameter set
  1777          $this->svp_model->setClientId($this->response_field["F7"]);
  1778                  $amt = $this->response_field["76"] / 100.0;
  1779                  return true;
  1780          }
  1781
  1782          function getHistory($parameters, $vdata, &$history)
  1783          {
  1784                  $history = array();
  1785                  //2-19-2019 - JC
  1786                  //Run a normal balance first that supports client_id locking and if the balance
  1787                  //works then run the history. RB #40281026
  1788                  $amt = 0;
  1789                  $bc_result = $this->getBalance($parameters, $vdata, $amt);
  1790                  if (is_array($bc_result) || $bc_result !== true) {
  1791                                  $history = null; //JC - Added 9-30-2019  -- RB: 45441403  | Added this because if still an empty array then it assumes successful, so we need to set it to null, to force error condition.
  1792                                  logError('Enhanced Balance:  Running standard balance first failed, so not running enhanced balance.');
  1793                                  return $bc_result;
  1794                          }
  1795                  if (!empty($parameters['HIS_MID']) && strlen($parameters['HIS_MID']) > 1) {
  1796                                  logInfo('Enhanced Balance:  Found HS_MID set so using that for the enhanced balance.');
  1797                                  $this->parameters['MID'] = $parameters['HIS_MID'];
  1798                          }
  1799                  //End 2-19-2019
  1800                  $max_loops = 3; //return 30 results max
  1801                  $step = 10;
  1802                  //ok now do some work
  1803                  $offset = 10;
  1804                  $new_block = array();
  1805                  for ($counter = 0; $counter < $max_loops; $counter++) {
  1806              $result = $this->_getHistory($parameters, $vdata, $offset, $new_block);
  1807              //RB - 46111198  -- 11-21-2019 -- Return a balance if card is good, but has no history
  1808              if (isset($new_block[0]['no_history']) && $new_block[0]['no_history'] === true) {
  1809                  if (count($history) == 0) {
  1810                      $history = $new_block;
  1811                  }
  1812                  // else just return the history we have
  1813                  return true;
  1814              } //END RB 46111198
  1815              //see if we are ok or not
  1816              if ($result !== true) {
  1817                  //logInfo('FD: '.print_r($result,true));
  1818                  if (isset($result['svperrnum']) && $result['svperrnum'] == '38' && count($history) > 0) { //38 means no more history...not really an error
  1819                      return true;
  1820                  }
  1821                  $history = null;
  1822                  return $result; //we did not get 38 so there is some other type of error
  1823              }
  1824              //have to do this so the oldest blocks of history are first in the array
  1825              $temp = $history;
  1826              $history = array();
  1827              foreach ($new_block as $item)
  1828                  $history[] = $item;
  1829              foreach ($temp as $item)
  1830                  $history[] = $item;
  1831
  1832              $new_block = array();
  1833              //Keep Going!
  1834              $offset = $offset + $step;
  1835          }
  1836                  return true;
  1837          }
  1838
  1839          function _getHistory($parameters, $vdata, $offset, &$history)
  1840          {
  1841          $this->load->library('firstdata');
  1842
  1843                  //$history = array();
  1844                  $this->vdata = &$vdata;
  1845                  $card = $this->vdata['recipient']['card'];
  1846                  if (!$this->_preamble($parameters, $card))
  1847                          return $this->result;
  1848                  $history_code = $this->parameters['history_code'];
  1849                  $cmd = 'Inquire Mystery History';
  1850                  if ($history_code == "2410") // complete but more of a burden on FD's database
  1851                          $cmd = 'Inquire Transaction History';
  1852                  else if ($history_code == "2411") // only reports transactions in the past 6 months
  1853                          $cmd = 'Inquire Recent Transaction History';
  1854                  else if ($history_code == "2413") // not yet tested
  1855                          $cmd = 'Inquire Activation/Reload Transaction History';
  1856                  if (!$this->_req_header($history_code))
  1857                          return $this->result;
  1858                  if (!$this->_pack_field_09())
  1859                          return $this->result;
  1860                  if (!$this->_pack_field_10())
  1861                          return $this->result;
  1862                  if (!$this->_pack_field_12())
  1863                          return $this->result;
  1864                  if (!$this->_pack_field_13())
  1865                          return $this->result;
  1866                  if (!$this->_pack_card($card))
  1867                          return $this->result;
  1868                  if (!$this->_pack_field_F3())
  1869                          return $this->result;
  1870                  if (!$this->_pack_field_42())
  1871                          return $this->result;
  1872                  if (!$this->_pack_field_EA())
  1873                          return $this->result;
  1874                  if (!$this->_pack_field_15())
  1875                          return $this->result;
  1876                  if (!$this->_pack_field_53())
  1877                          return $this->result;
  1878                  if (!$this->_pack_field_44())
  1879                          return $this->result;
  1880                  if (!$this->_pack_field_CE($offset))
  1881                          return $this->result;
  1882                  if (!$this->_pack_field_CF())
  1883                          return $this->result;
  1884                  if (!$this->_pack_field_E8())
  1885                          return $this->result;
  1886                  if (!$this->_req_do($cmd, 1, 0))
  1887                          return $this->result;
  1888                  if (!isset($this->response_field["D0"])) {
  1889                          //$this->_set_garble("No history"); //JC commented out 4-9-2013
  1890                          //return $this->result; //JC commented out 4-9-2013
  1891
  1892                          $h['no_history'] = true;
  1893                          $h['balance'] = $this->response_field["76"] / 100.0;
  1894                          $history[] = $h;
  1895                          return true;
  1896                          //END JC Additions 4-9-2013
  1897                  }
  1898
  1899                  // FIXME: some work may be required here
  1900                  // FirstData data comes back in $this->response_field["D0"] looking something like this:
  1901                  /*
  1902      [D0] => Array (
  1903              [Account status indicator] => 2
  1904              [Detail version format indicator] => 84
  1905              [Base Currency Code] => 840
  1906              [Detail Records] => Array (
  1907                      [0] => Array (
  1908                              [Alternate Merchant Number] => 0
  1909                              [Terminal Number] => 9999
  1910                              [Request Code] => 2102
  1911                              [Amount Sign] => +
  1912                              [Transaction Amount in Base] => 10000
  1913                              [Account Balance in Base] => 10000
  1914                              [Local Lock Amount] => 0
  1915                              [Month] => 8
  1916                              [Day] => 23
  1917                              [Century] => 20
  1918                              [Year] => 12
  1919                              [Hour] => 16
  1920                              [Minutes] => 14
  1921                          )
  1922                      [1] => Array (
  1923                              [Alternate Merchant Number] => 0
  1924                              [Terminal Number] => 9999
  1925                              [Request Code] => 2300
  1926                              [Amount Sign] => +
  1927                              [Transaction Amount in Base] => 2500
  1928                              [Account Balance in Base] => 12500
  1929                              [Local Lock Amount] => 0
  1930                              [Month] => 8
  1931                              [Day] => 23
  1932                              [Century] => 20
  1933                              [Year] => 12
  1934                              [Hour] => 16
  1935                              [Minutes] => 14
  1936                          )
  1937                      [2] => Array (
  1938                              [Alternate Merchant Number] => 0
  1939                              [Terminal Number] => 9999
  1940                              [Request Code] => 2300
  1941                              [Amount Sign] => +
  1942                              [Transaction Amount in Base] => 2500
  1943                              [Account Balance in Base] => 15000
  1944                              [Local Lock Amount] => 0
  1945                              [Month] => 8
  1946                              [Day] => 23
  1947                              [Century] => 20
  1948                              [Year] => 12
  1949                              [Hour] => 16
  1950                              [Minutes] => 14
  1951                          )
  1952                  )
  1953          )
  1954  */
  1955                  // this is what we're trying to produce:
  1956                  // $history = array('purchase_amount' => '0.00', 'purchase_date' => '1/1/1970', 'purchase_store_number' => '0000' );
  1957                  //echo("response_field: <pre>".print_r($this->response_field,1)."</pre>\n"); //pgar
  1958                  //file_put_contents('/tmp/enhanced_bc.txt',print_r($this->response_field,true));
  1959
  1960                  $D0 = $this->response_field["D0"];
  1961                  foreach ($D0['Detail Records'] as $rec) {
  1962                          if (true) //do advanced lookup
  1963                                  {
  1964                      $type = $this->firstdata->historyRequestCodeString($rec['Request Code']);
  1965                      /*
  1966                                          $type = '';
  1967                                          switch ($rec['Request Code']) {
  1968                                                  case 28:
  1969                                                          $type = 'Activation Reloadable';
  1970                                                          break;
  1971                                                  case 18: //Added for Red Robin activate the cards directly through VLBC -- 4-16-2015
  1972                                                  case 100:
  1973                                                  case 101:
  1974                                                  case 102:
  1975                                                  case 103:
  1976                                                  case 104:
  1977                                                  case 2100:
  1978                                                  case 2101:
  1979                                                  case 2102:
  1980                                                  case 2103:
  1981                                                  case 2104:
  1982                                                  case 6100:
  1983                                                  case 6101:
  1984                                                          $type = 'Activation';
  1985                                                          break;
  1986                                                  case 400:
  1987                                                  case 401:
  1988                                                  case 402:
  1989                                                  case 403:
  1990                                                  case 2400:
  1991                                                  case 2401:
  1992                                                          $type = 'Balance Inquiry';
  1993                                                          break;
  1994                                                  case 200:
  1995                                                  case 202:
  1996                                                  case 201:
  1997                                                  case 2201:
  1998                                                  case 2202:
  1999                                                  case 2204:
  2000                                                          $type = 'Redemption';
  2001                                                          break;
  2002                                                  case 300:
  2003                                                  case 301:
  2004                                                  case 2300:
  2005                                                  case 2301:
  2006                                                  case 6300:
  2007                                                  case 6301:
  2008                                                          $type = 'Reload';
  2009                                                          break;
  2010                                                  case 2700:
  2011                                                          $type = 'Refund';
  2012                                                          break;
  2013                                                  case 3460:
  2014                                                  case 460:
  2015                                                          $type = 'Adjustment';
  2016                                                          break;
  2017                                                  case 600:
  2018                                                          $type = 'Cash Out';
  2019                                                          break;
  2020                                                  default: //not sure what the code is here
  2021                                                          $type = 'System (' . $rec['Request Code'] . ')';
  2022                                                          //continue;
  2023                                                          break;
  2024                                          }
  2025                      */
  2026                                          $h['purchase_amount'] = $rec['Transaction Amount in Base'] / 100.0;
  2027                                          $h['purchase_date'] = $rec['Month'] . '/' . $rec['Day'] . '/' . $rec['Century'] . sprintf("%02d", $rec['Year']);
  2028                                          $h['purchase_store_number'] = $rec['Alternate Merchant Number'];
  2029                                          //$h['purchase_store_number'] = $rec['Terminal Number'];
  2030                                          //JC adding history params that have more logical names -- but leaving the old stuff in so it does not kill other stuff
  2031                                          $h['amount'] = money_format('%i', $h['purchase_amount']);
  2032                                          $h['sign'] = $rec['Amount Sign'];
  2033                                          $h['date'] = $h['purchase_date'];
  2034                                          $h['store'] = $h['purchase_store_number'];
  2035                                          $h['type'] = $type;
  2036                                          $h['balance'] = $rec['Account Balance in Base'] / 100.0;
  2037                      $h['request_code'] = $rec['Request Code'];
  2038                                          $history[] = $h;
  2039                                  } else {
  2040                                          if ($rec['Request Code'] != 2201) // Redemption (there are other types too)
  2041                                                  continue;
  2042                                          $h['purchase_amount'] = $rec['Transaction Amount in Base'] / 100.0;
  2043                                          $h['purchase_date'] = $rec['Month'] . '/' . $rec['Day'] . '/' . $rec['Century'] . sprintf("%02d", $rec['Year']);
  2044                                          $h['purchase_store_number'] = $rec['Alternate Merchant Number'];
  2045                                          //$h['purchase_store_number'] = $rec['Terminal Number'];
  2046                                          //JC adding history params that have more logical names -- but leaving the old stuff in so it does not kill other stuff
  2047                                          $h['amount'] = $h['purchase_amount'];
  2048                                          $h['date'] = $h['purchase_date'];
  2049                                          $h['store'] = $h['purchase_store_number'];
  2050                                          $history[] = $h;
  2051                                  }
  2052                  }
  2053                  //echo("history: ".print_r($history,1)."\n"); //pgar
  2054                  return true;
  2055          }
  2056
  2057          function _getStdErr($result)
  2058          {
  2059                  $std_error_map = array(
  2060                          "invalid bin" => "Invalid Card Number", "invalid source_code value" => "Invalid Request", "invalid merchant." => "Invalid Request", "authentication failed" => "Authorization Failed", "42 - http error" => "Authorization Failed"
  2061                  );
  2062
  2063                  if (strlen($result['errmsg'])) {
  2064                                  $std_err = 0;
  2065                                  foreach ($std_error_map as $err_msg => $std_error) {
  2066                                                  if (strpos(strtolower($result['errmsg']), $err_msg) !== false) {
  2067                                                                  $std_err = 1;
  2068                                                                  $result['errmsg'] = $std_error;
  2069                                                          }
  2070                                          }
  2071
  2072                                  if (!$std_err) {
  2073                                                  $result['errmsg'] = "Temporary Error";
  2074                                          }
  2075                          }
  2076
  2077                  return $result;
  2078          }
  2079
  2080          function incBalance($parameters, $card, $amt)
  2081          {
  2082                  if (!$this->_preamble($parameters, $card))
  2083                          return $this->result;
  2084                  $cmd = 'Reload';
  2085                  if (!isset($this->vdata['msg_id']))
  2086                          logInfo("incBalance no msg_id ENG-3117");
  2087
  2088                  if (in_array(CLUSTER, array('dev', 'test'))) {
  2089                                  if (isset($parameters['force_error_percentage']) && $parameters['force_error_percentage'] != 0) {
  2090                                                  $random_number = rand(1, 100);
  2091
  2092                                                  if (is_numeric($parameters['force_error_percentage']) && $parameters['force_error_percentage'] > 0 && $parameters['force_error_percentage'] <= 100) {
  2093                                                                  if ($random_number <= $parameters['force_error_percentage']) {
  2094                                                                                  logError('Reload FORCE error percentage hit!');
  2095                                                                                  return array(
  2096                                                                                          'errmsg' => "TEST: Reload force error hit. Error percent setting: {$parameters['force_error_percentage']}%.",
  2097                                                                                          'errnum' => SVP_ERRNUM_INTERNAL
  2098                                                                                  );
  2099                                                                          }
  2100                                                          } else {
  2101                                                                  logError('FirstData model "force_error_percentage" must be numeric and in the range of 0-100. value: ' . $parameters['force_error_percentage']);
  2102                                                          }
  2103                                          }
  2104
  2105                                  if (isset($parameters['force_error_card_number'])) {
  2106                                                  $error_card_numbers = explode(',', $parameters['force_error_card_number']);
  2107
  2108                                                  if (in_array($card['account_number'], $error_card_numbers)) {
  2109                                                                  logError("Reload FORCE error card number hit!  Card number: {$card['account_number']}");
  2110                                                                  return array(
  2111                                                                          'errmsg' => "TEST: Reload force error card hit. Card number: {$card['account_number']}.",
  2112                                                                          'errnum' => SVP_ERRNUM_INTERNAL
  2113                                                                  );
  2114                                                          }
  2115                                          }
  2116                          }
  2117
  2118                  if (!$this->_req_header(2300))
  2119                          return $this->result;
  2120                  if (!$this->_pack_field_04($amt))
  2121                          return $this->result;
  2122                  if (!$this->_pack_field_09())
  2123                          return $this->result;
  2124                  if (!$this->_pack_field_10())
  2125                          return $this->result;
  2126                  if (!$this->_pack_field_12())
  2127                          return $this->result;
  2128                  if (!$this->_pack_field_13())
  2129                          return $this->result;
  2130                  if (!$this->_pack_card($card))
  2131                          return $this->result;
  2132                  if (!$this->_pack_field_F3())
  2133                          return $this->result;
  2134                  if (!$this->_pack_field_42())
  2135                          return $this->result;
  2136                  if (!$this->_pack_field_EA())
  2137                          return $this->result;
  2138                  if (!$this->_pack_field_15())
  2139                          return $this->result;
  2140                  if (!$this->_pack_field_53())
  2141                          return $this->result;
  2142                  if (!$this->_pack_field_C0())
  2143                          return $this->result;
  2144                  if (!$this->_pack_field_44())
  2145                          return $this->result;
  2146                  if (!$this->_req_do($cmd, 1, 1))
  2147                          return $this->result;
  2148                  return true;
  2149          }
  2150
  2151          /* not tested/certified */
  2152          function decBalance($parameters, $card, $amt)
  2153          {
  2154                  if (!$this->_preamble($parameters, $card))
  2155                          return $this->result;
  2156                  $cmd = 'Redemption Unlock';
  2157                  if (!$this->_req_header(2201))
  2158                          return $this->result;
  2159                  if (!$this->_pack_field_04($amt))
  2160                          return $this->result;
  2161                  if (!$this->_pack_field_09())
  2162                          return $this->result;
  2163                  if (!$this->_pack_field_10())
  2164                          return $this->result;
  2165                  if (!$this->_pack_field_12())
  2166                          return $this->result;
  2167                  if (!$this->_pack_field_13())
  2168                          return $this->result;
  2169                  if (!$this->_pack_card($card))
  2170                          return $this->result;
  2171                  if (!$this->_pack_field_F3())
  2172                          return $this->result;
  2173                  if (!$this->_pack_field_42())
  2174                          return $this->result;
  2175                  if (!$this->_pack_field_EA())
  2176                          return $this->result;
  2177                  if (!$this->_pack_field_15())
  2178                          return $this->result;
  2179                  if (!$this->_pack_field_53())
  2180                          return $this->result;
  2181                  if (!$this->_pack_field_C0())
  2182                          return $this->result;
  2183                  if (!$this->_pack_field_44())
  2184                          return $this->result;
  2185                  if (!$this->_req_do($cmd, 1, 1))
  2186                          return $this->result;
  2187                  return true;
  2188          }
  2189
  2190          function _activateVirtualCard(&$card, $amt)
  2191          {
  2192                  $cmd = "Activate Virtual Card";
  2193                  if (!$this->_req_header(2102))
  2194                          return $this->result;
  2195                  if (!$this->_pack_field_04($amt))
  2196                          return $this->result;
  2197                  if (!$this->_pack_field_09())
  2198                          return $this->result;
  2199                  if (!$this->_pack_field_10())
  2200                          return $this->result;
  2201                  if (!$this->_pack_field_12())
  2202                          return $this->result;
  2203                  if (!$this->_pack_field_13())
  2204                          return $this->result;
  2205                  if (!$this->_pack_field_42())
  2206                          return $this->result;
  2207                  if (!$this->_pack_field_F3())
  2208                          return $this->result;
  2209                  if (!$this->_pack_field_EA())
  2210                          return $this->result;
  2211                  if (!$this->_pack_field_F2())
  2212                          return $this->result;
  2213                  if (!$this->_pack_field_15())
  2214                          return $this->result;
  2215                  if (!$this->_pack_field_53())
  2216                          return $this->result;
  2217                  if (!$this->_pack_field_C0())
  2218                          return $this->result;
  2219                  if (!$this->_pack_field_44())
  2220                          return $this->result;
  2221                  if (!$this->_req_do($cmd, 1, 1))
  2222                          return $this->result;
  2223                  if (!isset($this->response_field["70"])) {
  2224                          $this->_set_garble("No card number");
  2225                          return $this->result;
  2226                  }
  2227                  //echo "Response: <pre>" . print_r($this->response_field, TRUE) . "</pre>";
  2228                  $card['account_number'] = $this->response_field["70"];
  2229                  if (isset($this->response_field["34"])) {
  2230                          // decrypt EAN if present
  2231                          $ean = $this->response_field["34"];
  2232                          $ean = trim($ean);
  2233                          if (!empty($ean)) {
  2234                                  $cmd = "firstdata_crypt decrypt_ean " . $this->parameters['mwk'] . " " . $ean;
  2235                                  exec($cmd, $outraw, $status);
  2236                                  $output = join("\n", $outraw);
  2237                                  if ($status != 0) {
  2238                                          $msg = "EAN decryption failed ($cmd): " . $output;
  2239                                          $this->result['errmsg'] = $msg;
  2240                                          $this->result['errnum'] = SVP_ERRNUM_INTERNAL;
  2241                                          return $this->result;
  2242                                  }
  2243                                  $card['reference_number'] = $card['pin'] = trim($output);
  2244                                  logInfo('Activated with First Data, PIN: ' . $card['reference_number']);
  2245                          }
  2246                  }
  2247
  2248                  if (isset($this->response_field['A0'])) {
  2249                                  $expiration_year = substr($this->response_field['A0'], 4);
  2250                                  if ($expiration_year < 3000) {
  2251                                                  $card['extra_data'] = http_build_query(array(
  2252                                                          'ExpirationDate'     => $this->response_field['A0']
  2253                                                  ));
  2254                                          }
  2255                          }
  2256
  2257                  if ($this->parameters["cardextra"]) {
  2258                                  $card["card_extra"] = array();
  2259                                  $card["card_extra"]["txn_auth_code"] = $this->response_field["38"];
  2260                                  $card["card_extra"]["txn_resp_code"] = $this->response_field["39"];
  2261                                  $card["card_extra"]["txn_req_code"] = "2102";
  2262                                  //logInfo("card record with extra data is ".print_r($card,TRUE));
  2263                          }
  2264                  return true;
  2265          }
  2266
  2267          function _activatePhysicalCard($card, $amt)
  2268          {
  2269                  $cmd = "Activate Physical Card";
  2270                  if (!$this->_req_header(2104))
  2271                          return $this->result;
  2272                  if (!$this->_pack_field_04($amt))
  2273                          return $this->result;
  2274                  if (!$this->_pack_field_09())
  2275                          return $this->result;
  2276                  if (!$this->_pack_field_10())
  2277                          return $this->result;
  2278                  if (!$this->_pack_field_12())
  2279                          return $this->result;
  2280                  if (!$this->_pack_field_13())
  2281                          return $this->result;
  2282                  if (!$this->_pack_card($card))
  2283                          return $this->result;
  2284                  if (!$this->_pack_field_F3())
  2285                          return $this->result;
  2286                  if (!$this->_pack_field_42())
  2287                          return $this->result;
  2288                  if (!$this->_pack_field_EA())
  2289                          return $this->result;
  2290                  if (!$this->_pack_field_15())
  2291                          return $this->result;
  2292                  if (!$this->_pack_field_53())
  2293                          return $this->result;
  2294                  if (!$this->_pack_field_C0())
  2295                          return $this->result;
  2296                  if (!$this->_pack_field_44())
  2297                          return $this->result;
  2298                  if ($this->parameters['promotion_code'] != "0")
  2299                          if (!$this->_pack_field_F2())
  2300                                  return $this->result;
  2301                  if (!$this->_req_do($cmd, 1, 1))
  2302                          return $this->result;
  2303
  2304                  if (isset($this->response_field['A0'])) {
  2305                                  $expiration_year = substr($this->response_field['A0'], 4);
  2306                                  if ($expiration_year < 3000) {
  2307                                                  $card['extra_data'] = http_build_query(array(
  2308                                                          'ExpirationDate'     => $this->response_field['A0']
  2309                                                  ));
  2310                                          }
  2311                          }
  2312
  2313                  if ($this->parameters["cardextra"]) {
  2314                                  $card["card_extra"] = array();
  2315                                  $card["card_extra"]["txn_auth_code"] = $this->response_field["38"];
  2316                                  $card["card_extra"]["txn_resp_code"] = $this->response_field["39"];
  2317                                  $card["card_extra"]["txn_req_code"] = "2104";
  2318                          }
  2319                  return true;
  2320          }
  2321
  2322          function activateCard($parameters, &$card, $amt)
  2323          {
  2324                  if (!empty($parameters['activation_code'])) {
  2325                          switch ($parameters['activation_code']) {
  2326                                  case "2101":
  2327                                          return $this->t_2101($parameters, $card, $amt);
  2328                                  case "2107":
  2329                                          return $this->t_2107($parameters, $card, $amt);
  2330                                  case "2108":
  2331                                          return $this->t_2108($parameters, $card, $amt);
  2332                          }
  2333                  }
  2334                  if (!$this->_preamble($parameters, $card))
  2335                          return $this->result;
  2336                  if (!isset($this->vdata['msg_id']))
  2337                          logInfo("activateCard no msg_id ENG-3117");
  2338                  if (empty($card['account_number']))
  2339                          return $this->_activateVirtualCard($card, $amt);
  2340                  return $this->_activatePhysicalCard($card, $amt);
  2341          }
  2342
  2343          function voidCard($parameters, $card, $amt)
  2344          {
  2345                  $local_result = $this->voidActivation($parameters, $card, $amt);
  2346                  if ($local_result !== true)
  2347                          return $this->cashoutCard($parameters, $card);
  2348                  else
  2349                          return $local_result;
  2350          }
  2351
  2352          function cashoutCard($parameters, $card)
  2353          {
  2354                  if (!$this->_preamble($parameters, $card))
  2355                          return $this->result;
  2356                  $cmd = "Cash Out";
  2357                  if (!$this->_req_header(2600))
  2358                          return $this->result;
  2359                  if (!$this->_pack_field_09())
  2360                          return $this->result;
  2361                  if (!$this->_pack_field_10())
  2362                          return $this->result;
  2363                  if (!$this->_pack_field_12())
  2364                          return $this->result;
  2365                  if (!$this->_pack_field_13())
  2366                          return $this->result;
  2367                  if (!$this->_pack_card($card))
  2368                          return $this->result;
  2369                  if (!$this->_pack_field_F3())
  2370                          return $this->result;
  2371                  if (!$this->_pack_field_42())
  2372                          return $this->result;
  2373                  if (!$this->_pack_field_EA())
  2374                          return $this->result;
  2375                  if (!$this->_pack_field_15())
  2376                          return $this->result;
  2377                  if (!$this->_pack_field_53())
  2378                          return $this->result;
  2379                  if (!$this->_pack_field_C0())
  2380                          return $this->result;
  2381                  if (!$this->_pack_field_44())
  2382                          return $this->result;
  2383                  if (!$this->_req_do($cmd, 1, 1))
  2384                          return $this->result;
  2385                  return true;
  2386          }
  2387
  2388          function voidRedemption($parameters, $card, $amt)
  2389          {
  2390                  if (!$this->_preamble($parameters, $card))
  2391                          return $this->result;
  2392                  $cmd = "Void Of Redemption";
  2393                  if (!$this->_req_header(2800))
  2394                          return $this->result;
  2395                  if (!$this->_pack_field_04($amt))
  2396                          return $this->result;
  2397                  if (!$this->_pack_field_09())
  2398                          return $this->result;
  2399                  if (!$this->_pack_field_10())
  2400                          return $this->result;
  2401                  if (!$this->_pack_field_12())
  2402                          return $this->result;
  2403                  if (!$this->_pack_field_13())
  2404                          return $this->result;
  2405                  if (!$this->_pack_card($card))
  2406                          return $this->result;
  2407                  if (!$this->_pack_field_F3())
  2408                          return $this->result;
  2409                  if (!$this->_pack_field_42())
  2410                          return $this->result;
  2411                  if (!$this->_pack_field_EA())
  2412                          return $this->result;
  2413                  if (!$this->_pack_field_15())
  2414                          return $this->result;
  2415                  if (!$this->_pack_field_53())
  2416                          return $this->result;
  2417                  if (!$this->_pack_field_C0())
  2418                          return $this->result;
  2419                  if (!$this->_pack_field_44())
  2420                          return $this->result;
  2421                  if (!$this->_req_do($cmd, 1, 1))
  2422                          return $this->result;
  2423                  return true;
  2424          }
  2425
  2426          function voidActivation($parameters, $card, $amt)
  2427          {
  2428                  if (!$this->_preamble($parameters, $card))
  2429                          return $this->result;
  2430                  $cmd = "Void Of Activation";
  2431                  if (!$this->_req_header(2802))
  2432                          return $this->result;
  2433                  if (!$this->_pack_field_04($amt))
  2434                          return $this->result;
  2435                  if (!$this->_pack_field_09())
  2436                          return $this->result;
  2437                  if (!$this->_pack_field_10())
  2438                          return $this->result;
  2439                  if (!$this->_pack_field_12())
  2440                          return $this->result;
  2441                  if (!$this->_pack_field_13())
  2442                          return $this->result;
  2443                  if (!$this->_pack_card($card))
  2444                          return $this->result;
  2445                  if (!$this->_pack_field_F3())
  2446                          return $this->result;
  2447                  if (!$this->_pack_field_42())
  2448                          return $this->result;
  2449                  if (!$this->_pack_field_EA())
  2450                          return $this->result;
  2451                  if (!$this->_pack_field_15())
  2452                          return $this->result;
  2453                  if (!$this->_pack_field_53())
  2454                          return $this->result;
  2455                  if (!$this->_pack_field_C0())
  2456                          return $this->result;
  2457                  if (!$this->_pack_field_44())
  2458                          return $this->result;
  2459                  if (!$this->_req_do($cmd, 1, 1))
  2460                          return $this->result;
  2461                  return true;
  2462          }
  2463
  2464          function reverseCard($parameters, $card, $amt)
  2465          {
  2466                  return $this->voidActivation($parameters, $card, $amt);
  2467          }
  2468
  2469          // copied, not tested
  2470          function deactivateCard($card_id, $msg_id = null)
  2471          {
  2472                  //send email with information that card must be deactivated
  2473                  $this->load->library('email');
  2474                  $this->email->from('do-not-reply@wgiftcard.com', 'TW Card Management');
  2475                  $this->email->to('nchammas@gmail.com, codesjones@gmail.com');
  2476                  //$this->email->cc('nchammas@gmail.com, codesjones@gmail.com');
  2477
  2478                  //check if test server
  2479                  //CLUSTERFIX
  2480                  $test_server = (trim(file_get_contents("/var/tw/cluster")) == 'test') ? ' (Test Server)' : null;
  2481                  $this->email->subject('Card management necessary ' . $test_server);
  2482                  $message = 'Card ID: ' . $card_id . ' needs to be deactivated.  The SVP is Accent.';
  2483                  $this->email->message($message);
  2484                  $this->email->send();
  2485
  2486                  if (!is_null($msg_id)) {
  2487                                  $this->load->library('msg_library');
  2488                                  //create message event log
  2489                                  $info = 'Card deactivation email sent for card id: ' . $card_id . '.';
  2490                                  $this->msg_library->event_timestamp($msg_id, MSG_EVENT_TYPE_CARD_DEACTIVATION_EMAIL_SENT, null, $info);
  2491                          }
  2492          }
  2493
  2494          function workingKey($parameters)
  2495          {
  2496                  $card = array();
  2497                  if (!$this->_preamble($parameters, $card))
  2498                          return $this->result;
  2499                  $cmd = 'Assign Merchant Working Key';
  2500                  if (!$this->_req_header(2010))
  2501                          return $this->result;
  2502                  if (!$this->_pack_field_09())
  2503                          return $this->result;
  2504                  if (!$this->_pack_field_10())
  2505                          return $this->result;
  2506                  if (!$this->_pack_field_12())
  2507                          return $this->result;
  2508                  if (!$this->_pack_field_13())
  2509                          return $this->result;
  2510                  if (!$this->_pack_field_42())
  2511                          return $this->result;
  2512                  if (!$this->_pack_field_63())
  2513                          return $this->result;
  2514                  if (!$this->_pack_field_EA())
  2515                          return $this->result;
  2516                  if (!$this->_pack_field_F3())
  2517                          return $this->result;
  2518                  if (!$this->_pack_field_15())
  2519                          return $this->result;
  2520                  if (!$this->_pack_field_53())
  2521                          return $this->result;
  2522                  if (!$this->_pack_field_44())
  2523                          return $this->result;
  2524                  if (!$this->_req_do($cmd, 1, 0))
  2525                          return $this->result;
  2526                  return true;
  2527          }
  2528
  2529          function voidReload($parameters, $card, $amt)
  2530          {
  2531                  if (!$this->_preamble($parameters, $card))
  2532                          return $this->result;
  2533                  $cmd = "Void Of Reload/Refund";
  2534                  if (!$this->_req_header(2801))
  2535                          return $this->result;
  2536                  if (!$this->_pack_field_04($amt))
  2537                          return $this->result;
  2538                  if (!$this->_pack_field_09())
  2539                          return $this->result;
  2540                  if (!$this->_pack_field_10())
  2541                          return $this->result;
  2542                  if (!$this->_pack_field_12())
  2543                          return $this->result;
  2544                  if (!$this->_pack_field_13())
  2545                          return $this->result;
  2546                  if (!$this->_pack_card($card))
  2547                          return $this->result;
  2548                  if (!$this->_pack_field_F3())
  2549                          return $this->result;
  2550                  if (!$this->_pack_field_42())
  2551                          return $this->result;
  2552                  if (!$this->_pack_field_EA())
  2553                          return $this->result;
  2554                  if (!$this->_pack_field_15())
  2555                          return $this->result;
  2556                  if (!$this->_pack_field_53())
  2557                          return $this->result;
  2558                  if (!$this->_pack_field_C0())
  2559                          return $this->result;
  2560                  if (!$this->_pack_field_44())
  2561                          return $this->result;
  2562                  if (!$this->_req_do($cmd, 1, 1))
  2563                          return $this->result;
  2564                  return true;
  2565          }
  2566
  2567          function reverseInc($parameters, $card, $amt)
  2568          {
  2569                  return $this->voidReload($parameters, $card, $amt);
  2570          }
  2571
  2572          function test_call()
  2573          {
  2574                  echo __FUNCTION__ . "\n";
  2575          }
  2576
  2577          // Agg17 testing
  2578          // 2107 Virtual Card Activation with SCV and Provisioning
  2579          // 2108 (EAN) Virtual Card Activation with Provisioning
  2580          // 2301 Reload Merchant Specific
  2581          // 2495 Mobile Wallet Provisioning
  2582          // 2496 Mobile Wallet Remove Provisioning
  2583          // 2202 Redeem No NSF (should already be certified to corresponding void 2800)
  2584          // 2408 Inquire Multi-Lock
  2585          // 2208 Redeem Unlock
  2586          // 2808 Void Redeem Multi Lock
  2587
  2588          function t_2107($parameters, &$card, $amt)
  2589          {
  2590                  $tcode = 2107;
  2591                  $cmd = $tcode . " SCV Virtual Activation";
  2592                  if (!$this->_preamble($parameters, $card))
  2593                          return $this->result;
  2594                  if (!$this->_req_header($tcode))
  2595                          return $this->result;
  2596
  2597                  // required
  2598                  if (!$this->_pack_field_12())
  2599                          return $this->result;
  2600                  if (!$this->_pack_field_13())
  2601                          return $this->result;
  2602                  if (!$this->_pack_field_42())
  2603                          return $this->result;
  2604                  if (!$this->_pack_field_EA())
  2605                          return $this->result;
  2606                  if (!$this->_pack_field_F2())
  2607                          return $this->result;
  2608
  2609                  // suggested
  2610                  if (!$this->_pack_field_15())
  2611                          return $this->result;
  2612                  if (!$this->_pack_field_53())
  2613                          return $this->result;
  2614
  2615                  // optional
  2616                  if (!$this->_pack_field_04($amt))
  2617                          return $this->result;
  2618                  if (!$this->_pack_field_09())
  2619                          return $this->result;
  2620                  if (!$this->_pack_field_10())
  2621                          return $this->result;
  2622                  if (!$this->_pack_field_44())
  2623                          return $this->result;
  2624                  if (!$this->_pack_field_C0())
  2625                          return $this->result;
  2626
  2627                  if (!$this->_req_do($cmd, 1, 1))
  2628                          return $this->result;
  2629                  if (!isset($this->response_field["70"])) {
  2630                          $this->_set_garble("No card number");
  2631                          return $this->result;
  2632                  }
  2633                  $card['account_number'] = $this->response_field["70"];
  2634                  $card['reference_number'] = $card['pin'] = $this->response_field["32"];
  2635                  if ($this->parameters["cardextra"]) {
  2636                          $card["card_extra"] = array();
  2637                          $card["card_extra"]["track_ii"] = $this->response_field["35"];
  2638                          $card["card_extra"]["txn_auth_code"] = $this->response_field["38"];
  2639                          $card["card_extra"]["txn_resp_code"] = $this->response_field["39"];
  2640                          $card["card_extra"]["txn_req_code"] = (string)$tcode;
  2641                  }
  2642                  return true;
  2643          }
  2644
  2645          function t_2108($parameters, &$card, $amt)
  2646          {
  2647                  $tcode = 2108;
  2648                  $cmd = $tcode . " EAN Virtual Activation";
  2649                  if (!$this->_preamble($parameters, $card))
  2650                          return $this->result;
  2651                  if (!$this->_req_header($tcode))
  2652                          return $this->result;
  2653
  2654                  // required
  2655                  if (!$this->_pack_field_12())
  2656                          return $this->result;
  2657                  if (!$this->_pack_field_13())
  2658                          return $this->result;
  2659                  if (!$this->_pack_field_42())
  2660                          return $this->result;
  2661                  if (!$this->_pack_field_EA())
  2662                          return $this->result;
  2663                  if (!$this->_pack_field_F2())
  2664                          return $this->result;
  2665
  2666                  // suggested
  2667                  if (!$this->_pack_field_15())
  2668                          return $this->result;
  2669                  if (!$this->_pack_field_53())
  2670                          return $this->result;
  2671
  2672                  // optional
  2673                  if (!$this->_pack_field_04($amt))
  2674                          return $this->result;
  2675                  if (!$this->_pack_field_09())
  2676                          return $this->result;
  2677                  if (!$this->_pack_field_10())
  2678                          return $this->result;
  2679                  if (!$this->_pack_field_44())
  2680                          return $this->result;
  2681                  if (!$this->_pack_field_C0())
  2682                          return $this->result;
  2683                  if (!$this->_pack_field_F3())
  2684                          return $this->result;
  2685
  2686                  if (!$this->_req_do($cmd, 1, 1))
  2687                          return $this->result;
  2688                  if (!isset($this->response_field["70"])) {
  2689                          $this->_set_garble("No card number");
  2690                          return $this->result;
  2691                  }
  2692                  $card['account_number'] = $this->response_field["70"];
  2693                  if (isset($this->response_field["34"])) {
  2694                          // decrypt EAN if present
  2695                          $ean = $this->response_field["34"];
  2696                          $ean = trim($ean);
  2697                          if (!empty($ean)) {
  2698                                  $cmd = "firstdata_crypt decrypt_ean " . $this->parameters['mwk'] . " " . $ean;
  2699                                  exec($cmd, $outraw, $status);
  2700                                  $output = join("\n", $outraw);
  2701                                  if ($status != 0) {
  2702                                          $msg = "EAN decryption failed ($cmd): " . $output;
  2703                                          $this->result['errmsg'] = $msg;
  2704                                          $this->result['errnum'] = SVP_ERRNUM_INTERNAL;
  2705                                          return $this->result;
  2706                                  }
  2707                                  $card['reference_number'] = $card['pin'] = trim($output);
  2708                                  //logInfo('Activated with First Data, PIN: '.$card['reference_number']);
  2709                          }
  2710                  }
  2711                  if ($this->parameters["cardextra"]) {
  2712                          $card["card_extra"] = array();
  2713                          $card["card_extra"]["track_ii"] = $this->response_field["35"];
  2714                          $card["card_extra"]["txn_auth_code"] = $this->response_field["38"];
  2715                          $card["card_extra"]["txn_resp_code"] = $this->response_field["39"];
  2716                          $card["card_extra"]["txn_req_code"] = (string)$tcode;
  2717                  }
  2718                  return true;
  2719          }
  2720
  2721          function t_2301($parameters, $card, $amt)
  2722          {
  2723                  $tcode = 2301;
  2724                  $cmd = $tcode . " Reload Merchant Specific";
  2725                  if (!$this->_preamble($parameters, $card))
  2726                          return $this->result;
  2727                  if (!$this->_req_header($tcode))
  2728                          return $this->result;
  2729
  2730                  // required
  2731                  if (!$this->_pack_field_04($amt))
  2732                          return $this->result;
  2733                  if (!$this->_pack_field_12())
  2734                          return $this->result;
  2735                  if (!$this->_pack_field_13())
  2736                          return $this->result;
  2737                  if (!$this->_pack_card($card))
  2738                          return $this->result;
  2739                  if (!$this->_pack_field_42())
  2740                          return $this->result;
  2741                  if (!$this->_pack_field_EA())
  2742                          return $this->result;
  2743
  2744                  // suggested
  2745                  if (!$this->_pack_field_15())
  2746                          return $this->result;
  2747                  if (!$this->_pack_field_53())
  2748                          return $this->result;
  2749                  if (!$this->_pack_field_C0())
  2750                          return $this->result;
  2751
  2752                  // optional
  2753                  if (!$this->_pack_field_09())
  2754                          return $this->result;
  2755                  if (!$this->_pack_field_10())
  2756                          return $this->result;
  2757                  if (!$this->_pack_field_44())
  2758                          return $this->result;
  2759                  if (!$this->_pack_field_F3())
  2760                          return $this->result;
  2761
  2762                  if (!$this->_req_do($cmd, 1, 1))
  2763                          return $this->result;
  2764                  return true;
  2765          }
  2766
  2767          /* Note:
  2768           * Using source_code=31 (field EA, no EAN) with transaction type 2495 will produce error 12, "Invalid transaction format..."
  2769           * Use force_pin="00000000" (SCV, field 32) to suppress this error.
  2770           */
  2771          function provisionCard($parameters, &$card, &$track_ii)
  2772          {
  2773                  $tcode = 2495;
  2774                  $cmd = $tcode . " Mobile Wallet Provisioning";
  2775                  if (!$this->_preamble($parameters, $card))
  2776                          return $this->result;
  2777                  if (!$this->_req_header($tcode))
  2778                          return $this->result;
  2779
  2780                  // required
  2781                  if (!$this->_pack_field_12())
  2782                          return $this->result;
  2783                  if (!$this->_pack_field_13())
  2784                          return $this->result;
  2785                  if (!$this->_pack_field_42())
  2786                          return $this->result;
  2787                  if (!$this->_pack_card($card))
  2788                          return $this->result;
  2789
  2790                  // special case - ensure pin present (EAN/SCV), as per discussion w/Paul Dattilo 7 June 2016
  2791                  if (!isset($this->field_table[0x32]) && !isset($this->field_table[0x34])) {
  2792                          $this->result['errmsg'] = 'Provisioning must use a pin';
  2793                          $this->result['errnum'] = SVP_ERRNUM_GARBLE;
  2794                          return $this->result;
  2795                  }
  2796
  2797                  // suggested
  2798                  if (!$this->_pack_field_15())
  2799                          return $this->result;
  2800                  if (!$this->_pack_field_53())
  2801                          return $this->result;
  2802
  2803                  // optional
  2804                  if (!$this->_pack_field_09())
  2805                          return $this->result;
  2806                  if (!$this->_pack_field_10())
  2807                          return $this->result;
  2808                  if (!$this->_pack_field_44())
  2809                          return $this->result;
  2810                  if (!$this->_pack_field_C0())
  2811                          return $this->result;
  2812                  if (!$this->_pack_field_EA())
  2813                          return $this->result;
  2814                  if (!$this->_pack_field_F3())
  2815                          return $this->result;
  2816
  2817                  if (!$this->_req_do_simple_client_id($cmd, 1, 1))
  2818                          return $this->result;
  2819
  2820                  if ($this->response_field["39"] === "00" && !empty($this->response_field["35"])) {
  2821                                  $track_ii = $this->response_field["35"];
  2822                                  if ($this->parameters["cardextra"]) {
  2823                                          $card["card_extra"] = array();
  2824                                          $card["card_extra"]["txn_auth_code"] = $this->response_field["38"];
  2825                                          $card["card_extra"]["txn_resp_code"] = $this->response_field["39"];
  2826                                          $card["card_extra"]["txn_req_code"] = (string)$tcode;
  2827                                  }
  2828                                  return true;
  2829                          } else {
  2830                                  $this->result['errnum'] = $this->response_field["39"];
  2831                                  $this->result['errmsg'] = $this->_svdot_errmsg($this->response_field["39"]);
  2832                                  return $this->result;
  2833                          }
  2834          }
  2835
  2836          function unprovisionCard($parameters, $card)
  2837          {
  2838                  $tcode = 2496;
  2839                  $cmd = $tcode . " Mobile Wallet Remove Provisioning";
  2840                  if (!$this->_preamble($parameters, $card))
  2841                          return $this->result;
  2842                  if (!$this->_req_header($tcode))
  2843                          return $this->result;
  2844
  2845                  // required
  2846                  if (!$this->_pack_field_12())
  2847                          return $this->result;
  2848                  if (!$this->_pack_field_13())
  2849                          return $this->result;
  2850                  if (!$this->_pack_field_42())
  2851                          return $this->result;
  2852                  if (!$this->_pack_card($card))
  2853                          return $this->result;
  2854
  2855                  // suggested
  2856                  if (!$this->_pack_field_15())
  2857                          return $this->result;
  2858                  if (!$this->_pack_field_53())
  2859                          return $this->result;
  2860
  2861                  // optional
  2862                  if (!$this->_pack_field_09())
  2863                          return $this->result;
  2864                  if (!$this->_pack_field_10())
  2865                          return $this->result;
  2866                  if (!$this->_pack_field_44())
  2867                          return $this->result;
  2868                  if (!$this->_pack_field_C0())
  2869                          return $this->result;
  2870                  if (!$this->_pack_field_EA())
  2871                          return $this->result;
  2872                  if (!$this->_pack_field_F3())
  2873                          return $this->result;
  2874
  2875                  // 2496 response does not include F7, can't check it
  2876                  //if (!$this->_req_do_simple_client_id($cmd, 1, 1))
  2877                  if (!$this->_req_do($cmd, 1, 0))
  2878                          return $this->result;
  2879
  2880                  if ($this->response_field["39"] === "00") {
  2881                                  return true;
  2882                          } else {
  2883                                  $this->result['errnum'] = $this->response_field["39"];
  2884                                  $this->result['errmsg'] = $this->_svdot_errmsg($this->response_field["39"]);
  2885                                  return $this->result;
  2886                          }
  2887          }
  2888
  2889          // returns amount taken by redemption
  2890          function t_2202($parameters, &$card, &$amt)
  2891          {
  2892                  $tcode = 2202;
  2893                  $cmd = $tcode . " Redemption, NSF";
  2894                  if (!$this->_preamble($parameters, $card))
  2895                          return $this->result;
  2896                  if (!$this->_req_header($tcode))
  2897                          return $this->result;
  2898
  2899                  // required
  2900                  if (!$this->_pack_field_04($amt))
  2901                          return $this->result;
  2902                  if (!$this->_pack_field_12())
  2903                          return $this->result;
  2904                  if (!$this->_pack_field_13())
  2905                          return $this->result;
  2906                  if (!$this->_pack_card($card))
  2907                          return $this->result;
  2908                  if (!$this->_pack_field_42())
  2909                          return $this->result;
  2910                  if (!$this->_pack_field_EA())
  2911                          return $this->result;
  2912
  2913                  // suggested
  2914                  if (!$this->_pack_field_15())
  2915                          return $this->result;
  2916                  if (!$this->_pack_field_53())
  2917                          return $this->result;
  2918                  if (!$this->_pack_field_C0())
  2919                          return $this->result;
  2920
  2921                  // optional
  2922                  if (!$this->_pack_field_09())
  2923                          return $this->result;
  2924                  if (!$this->_pack_field_10())
  2925                          return $this->result;
  2926                  if (!$this->_pack_field_44())
  2927                          return $this->result;
  2928                  if (!$this->_pack_field_F3())
  2929                          return $this->result;
  2930
  2931                  if (!$this->_req_do($cmd, 1, 1))
  2932                          return $this->result;
  2933                  $previous_balance = $this->response_field["75"];
  2934                  $new_balance = $this->response_field["76"];
  2935                  $taken = $previous_balance - $new_balance;
  2936                  $amt = $taken / 100.0;
  2937                  if ($this->parameters["cardextra"]) {
  2938                          $card["card_extra"] = array();
  2939                          $card["card_extra"]["txn_auth_code"] = $this->response_field["38"];
  2940                          $card["card_extra"]["txn_resp_code"] = $this->response_field["39"];
  2941                          $card["balance"] = $new_balance;
  2942                          $card["card_extra"]["txn_req_code"] = (string)$tcode;
  2943                  }
  2944                  return true;
  2945          }
  2946
  2947          // returns $lockid to be passed to 2808
  2948          function t_2408($parameters, &$card, $amt, &$lockid)
  2949          {
  2950                  $tcode = 2408;
  2951                  $cmd = $tcode . " Inquire Current Balance w/Multi-Lock";
  2952                  if (!$this->_preamble($parameters, $card))
  2953                          return $this->result;
  2954                  if (!$this->_req_header($tcode))
  2955                          return $this->result;
  2956
  2957                  // required
  2958                  if (!$this->_pack_field_12())
  2959                          return $this->result;
  2960                  if (!$this->_pack_field_13())
  2961                          return $this->result;
  2962                  if (!$this->_pack_card($card))
  2963                          return $this->result;
  2964                  if (!$this->_pack_field_42())
  2965                          return $this->result;
  2966                  if (!$this->_pack_field_EA())
  2967                          return $this->result;
  2968
  2969                  // suggested
  2970                  if (!$this->_pack_field_04($amt))
  2971                          return $this->result;
  2972                  if (!$this->_pack_field_15())
  2973                          return $this->result;
  2974                  if (!$this->_pack_field_53())
  2975                          return $this->result;
  2976                  if (!$this->_pack_field_C0())
  2977                          return $this->result;
  2978
  2979                  // optional
  2980                  if (!$this->_pack_field_09())
  2981                          return $this->result;
  2982                  if (!$this->_pack_field_10())
  2983                          return $this->result;
  2984                  if (!$this->_pack_field_44())
  2985                          return $this->result;
  2986                  if (!$this->_pack_field_F3())
  2987                          return $this->result;
  2988
  2989                  if (!$this->_req_do($cmd, 1, 1))
  2990                          return $this->result;
  2991                  $lockid = $this->response_field["79"];
  2992                  if ($this->parameters["cardextra"]) {
  2993                          $card["card_extra"] = array();
  2994                          $card["card_extra"]["txn_auth_code"] = $this->response_field["38"];
  2995                          $card["card_extra"]["txn_resp_code"] = $this->response_field["39"];
  2996                          $card["card_extra"]["txn_req_code"] = (string)$tcode;
  2997                  }
  2998                  return true;
  2999          }
  3000
  3001          function t_2208($parameters, &$card, $amt, $lockid)
  3002          {
  3003                  $tcode = 2208;
  3004                  $cmd = $tcode . " Redemption Unlock w/Multi-Lock";
  3005                  if (!$this->_preamble($parameters, $card))
  3006                          return $this->result;
  3007                  if (!$this->_req_header($tcode))
  3008                          return $this->result;
  3009
  3010                  // required
  3011                  if (!$this->_pack_field_04($amt))
  3012                          return $this->result;
  3013                  if (!$this->_pack_field_12())
  3014                          return $this->result;
  3015                  if (!$this->_pack_field_13())
  3016                          return $this->result;
  3017                  if (!$this->_pack_card($card))
  3018                          return $this->result;
  3019                  if (!$this->_pack_field_42())
  3020                          return $this->result;
  3021                  if (!$this->_pack_field_79($lockid))
  3022                          return $this->result;
  3023                  if (!$this->_pack_field_EA())
  3024                          return $this->result;
  3025
  3026                  // suggested
  3027                  if (!$this->_pack_field_15())
  3028                          return $this->result;
  3029                  if (!$this->_pack_field_53())
  3030                          return $this->result;
  3031                  if (!$this->_pack_field_C0())
  3032                          return $this->result;
  3033
  3034                  // optional
  3035                  if (!$this->_pack_field_09())
  3036                          return $this->result;
  3037                  if (!$this->_pack_field_10())
  3038                          return $this->result;
  3039                  if (!$this->_pack_field_44())
  3040                          return $this->result;
  3041                  if (!$this->_pack_field_F3())
  3042                          return $this->result;
  3043
  3044                  if (!$this->_req_do($cmd, 1, 1))
  3045                          return $this->result;
  3046                  if ($this->parameters["cardextra"]) {
  3047                          $card["card_extra"] = array();
  3048                          $card["card_extra"]["txn_auth_code"] = $this->response_field["38"];
  3049                          $card["card_extra"]["txn_resp_code"] = $this->response_field["39"];
  3050                          $card["card_extra"]["txn_req_code"] = (string)$tcode;
  3051                  }
  3052                  return true;
  3053          }
  3054
  3055          function t_2808($parameters, &$card, $amt, $lockid)
  3056          {
  3057                  $tcode = 2808;
  3058                  $cmd = $tcode . " Void Of Redemption w/Multi-Lock";
  3059                  if (!$this->_preamble($parameters, $card))
  3060                          return $this->result;
  3061                  if (!$this->_req_header($tcode))
  3062                          return $this->result;
  3063
  3064                  // required
  3065                  if (!$this->_pack_field_04($amt))
  3066                          return $this->result;
  3067                  if (!$this->_pack_field_12())
  3068                          return $this->result;
  3069                  if (!$this->_pack_field_13())
  3070                          return $this->result;
  3071                  if (!$this->_pack_card($card))
  3072                          return $this->result;
  3073                  if (!$this->_pack_field_42())
  3074                          return $this->result;
  3075                  if (!$this->_pack_field_79($lockid))
  3076                          return $this->result;
  3077                  if (!$this->_pack_field_EA())
  3078                          return $this->result;
  3079
  3080                  // suggested
  3081                  if (!$this->_pack_field_15())
  3082                          return $this->result;
  3083                  if (!$this->_pack_field_53())
  3084                          return $this->result;
  3085                  if (!$this->_pack_field_C0())
  3086                          return $this->result;
  3087
  3088                  // optional
  3089                  if (!$this->_pack_field_09())
  3090                          return $this->result;
  3091                  if (!$this->_pack_field_10())
  3092                          return $this->result;
  3093                  if (!$this->_pack_field_44())
  3094                          return $this->result;
  3095                  if (!$this->_pack_field_F3())
  3096                          return $this->result;
  3097
  3098                  if (!$this->_req_do($cmd, 1, 1))
  3099                          return $this->result;
  3100                  if ($this->parameters["cardextra"]) {
  3101                          $card["card_extra"] = array();
  3102                          $card["card_extra"]["txn_auth_code"] = $this->response_field["38"];
  3103                          $card["card_extra"]["txn_resp_code"] = $this->response_field["39"];
  3104                          $card["card_extra"]["txn_req_code"] = (string)$tcode;
  3105                  }
  3106                  return true;
  3107          }
  3108
  3109          // ALL FUNCTIONS BELOW THIS LINE HAVE NOT YET BEEN TESTED/CERTIFIED
  3110          // as per request by Paul Dattilo, Cindy Windomaker via email 14 Apr 2016:
  3111          // 2202 Redeem No NSF (should already be certified to corresponding void 2800)
  3112          // 2408 Inquire Multi-Lock (corresponding void is 2808)
  3113          // 2808 Redeem Unlock and Void
  3114
  3115          // as per request by Paul Dattilo via email 24 Dec 2015:
  3116          // Near Term
  3117          // 2107 Virtual Card Activation with SCV and Provisioning
  3118          // 2108 (EAN) Virtual Card Activation with Provisioning
  3119          // 2121 NRTM Card Registration
  3120          // 2301 Reload Merchant Specific
  3121          // 2495 Mobile Wallet Provisioning
  3122          // 2496 Mobile Wallet Remove Provisioning
  3123          // 2821 NRTM Card De-Registration
  3124          // 7400 Application Check
  3125
  3126          function t_2121($parameters, &$card)
  3127          {
  3128                  $tcode = 2121;
  3129                  $cmd = $tcode . " NRTM Card Registration";
  3130                  if (!$this->_preamble($parameters, $card))
  3131                          return $this->result;
  3132                  if (!$this->_req_header($tcode))
  3133                          return $this->result;
  3134
  3135                  // required
  3136                  if (!$this->_pack_field_12())
  3137                          return $this->result;
  3138                  if (!$this->_pack_field_13())
  3139                          return $this->result;
  3140                  if (!$this->_pack_field_70($card)) // TODO maybe use $this->_pack_card($card) instead?
  3141                          return $this->result;
  3142                  if (!$this->_pack_field_42())
  3143                          return $this->result;
  3144                  if (!$this->_pack_field_EA())
  3145                          return $this->result;
  3146
  3147                  // suggested
  3148                  if (!$this->_pack_field_15())
  3149                          return $this->result;
  3150                  if (!$this->_pack_field_53())
  3151                          return $this->result;
  3152
  3153                  // optional
  3154                  if (!$this->_pack_field_09())
  3155                          return $this->result;
  3156                  if (!$this->_pack_field_10())
  3157                          return $this->result;
  3158                  if (!$this->_pack_field_44())
  3159                          return $this->result;
  3160
  3161                  if (!$this->_req_do($cmd, 1, 1))
  3162                          return $this->result;
  3163                  if ($this->parameters["cardextra"]) {
  3164                          $card["card_extra"] = array();
  3165                          $card["card_extra"]["txn_auth_code"] = $this->response_field["38"];
  3166                          $card["card_extra"]["txn_resp_code"] = $this->response_field["39"];
  3167                          $card["card_extra"]["txn_req_code"] = (string)$tcode;
  3168                  }
  3169                  return true;
  3170          }
  3171
  3172          function t_2821($parameters, &$card)
  3173          {
  3174                  $tcode = 2821;
  3175                  $cmd = $tcode . " NRTM Card De-Registration";
  3176                  if (!$this->_preamble($parameters, $card))
  3177                          return $this->result;
  3178                  if (!$this->_req_header($tcode))
  3179                          return $this->result;
  3180
  3181                  // required
  3182                  if (!$this->_pack_field_12())
  3183                          return $this->result;
  3184                  if (!$this->_pack_field_13())
  3185                          return $this->result;
  3186                  if (!$this->_pack_field_42())
  3187                          return $this->result;
  3188                  if (!$this->_pack_field_70($card)) // TODO maybe use $this->_pack_card($card) instead?
  3189                          return $this->result;
  3190                  if (!$this->_pack_field_EA())
  3191                          return $this->result;
  3192
  3193                  // suggested
  3194                  if (!$this->_pack_field_15())
  3195                          return $this->result;
  3196                  if (!$this->_pack_field_53())
  3197                          return $this->result;
  3198
  3199                  // optional
  3200                  if (!$this->_pack_field_09())
  3201                          return $this->result;
  3202                  if (!$this->_pack_field_10())
  3203                          return $this->result;
  3204                  if (!$this->_pack_field_44())
  3205                          return $this->result;
  3206
  3207                  if (!$this->_req_do($cmd, 1, 1))
  3208                          return $this->result;
  3209                  if ($this->parameters["cardextra"]) {
  3210                          $card["card_extra"] = array();
  3211                          $card["card_extra"]["txn_auth_code"] = $this->response_field["38"];
  3212                          $card["card_extra"]["txn_resp_code"] = $this->response_field["39"];
  3213                          $card["card_extra"]["txn_req_code"] = (string)$tcode;
  3214                          //logInfo("card record with extra data is ".print_r($card,TRUE));
  3215                  }
  3216                  return true;
  3217          }
  3218
  3219          function t_7400($parameters)
  3220          {
  3221                  $tcode = 7400;
  3222                  $cmd = $tcode . " CLGC Application Check";
  3223                  $card = array();
  3224                  if (!$this->_preamble($parameters, $card))
  3225                          return $this->result;
  3226                  if (!$this->_req_header($tcode))
  3227                          return $this->result;
  3228
  3229                  // required
  3230                  if (!$this->_pack_field_12())
  3231                          return $this->result;
  3232                  if (!$this->_pack_field_13())
  3233                          return $this->result;
  3234                  if (!$this->_pack_field_42())
  3235                          return $this->result;
  3236                  if (!$this->_pack_field_44())
  3237                          return $this->result;
  3238
  3239                  // optional
  3240                  if (!$this->_pack_field_15())
  3241                          return $this->result;
  3242                  if (!$this->_pack_field_EA())
  3243                          return $this->result;
  3244
  3245                  if (!$this->_req_do($cmd, 1, 0))
  3246                          return $this->result;
  3247                  return true;
  3248          }
  3249
  3250          // as per request by Paul Dattilo via email 24 Dec 2015:
  3251          // Medium Term
  3252          // 2100 Activate card Without EAN
  3253          // 2103 Enable Physical Card
  3254          // 2150 Disable Internet Usage
  3255          // 2151 Merchant Disable EAN/SCV
  3256          // 2152 Merchant Enable EAN/SCV
  3257          // 2201 Redemption Unlock
  3258          // 2204 Product Redemption, No NSF
  3259          // 2208 Redemption Unlock w/Multi-Lock
  3260          // 2401 Inquire Current Balance w/Lock
  3261          // 2402 Inquire Current Balance w/Partial Lock
  3262          // 2408 Inquire Current Balance w/Multi-Lock
  3263          // 2410 Inquire Transaction History
  3264          // 2413 Inquire Activation/Reload Transaction History
  3265          // 2464 Set Reference
  3266          // 2700 Refund
  3267          // 2808 Void of Redemption w/Multi-Lock
  3268
  3269          // as per request by Paul Dattilo via email 19 Sep 2017:
  3270          // 2413, 2464, 2503
  3271          // 2403
  3272
  3273          function t_2413($parameters, $vdata, &$history)
  3274          {
  3275                  $parameters['history_code'] = 2413;
  3276                  return $this->getHistory($parameters, $vdata, $history);
  3277          }
  3278
  3279          function t_2464($parameters, &$card, $refnum)
  3280          {
  3281                  $tcode = 2464;
  3282                  $cmd = $tcode . " Set Reference";
  3283                  if (!$this->_preamble($parameters, $card))
  3284                          return $this->result;
  3285                  if (!$this->_req_header($tcode))
  3286                          return $this->result;
  3287
  3288                  // required
  3289                  if (!$this->_pack_field_08($refnum))
  3290                          return $this->result;
  3291                  if (!$this->_pack_field_12())
  3292                          return $this->result;
  3293                  if (!$this->_pack_field_13())
  3294                          return $this->result;
  3295                  if (!$this->_pack_field_42())
  3296                          return $this->result;
  3297                  if (!$this->_pack_field_44())
  3298                          return $this->result;
  3299                  if (!$this->_pack_card($card))
  3300                          return $this->result;
  3301                  if (!$this->_pack_field_EA())
  3302                          return $this->result;
  3303
  3304                  // suggested
  3305                  if (!$this->_pack_field_15())
  3306                          return $this->result;
  3307                  if (!$this->_pack_field_53())
  3308                          return $this->result;
  3309
  3310                  // optional
  3311                  if (!$this->_pack_field_09())
  3312                          return $this->result;
  3313                  if (!$this->_pack_field_10())
  3314                          return $this->result;
  3315                  if (!$this->_pack_field_F3())
  3316                          return $this->result;
  3317
  3318                  if (!$this->_req_do($cmd, 1, 1))
  3319                          return $this->result;
  3320                  if ($this->parameters["cardextra"]) {
  3321                          $card["card_extra"] = array();
  3322                          $card["card_extra"]["txn_auth_code"] = $this->response_field["38"];
  3323                          $card["card_extra"]["txn_resp_code"] = $this->response_field["39"];
  3324                          $card["card_extra"]["txn_req_code"] = (string)$tcode;
  3325                          //logInfo("card record with extra data is ".print_r($card,TRUE));
  3326                  }
  3327                  return true;
  3328          }
  3329
  3330          function t_2503($parameters, &$card, &$amt)
  3331          {
  3332                  $tcode = 2503;
  3333                  $cmd = $tcode . " Set Fraud/Watch/Restricted Status";
  3334                  if (!$this->_preamble($parameters, $card))
  3335                          return $this->result;
  3336                  if (!$this->_req_header($tcode))
  3337                          return $this->result;
  3338
  3339                  // required
  3340                  if (!$this->_pack_field_12()) // Local Transaction Time
  3341                          return $this->result;
  3342                  if (!$this->_pack_field_13()) // Local Transaction Date
  3343                          return $this->result;
  3344                  if (!$this->_pack_card($card))
  3345                          return $this->result;
  3346                  if (!$this->_pack_field_42()) // Merchant & Terminal ID
  3347                          return $this->result;
  3348                  if (!$this->_pack_field_EA()) // Source Code
  3349                          return $this->result;
  3350                  if (!$this->_pack_field_F4()) // Fraud/Watch/Restricted Status
  3351                          return $this->result;
  3352
  3353                  // suggested
  3354                  if (!$this->_pack_field_15())
  3355                          return $this->result;
  3356                  if (!$this->_pack_field_53())
  3357                          return $this->result;
  3358
  3359                  // optional
  3360                  if (!$this->_pack_field_09()) // User 1
  3361                          return $this->result;
  3362                  if (!$this->_pack_field_10()) // User 2
  3363                          return $this->result;
  3364                  if (!$this->_pack_field_44()) // Alternate Merchant Number
  3365                          return $this->result;
  3366                  // 55 Transaction Postal Code
  3367                  // 62 Clerk ID
  3368                  // AA Account Origin
  3369                  // AC Foreign Access Code
  3370                  if (!$this->_pack_field_F3()) // Merchant Key ID
  3371                          return $this->result;
  3372                  // 7F Echo Back
  3373                  // 0A Device ID
  3374                  // 0B IP Address
  3375                  // 0C Originating IP Address
  3376                  // 0D System ID
  3377
  3378                  if (!$this->_req_do($cmd, 1, 1))
  3379                          return $this->result;
  3380
  3381                  // RESPONSE required
  3382                  // 11 System Trace Number
  3383                  // 39 Response Code
  3384                  // 75 Previous Balance
  3385                  // 76 New Balance
  3386                  // 78 Lock Amount
  3387                  // 70 Embossed Card Number
  3388                  // 42 Merchant & Terminal ID
  3389                  // 44 Alternate Merchant Number
  3390                  // F6 Original Transaction Request
  3391                  // B0 Card Class
  3392                  // C0 Local Currency
  3393                  // F4 Fraud/Watch/Restricted Status
  3394
  3395                  // RESPONSE optional
  3396                  // 08 Reference Number
  3397                  // 15 Terminal Transaction Number
  3398                  // 7F Echo Back
  3399                  // E0 Absolute Expiration Date
  3400                  // F2 Promotion Code
  3401
  3402                  $previous_balance = $this->response_field["75"];
  3403                  $new_balance = $this->response_field["76"];
  3404                  $taken = $previous_balance - $new_balance;
  3405                  $amt = $taken / 100.0;
  3406                  if ($this->parameters["cardextra"]) {
  3407                          $card["card_extra"] = array();
  3408                          //$card["card_extra"]["txn_auth_code"] = $this->response_field["38"];
  3409                          $card["card_extra"]["txn_resp_code"] = $this->response_field["39"];
  3410                          $card["balance"] = $new_balance;
  3411                          $card["card_extra"]["txn_req_code"] = (string)$tcode;
  3412                  }
  3413                  return true;
  3414          }
  3415
  3416          function cardStatus($parameters, $vdata, &$amt, &$status)
  3417          {
  3418                  return $this->t_2403($parameters, $vdata, $amt, $status);
  3419          }
  3420
  3421          function t_2403($parameters, $vdata, &$amt, &$cardstatus)
  3422          {
  3423                  $tcode = 2403;
  3424                  $cmd = $tcode . " Card Status Notification";
  3425
  3426                  $this->vdata = $vdata;
  3427                  $card = $this->vdata['recipient']['card'];
  3428
  3429                  if (!$this->_preamble($parameters, $card))
  3430                          return $this->result;
  3431                  if (!$this->_req_header($tcode))
  3432                          return $this->result;
  3433
  3434                  // required
  3435                  if (!$this->_pack_field_12())
  3436                          return $this->result;
  3437                  if (!$this->_pack_field_13())
  3438                          return $this->result;
  3439                  if (!$this->_pack_card($card))
  3440                          return $this->result;
  3441                  if (!$this->_pack_field_42())
  3442                          return $this->result;
  3443                  if (!$this->_pack_field_EA())
  3444                          return $this->result;
  3445
  3446                  // suggested
  3447                  if (!$this->_pack_field_15())
  3448                          return $this->result;
  3449                  if (!$this->_pack_field_53())
  3450                          return $this->result;
  3451
  3452                  // optional
  3453                  if (!$this->_pack_field_09())
  3454                          return $this->result;
  3455                  if (!$this->_pack_field_10())
  3456                          return $this->result;
  3457                  if (!$this->_pack_field_44())
  3458                          return $this->result;
  3459                  if (!$this->_pack_field_F3())
  3460                          return $this->result;
  3461                  // Passing field CO can produce error status 55 - "Invalid currency. The provided currency is invalid."
  3462                  // Email from Ken.Jennings@firstdata.com on 9 Oct 2017 explains why:
  3463                  //
  3464                  // 1) The card examined is not capable of currency conversion.
  3465                  //    If the currency code field is sent in a request then it must match the card’s base currency.
  3466                  // 2) The GYFT MID is configured for International use (good).
  3467                  //    However, the base currency on the card examined is NOT part of that configuration.
  3468                  // 3) Currencies associated with the particular corporation are maintained in CORPORATION_CURRENCY table.
  3469                  //    So, you need that setup configuration updated to include the base currency of this card.
  3470                  //
  3471                  // We currently prevent this error by never passing C0.
  3472                  // An alternative would be to uncomment the following lines and set currency_code=notset when
  3473                  // base currency has not been configured as per Ken's explanation.
  3474                  //
  3475                  //if ($this->parameters['currency_code'] !== "notset")
  3476                  //      if (!$this->_pack_field_C0())
  3477                  //              return $this->result;
  3478
  3479                  if (!$this->_req_do($cmd, 1, 0))
  3480                          return $this->result;
  3481
  3482                  if (!isset($this->response_field["76"])) {
  3483                          $this->_set_garble("No amount (field 76)");
  3484                          return $this->result;
  3485                  }
  3486                  $amt = $this->response_field["76"] / 100.0;
  3487
  3488                  if (!isset($this->response_field["E2"])) {
  3489                          $this->_set_garble("No cardstatus (field E2)");
  3490                          return $this->result;
  3491                  }
  3492                  $cardstatus = $this->response_field["E2"];
  3493
  3494                  return true;
  3495          }
  3496
  3497          function isValid($parameters, $vdata, &$amt)
  3498          {
  3499                  //1.  Figure out which isValid function we should use.
  3500                  if (!empty($parameters['is_valid_use']) && trim($parameters['is_valid_use']) === '2403')
  3501                          return $this->_isValid2403($parameters, $vdata, $amt);
  3502
  3503                  //2. Just use the default balance method
  3504                  return $this->_isValid2400($parameters, $vdata, $amt);
  3505          }
  3506
  3507          private function _isValid2403($parameters, $vdata, &$amt)
  3508          {
  3509                  logInfo('Using: isValid2403');
  3510                  //1. Run Card Status
  3511                  $result = $this->cardStatus($parameters, $vdata, $amt, $status);
  3512                  if (is_array($result))
  3513                          return $result;
  3514                  logInfo("isValid2403: Response = ($status)");
  3515                  switch ($status) {
  3516                          case '0': //Account Inactive
  3517                                  return VALID_INACTIVE_CARD;
  3518                                  break;
  3519                          case '2': //Account Active
  3520                                  return VALID_ACTIVE_CARD;
  3521                                  break;
  3522                          case '3': //Account Closed
  3523                          case '4': //Card Lost/Stolen
  3524                          case '5': //Card Replaced
  3525                          case '6': //Account Frozen
  3526                          case '7': //Card Missing
  3527                          case '8': //Card Dead
  3528                          case '9': //Account Alias
  3529                          case '10': //On Hold
  3530                          case '11': //Void Lock
  3531                          case '12': //On HOld Activation
  3532                          case '13': //Conversion Destroyed
  3533                          case '14': //Account Dormant
  3534                          case '15': //Account Enabled
  3535                          case '16': //Watch
  3536                          case '17': //Fraud
  3537                          default:
  3538                                  return NOT_VALID_CARD;
  3539                                  break;
  3540                  }
  3541                  //Cannot really get here, but just put here for completeness
  3542                  return NOT_VALID_CARD;
  3543          }
  3544
  3545          private function _isValid2400($parameters, $vdata, &$amt)
  3546          {
  3547                  logInfo('Using: isValid2400');
  3548                  //1. Run balance
  3549                  $result = $this->getBalance($parameters, $vdata, $amt);
  3550                  //2.  We actually want an error case for FD to help us determine the state of the card
  3551                  if (is_array($result)) {
  3552                                  if (empty($result['svperrnum'])) {
  3553                                                  logError('First Data:  svperrnum is missing returning with NOT_VALID_CARD');
  3554                                                  return NOT_VALID_CARD; //Catchall
  3555                                          }
  3556
  3557                                  switch ($result['svperrnum']) {
  3558                                          case '04': //Inactive account. The account has not been activated by an approved location.
  3559                                                  return VALID_INACTIVE_CARD;
  3560                                                  break;
  3561                                          case '03': //Unknown account. The account could not be located in the account table.
  3562                                                  return NOT_VALID_CARD;
  3563                                                  break;
  3564                                          default:
  3565                                                  logError('First Data:  isValid error | ' . print_r($result, true));
  3566                                                  return $result;
  3567                                                  break;
  3568                                  }
  3569                          }
  3570                  //3.  We got a balance so return
  3571                  return VALID_ACTIVE_CARD;
  3572          }
  3573
  3574          // added by Paul Gardner - 14 Nov 2018
  3575          function t_2101($parameters, &$card, $amt)
  3576          {
  3577                  $tcode = 2101;
  3578                  $cmd = $tcode . " Activate Virtual Card w/SCV";
  3579                  if (!$this->_preamble($parameters, $card))
  3580                          return $this->result;
  3581                  if (!$this->_req_header($tcode))
  3582                          return $this->result;
  3583
  3584                  // required
  3585                  if (!$this->_pack_field_12())
  3586                          return $this->result;
  3587                  if (!$this->_pack_field_13())
  3588                          return $this->result;
  3589                  if (!$this->_pack_field_42())
  3590                          return $this->result;
  3591                  if (!$this->_pack_field_EA())
  3592                          return $this->result;
  3593                  if (!$this->_pack_field_F2())
  3594                          return $this->result;
  3595
  3596                  // suggested
  3597                  if (!$this->_pack_field_15())
  3598                          return $this->result;
  3599                  if (!$this->_pack_field_53())
  3600                          return $this->result;
  3601
  3602                  // optional
  3603                  if (!$this->_pack_field_04($amt))
  3604                          return $this->result;
  3605                  if (!$this->_pack_field_09())
  3606                          return $this->result;
  3607                  if (!$this->_pack_field_10())
  3608                          return $this->result;
  3609                  if (!$this->_pack_field_44())
  3610                          return $this->result;
  3611                  if (!$this->_pack_field_C0())
  3612                          return $this->result;
  3613
  3614                  if (!$this->_req_do($cmd, 1, 1))
  3615                          return $this->result;
  3616                  if (!isset($this->response_field["70"])) {
  3617                          $this->_set_garble("No card number");
  3618                          return $this->result;
  3619                  }
  3620                  $card['account_number'] = $this->response_field["70"];
  3621                  $card['reference_number'] = $card['pin'] = $this->response_field["32"];
  3622                  if ($this->parameters["cardextra"]) {
  3623                          $card["card_extra"] = array();
  3624                          if (!empty($this->response_field["35"])) {
  3625                                  $card["card_extra"]["track_ii"] = $this->response_field["35"];
  3626                          }
  3627                          if (!empty($this->response_field["38"])) {
  3628                                  $card["card_extra"]["txn_auth_code"] = $this->response_field["38"];
  3629                          }
  3630                          if (!empty($this->response_field["39"])) {
  3631                                  $card["card_extra"]["txn_resp_code"] = $this->response_field["39"];
  3632                          }
  3633                          $card["card_extra"]["txn_req_code"] = (string)$tcode;
  3634                  }
  3635                  return true;
  3636          }
  3637
  3638          // converse of 2016 Unfreeze Active Card
  3639          function t_2003($parameters, &$card, &$amt)
  3640          {
  3641                  $tcode = 2003;
  3642                  $cmd = $tcode . " Freeze Active Card";
  3643                  if (!$this->_preamble($parameters, $card))
  3644                          return $this->result;
  3645                  if (!$this->_req_header($tcode))
  3646                          return $this->result;
  3647
  3648                  // required
  3649                  if (!$this->_pack_field_12()) // Local Transaction Time
  3650                          return $this->result;
  3651                  if (!$this->_pack_field_13()) // Local Transaction Date
  3652                          return $this->result;
  3653                  if (!$this->_pack_field_42()) // Merchant & Terminal ID
  3654                          return $this->result;
  3655                  if (!$this->_pack_card($card))
  3656                          return $this->result;
  3657                  if (!$this->_pack_field_EA()) // Source Code
  3658                          return $this->result;
  3659
  3660                  // optional
  3661                  if (!$this->_pack_field_09()) // User 1
  3662                          return $this->result;
  3663                  if (!$this->_pack_field_10()) // User 2
  3664                          return $this->result;
  3665                  if (!$this->_pack_field_15()) // 15 Terminal Transaction Number
  3666                          return $this->result;
  3667                  if (!$this->_pack_field_44()) // Alternate Merchant Number
  3668                          return $this->result;
  3669                  // 53 Post Date
  3670                  // 55 Transaction Postal Code
  3671                  // 62 Clerk ID
  3672                  // 0A Device ID
  3673                  // 0B IP Address
  3674                  // 0C Originating IP Address
  3675                  // 0D System ID
  3676                  // 7F Echo Back
  3677                  // AA Account Origin
  3678                  // AC Foreign Access Code
  3679                  if (!$this->_pack_field_F3()) // Merchant Key ID
  3680                          return $this->result;
  3681
  3682                  if (!$this->_req_do($cmd, 1, 1))
  3683                          return $this->result;
  3684
  3685                  // RESPONSE required
  3686                  // 11 System Trace Number
  3687                  // 38 Authorization Code
  3688                  // 39 Response Code
  3689                  // 42 Merchant & Terminal ID
  3690                  // 44 Alternate Merchant Number
  3691                  // 70 Embossed Card Number
  3692                  // 75 Previous Balance
  3693                  // 76 New Balance
  3694                  // 78 Lock Amount
  3695                  // A0 Expiration Date
  3696                  // B0 Card Class
  3697                  // C0 Local Currency
  3698                  // F6 Original Transaction Request
  3699
  3700                  // RESPONSE optional
  3701                  // 08 Reference Number
  3702                  // F2 Promotion Code
  3703
  3704                  $previous_balance = $this->response_field["75"];
  3705                  $new_balance = $this->response_field["76"];
  3706                  $taken = $previous_balance - $new_balance;
  3707                  $amt = $taken / 100.0;
  3708                  if ($this->parameters["cardextra"]) {
  3709                          $card["card_extra"] = array();
  3710                          $card["card_extra"]["txn_auth_code"] = $this->response_field["38"];
  3711                          $card["card_extra"]["txn_resp_code"] = $this->response_field["39"];
  3712                          $card["balance"] = $new_balance;
  3713                          $card["card_extra"]["txn_req_code"] = (string)$tcode;
  3714                  }
  3715                  return true;
  3716          }
  3717
  3718          function t_2004($parameters, &$card, &$amt)
  3719          {
  3720                  $tcode = 2004;
  3721                  $cmd = $tcode . " Close Account";
  3722                  if (!$this->_preamble($parameters, $card))
  3723                          return $this->result;
  3724                  if (!$this->_req_header($tcode))
  3725                          return $this->result;
  3726
  3727                  // required
  3728                  if (!$this->_pack_field_12()) // Local Transaction Time
  3729                          return $this->result;
  3730                  if (!$this->_pack_field_13()) // Local Transaction Date
  3731                          return $this->result;
  3732                  if (!$this->_pack_field_42()) // Merchant & Terminal ID
  3733                          return $this->result;
  3734                  if (!$this->_pack_card($card))
  3735                          return $this->result;
  3736                  if (!$this->_pack_field_EA()) // Source Code
  3737                          return $this->result;
  3738
  3739                  // optional
  3740                  if (!$this->_pack_field_09()) // User 1
  3741                          return $this->result;
  3742                  if (!$this->_pack_field_10()) // User 2
  3743                          return $this->result;
  3744                  if (!$this->_pack_field_15()) // 15 Terminal Transaction Number
  3745                          return $this->result;
  3746                  if (!$this->_pack_field_44()) // Alternate Merchant Number
  3747                          return $this->result;
  3748                  // 53 Post Date
  3749                  // 55 Transaction Postal Code
  3750                  // 62 Clerk ID
  3751                  // 0A Device ID
  3752                  // 0B IP Address
  3753                  // 0C Originating IP Address
  3754                  // 0D System ID
  3755                  // 7F Echo Back
  3756                  // AA Account Origin
  3757                  // AC Foreign Access Code
  3758                  if (!$this->_pack_field_F3()) // Merchant Key ID
  3759                          return $this->result;
  3760                  if (!$this->_pack_field_FA()) // Remove Balance
  3761                          return $this->result;
  3762
  3763                  if (!$this->_req_do($cmd, 1, 1))
  3764                          return $this->result;
  3765
  3766                  // RESPONSE required
  3767                  // 11 System Trace Number
  3768                  // 38 Authorization Code
  3769                  // 39 Response Code
  3770                  // 42 Merchant & Terminal ID
  3771                  // 44 Alternate Merchant Number
  3772                  // 70 Embossed Card Number
  3773                  // 75 Previous Balance
  3774                  // 76 New Balance
  3775                  // 78 Lock Amount
  3776                  // A0 Expiration Date
  3777                  // B0 Card Class
  3778                  // C0 Local Currency
  3779                  // F6 Original Transaction Request
  3780
  3781                  // RESPONSE optional
  3782                  // 08 Reference Number
  3783                  // F2 Promotion Code
  3784
  3785                  $previous_balance = $this->response_field["75"];
  3786                  $new_balance = $this->response_field["76"];
  3787                  $taken = $previous_balance - $new_balance;
  3788                  $amt = $taken / 100.0;
  3789                  if ($this->parameters["cardextra"]) {
  3790                          $card["card_extra"] = array();
  3791                          $card["card_extra"]["txn_auth_code"] = $this->response_field["38"];
  3792                          $card["card_extra"]["txn_resp_code"] = $this->response_field["39"];
  3793                          $card["balance"] = $new_balance;
  3794                          $card["card_extra"]["txn_req_code"] = (string)$tcode;
  3795                  }
  3796                  return true;
  3797          }
  3798
  3799          function t_2009($parameters, &$card, &$amt)
  3800          {
  3801                  $tcode = 2009;
  3802                  $cmd = $tcode . " Freeze Inactive Card";
  3803                  if (!$this->_preamble($parameters, $card))
  3804                          return $this->result;
  3805                  if (!$this->_req_header($tcode))
  3806                          return $this->result;
  3807
  3808                  // required
  3809                  if (!$this->_pack_field_12()) // Local Transaction Time
  3810                          return $this->result;
  3811                  if (!$this->_pack_field_13()) // Local Transaction Date
  3812                          return $this->result;
  3813                  if (!$this->_pack_field_42()) // Merchant & Terminal ID
  3814                          return $this->result;
  3815                  if (!$this->_pack_card($card))
  3816                          return $this->result;
  3817                  if (!$this->_pack_field_EA()) // Source Code
  3818                          return $this->result;
  3819
  3820                  // optional
  3821                  if (!$this->_pack_field_09()) // User 1
  3822                          return $this->result;
  3823                  if (!$this->_pack_field_10()) // User 2
  3824                          return $this->result;
  3825                  if (!$this->_pack_field_15()) // 15 Terminal Transaction Number
  3826                          return $this->result;
  3827                  if (!$this->_pack_field_44()) // Alternate Merchant Number
  3828                          return $this->result;
  3829                  // 53 Post Date
  3830                  // 55 Transaction Postal Code
  3831                  // 62 Clerk ID
  3832                  // 0A Device ID
  3833                  // 0B IP Address
  3834                  // 0C Originating IP Address
  3835                  // 0D System ID
  3836                  // 7F Echo Back
  3837                  // AA Account Origin
  3838                  // AC Foreign Access Code
  3839                  if (!$this->_pack_field_F3()) // Merchant Key ID
  3840                          return $this->result;
  3841
  3842                  if (!$this->_req_do($cmd, 1, 1))
  3843                          return $this->result;
  3844
  3845                  // RESPONSE required
  3846                  // 11 System Trace Number
  3847                  // 38 Authorization Code
  3848                  // 39 Response Code
  3849                  // 42 Merchant & Terminal ID
  3850                  // 44 Alternate Merchant Number
  3851                  // 70 Embossed Card Number
  3852                  // 75 Previous Balance
  3853                  // 76 New Balance
  3854                  // 78 Lock Amount
  3855                  // A0 Expiration Date
  3856                  // B0 Card Class
  3857                  // C0 Local Currency
  3858                  // F6 Original Transaction Request
  3859
  3860                  // RESPONSE optional
  3861                  // 08 Reference Number
  3862                  // F2 Promotion Code
  3863
  3864                  $previous_balance = $this->response_field["75"];
  3865                  $new_balance = $this->response_field["76"];
  3866                  $taken = $previous_balance - $new_balance;
  3867                  $amt = $taken / 100.0;
  3868                  if ($this->parameters["cardextra"]) {
  3869                          $card["card_extra"] = array();
  3870                          $card["card_extra"]["txn_auth_code"] = $this->response_field["38"];
  3871                          $card["card_extra"]["txn_resp_code"] = $this->response_field["39"];
  3872                          $card["balance"] = $new_balance;
  3873                          $card["card_extra"]["txn_req_code"] = (string)$tcode;
  3874                  }
  3875                  return true;
  3876          }
  3877
  3878          function t_2501($parameters, &$card, &$amt)
  3879          {
  3880                  $tcode = 2501;
  3881                  $cmd = $tcode . " Report Lost/Stolen";
  3882                  if (!$this->_preamble($parameters, $card))
  3883                          return $this->result;
  3884                  if (!$this->_req_header($tcode))
  3885                          return $this->result;
  3886
  3887                  // required
  3888                  if (!$this->_pack_field_12()) // Local Transaction Time
  3889                          return $this->result;
  3890                  if (!$this->_pack_field_13()) // Local Transaction Date
  3891                          return $this->result;
  3892                  if (!$this->_pack_field_42()) // Merchant & Terminal ID
  3893                          return $this->result;
  3894                  if (!$this->_pack_card($card))
  3895                          return $this->result;
  3896                  if (!$this->_pack_field_EA()) // Source Code
  3897                          return $this->result;
  3898
  3899                  // optional
  3900                  if (!$this->_pack_field_09()) // User 1
  3901                          return $this->result;
  3902                  if (!$this->_pack_field_10()) // User 2
  3903                          return $this->result;
  3904                  if (!$this->_pack_field_15()) // 15 Terminal Transaction Number
  3905                          return $this->result;
  3906                  if (!$this->_pack_field_44()) // Alternate Merchant Number
  3907                          return $this->result;
  3908                  // 53 Post Date
  3909                  // 55 Transaction Postal Code
  3910                  // 62 Clerk ID
  3911                  // 0A Device ID
  3912                  // 0B IP Address
  3913                  // 0C Originating IP Address
  3914                  // 0D System ID
  3915                  // 7F Echo Back
  3916                  // AA Account Origin
  3917                  // AC Foreign Access Code
  3918                  if (!$this->_pack_field_F3()) // Merchant Key ID
  3919                          return $this->result;
  3920
  3921                  if (!$this->_req_do($cmd, 1, 1))
  3922                          return $this->result;
  3923
  3924                  // RESPONSE required
  3925                  // 11 System Trace Number
  3926                  // 38 Authorization Code
  3927                  // 39 Response Code
  3928                  // 42 Merchant & Terminal ID
  3929                  // 44 Alternate Merchant Number
  3930                  // 70 Embossed Card Number
  3931                  // 75 Previous Balance
  3932                  // 76 New Balance
  3933                  // 78 Lock Amount
  3934                  // A0 Expiration Date
  3935                  // B0 Card Class
  3936                  // C0 Local Currency
  3937                  // F6 Original Transaction Request
  3938
  3939                  // RESPONSE optional
  3940                  // 08 Reference Number
  3941                  // F2 Promotion Code
  3942
  3943                  $previous_balance = $this->response_field["75"];
  3944                  $new_balance = $this->response_field["76"];
  3945                  $taken = $previous_balance - $new_balance;
  3946                  $amt = $taken / 100.0;
  3947                  if ($this->parameters["cardextra"]) {
  3948                          $card["card_extra"] = array();
  3949                          $card["card_extra"]["txn_auth_code"] = $this->response_field["38"];
  3950                          $card["card_extra"]["txn_resp_code"] = $this->response_field["39"];
  3951                          $card["balance"] = $new_balance;
  3952                          $card["card_extra"]["txn_req_code"] = (string)$tcode;
  3953                  }
  3954                  return true;
  3955          }
  3956
  3957          function t_2502($parameters, &$card, &$amt)
  3958          {
  3959                  $tcode = 2502;
  3960                  $cmd = $tcode . " Report Missing";
  3961                  if (!$this->_preamble($parameters, $card))
  3962                          return $this->result;
  3963                  if (!$this->_req_header($tcode))
  3964                          return $this->result;
  3965
  3966                  // required
  3967                  if (!$this->_pack_field_12()) // Local Transaction Time
  3968                          return $this->result;
  3969                  if (!$this->_pack_field_13()) // Local Transaction Date
  3970                          return $this->result;
  3971                  if (!$this->_pack_field_42()) // Merchant & Terminal ID
  3972                          return $this->result;
  3973                  if (!$this->_pack_card($card))
  3974                          return $this->result;
  3975                  if (!$this->_pack_field_EA()) // Source Code
  3976                          return $this->result;
  3977
  3978                  // optional
  3979                  if (!$this->_pack_field_09()) // User 1
  3980                          return $this->result;
  3981                  if (!$this->_pack_field_10()) // User 2
  3982                          return $this->result;
  3983                  if (!$this->_pack_field_15()) // 15 Terminal Transaction Number
  3984                          return $this->result;
  3985                  if (!$this->_pack_field_44()) // Alternate Merchant Number
  3986                          return $this->result;
  3987                  // 53 Post Date
  3988                  // 55 Transaction Postal Code
  3989                  // 62 Clerk ID
  3990                  // 0A Device ID
  3991                  // 0B IP Address
  3992                  // 0C Originating IP Address
  3993                  // 0D System ID
  3994                  // 7F Echo Back
  3995                  // AA Account Origin
  3996                  // AC Foreign Access Code
  3997                  if (!$this->_pack_field_F3()) // Merchant Key ID
  3998                          return $this->result;
  3999
  4000                  if (!$this->_req_do($cmd, 1, 1))
  4001                          return $this->result;
  4002
  4003                  // RESPONSE required
  4004                  // 11 System Trace Number
  4005                  // 38 Authorization Code
  4006                  // 39 Response Code
  4007                  // 42 Merchant & Terminal ID
  4008                  // 44 Alternate Merchant Number
  4009                  // 70 Embossed Card Number
  4010                  // 75 Previous Balance
  4011                  // 76 New Balance
  4012                  // 78 Lock Amount
  4013                  // A0 Expiration Date
  4014                  // B0 Card Class
  4015                  // C0 Local Currency
  4016                  // F6 Original Transaction Request
  4017
  4018                  // RESPONSE optional
  4019                  // 08 Reference Number
  4020                  // F2 Promotion Code
  4021
  4022                  $previous_balance = $this->response_field["75"];
  4023                  $new_balance = $this->response_field["76"];
  4024                  $taken = $previous_balance - $new_balance;
  4025                  $amt = $taken / 100.0;
  4026                  if ($this->parameters["cardextra"]) {
  4027                          $card["card_extra"] = array();
  4028                          $card["card_extra"]["txn_auth_code"] = $this->response_field["38"];
  4029                          $card["card_extra"]["txn_resp_code"] = $this->response_field["39"];
  4030                          $card["balance"] = $new_balance;
  4031                          $card["card_extra"]["txn_req_code"] = (string)$tcode;
  4032                  }
  4033                  return true;
  4034          }
  4035
  4036          function t_0460($parameters, &$card, &$amt)
  4037          {
  4038                  $tcode = 0460;
  4039                  $cmd = $tcode . " Balance Adjustment";
  4040                  if (!$this->_preamble($parameters, $card))
  4041                          return $this->result;
  4042                  if (!$this->_req_header($tcode))
  4043                          return $this->result;
  4044
  4045                  // required
  4046                  if (!$this->_pack_field_05($amt)) // Adjustment Amount
  4047                          return $this->result;
  4048                  if (!$this->_pack_field_12()) // Local Transaction Time
  4049                          return $this->result;
  4050                  if (!$this->_pack_field_13()) // Local Transaction Date
  4051                          return $this->result;
  4052                  if (!$this->_pack_card($card)) // 70, 32/34
  4053                          return $this->result;
  4054                  if (!$this->_pack_field_42()) // Merchant & Terminal ID
  4055                          return $this->result;
  4056                  if (!$this->_pack_field_44()) // Alternate Merchant Number
  4057                          return $this->result;
  4058
  4059                  // suggested
  4060                  if (!$this->_pack_field_53()) // Post Date
  4061                          return $this->result;
  4062                  if (!$this->_pack_field_15()) // Terminal Transaction Number
  4063                          return $this->result;
  4064                  if (!$this->_pack_field_C0()) // Local Currency
  4065                          return $this->result;
  4066                  // 62 Clerk ID
  4067
  4068                  // optional
  4069                  if (!$this->_pack_field_09()) // User 1
  4070                          return $this->result;
  4071                  if (!$this->_pack_field_10()) // User 2
  4072                          return $this->result;
  4073                  if (!$this->_pack_field_EA()) // Source Code
  4074                          return $this->result;
  4075                  if (!$this->_pack_field_F3()) // Merchant Key ID
  4076                          return $this->result;
  4077                  // 06 Card Cost
  4078                  // 07 Escheatable Transaction
  4079                  // 18 SIC Code
  4080                  // AA Account Origin
  4081                  // 7F Echo Back
  4082
  4083                  if (!$this->_req_do($cmd, 1, 1))
  4084                          return $this->result;
  4085
  4086                  // RESPONSE required
  4087                  // 11 System Trace Number
  4088                  // 38 Authorization Code
  4089                  // 39 Response Code
  4090                  // 42 Merchant & Terminal ID
  4091                  // 44 Alternate Merchant Number
  4092                  // 70 Embossed Card Number
  4093                  // 75 Previous Balance
  4094                  // 76 New Balance
  4095                  // 78 Lock Amount
  4096                  // A0 Expiration Date
  4097                  // B0 Card Class
  4098                  // C0 Local Currency
  4099                  // F6 Original Transaction Request
  4100
  4101                  // RESPONSE optional
  4102                  // 08 Reference Number
  4103                  // 15 Terminal Transaction Number
  4104                  // BC Base Currency
  4105                  // 80 Base Previous Balance
  4106                  // 81 Base New Balance
  4107                  // 82 Base Lock Amount
  4108                  // E9 Exchange Rate
  4109                  // 7F Echo Back
  4110
  4111                  $previous_balance = $this->response_field["75"];
  4112                  $new_balance = $this->response_field["76"];
  4113                  $taken = $previous_balance - $new_balance;
  4114                  $amt = $taken / 100.0;
  4115                  if ($this->parameters["cardextra"]) {
  4116                          $card["card_extra"] = array();
  4117                          $card["card_extra"]["txn_auth_code"] = $this->response_field["38"];
  4118                          $card["card_extra"]["txn_resp_code"] = $this->response_field["39"];
  4119                          $card["balance"] = $new_balance;
  4120                          $card["card_extra"]["txn_req_code"] = (string)$tcode;
  4121                  }
  4122                  return true;
  4123          }
  4124
  4125          function cardInquiry($parameters, &$card) {
  4126                  $tcode = 2409;
  4127                  $cmd = $tcode . " Card Inquiry";
  4128                  if (!$this->_preamble($parameters, $card))
  4129                          return $this->result;
  4130                  if (!$this->_req_header($tcode))
  4131                          return $this->result;
  4132
  4133                  // required
  4134                  if (!$this->_pack_field_12()) // Local Transaction Time
  4135                          return $this->result;
  4136                  if (!$this->_pack_field_13()) // Local Transaction Date
  4137                          return $this->result;
  4138                  if (!$this->_pack_field_42()) // Merchant & Terminal ID
  4139                          return $this->result;
  4140                  if (!$this->_pack_card($card)) // 70, 32/34
  4141                          return $this->result;
  4142                  if (!$this->_pack_field_EA())
  4143                          return $this->result;
  4144
  4145                  // optional
  4146                  if (!$this->_pack_field_09())
  4147                          return $this->result;
  4148                  if (!$this->_pack_field_10())
  4149                          return $this->result;
  4150                  if (!$this->_pack_field_44())
  4151                          return $this->result;
  4152
  4153
  4154                  if (!$this->_req_do($cmd, 1, 1))
  4155                          return $this->result;
  4156                  if (!isset($this->response_field["01"])) {
  4157                          $this->_set_garble("No card data");
  4158                          return $this->result;
  4159                  } else {
  4160                          $cardExtraTitles = array('consortium_name', 'promo_code', 'currency_code', 'status', 'reloadable',
  4161                          'max_balance', 'min_reload_amount', 'max_reload_amount', 'load_limit', 'card_balance',
  4162                          'lock_balance', 'fund_hold', 'fund_hold_timer', 'future_availability', 'future_availability_date', 'enabled_hot',
  4163                          'security_code',' expiration_type', 'expiration_date_time', 'bonus_value', 'discount', 'denominated',
  4164                          'promoprotect', 'product_restricted', 'old_product_restricted', 'single_use', 'prevent_balance_transfer_merge',
  4165                          'is_close_on_zero');
  4166                  }
  4167
  4168                  if (!isset($this->response_field["70"])) {
  4169                          $this->_set_garble("No card number");
  4170                          return $this->result;
  4171                  }
  4172                  $card['account_number'] = $this->response_field["70"];
  4173                  if ($this->parameters["cardextra"]) {
  4174                                  $card["card_extra"] = array();
  4175                                  $cardExtraValues = explode("|", $this->response_field["01"]);
  4176                                  $idx = 0;
  4177                                  foreach ($cardExtraTitles as $title) {
  4178                                          $card["card_extra"]['extra'][$title] = $cardExtraValues[$idx++];
  4179                                  }
  4180                                  $card["card_extra"]["txn_auth_code"] = $this->response_field["38"];
  4181                                  $card["card_extra"]["txn_resp_code"] = $this->response_field["39"];
  4182                                  $card["card_extra"]['merchant_terminal_id'] = $this->response_field["42"];
  4183                                  $card["card_extra"]['alternate_merchant_number'] = $this->response_field["44"];
  4184                                  $card["card_extra"]['card_class'] = $this->response_field["B0"];
  4185                                  $card["card_extra"]['original_transaction_request'] = $this->response_field["F6"];
  4186                                  $card["card_extra"]["txn_req_code"] = (string)$tcode;
  4187                          }
  4188                          return true;
  4189          }
  4190
  4191          // added by Paul Gardner - 18 Nov 2020
  4192          function balanceMerge($parameters, &$src_card, &$dst_card, $amt = "")
  4193          {
  4194                  $tcode = 2420;
  4195                  $cmd = $tcode . " Balance Merge";
  4196                  if (!$this->_preamble($parameters, $src_card))
  4197                          return $this->result;
  4198                  if (!$this->_req_header($tcode))
  4199                          return $this->result;
  4200
  4201                  // required
  4202                  if (!$this->_pack_field_12())
  4203                          return $this->result;
  4204                  if (!$this->_pack_field_13())
  4205                          return $this->result;
  4206                  if (!$this->_pack_field_42())
  4207                          return $this->result;
  4208                  if (!$this->_pack_card_plus($src_card, 0x70, 0x40, 0x40))
  4209                          return $this->result;
  4210                  if (!$this->_pack_field_EA())
  4211                          return $this->result;
  4212                  if (!$this->_pack_card_plus($dst_card, 0xED, 0x41, 0x41))
  4213                          return $this->result;
  4214
  4215                  // suggested
  4216                  if (!$this->_pack_field_15())
  4217                          return $this->result;
  4218                  if (!$this->_pack_field_53())
  4219                          return $this->result;
  4220
  4221                  // optional
  4222                  if ($amt != "") {
  4223                          if (!$this->_pack_field_04($amt))
  4224                                  return $this->result;
  4225                          if (!$this->_pack_field_C0())
  4226                                  return $this->result;
  4227                  }
  4228                  if (!$this->_pack_field_09())
  4229                          return $this->result;
  4230                  if (!$this->_pack_field_10())
  4231                          return $this->result;
  4232                  if (!$this->_pack_field_44())
  4233                          return $this->result;
  4234                  if (!$this->_pack_field_F3())
  4235                          return $this->result;
  4236
  4237                  if (!$this->_req_do($cmd, 1, 1))
  4238                          return $this->result;
  4239                  return true;
  4240          }
  4241
  4242          // added by Paul Gardner - 18 Nov 2020
  4243          function voidBalanceMerge($parameters, &$src_card, &$dst_card, $amt = "")
  4244          {
  4245                  $tcode = 2806;
  4246                  $cmd = $tcode . " Void of Balance Merge";
  4247                  if (!$this->_preamble($parameters, $src_card))
  4248                          return $this->result;
  4249                  if (!$this->_req_header($tcode))
  4250                          return $this->result;
  4251
  4252                  // required
  4253                  if (!$this->_pack_field_12())
  4254                          return $this->result;
  4255                  if (!$this->_pack_field_13())
  4256                          return $this->result;
  4257                  if (!$this->_pack_field_42())
  4258                          return $this->result;
  4259                  if (!$this->_pack_card_plus($src_card, 0x70, 0x40, 0x40))
  4260                          return $this->result;
  4261                  if (!$this->_pack_field_EA())
  4262                          return $this->result;
  4263                  if (!$this->_pack_card_plus($dst_card, 0xED, 0x41, 0x41))
  4264                          return $this->result;
  4265
  4266                  // suggested
  4267                  if (!$this->_pack_field_15())
  4268                          return $this->result;
  4269                  if (!$this->_pack_field_53())
  4270                          return $this->result;
  4271
  4272                  // optional
  4273                  if ($amt != "") {
  4274                          if (!$this->_pack_field_04($amt))
  4275                                  return $this->result;
  4276                          if (!$this->_pack_field_C0())
  4277                                  return $this->result;
  4278                  }
  4279                  if (!$this->_pack_field_09())
  4280                          return $this->result;
  4281                  if (!$this->_pack_field_10())
  4282                          return $this->result;
  4283                  if (!$this->_pack_field_44())
  4284                          return $this->result;
  4285                  if (!$this->_pack_field_F3())
  4286                          return $this->result;
  4287
  4288                  if (!$this->_req_do($cmd, 1, 1))
  4289                          return $this->result;
  4290                  return true;
  4291          }
  4292
  4293          // converse of 2003 Freeze Active Card
  4294          // added by Paul Gardner - 8 Sep 2021
  4295          function t_2016($parameters, &$card, &$amt)
  4296          {
  4297                  $tcode = 2016;
  4298                  $cmd = $tcode . " Unfreeze Active Card";
  4299                  if (!$this->_preamble($parameters, $card))
  4300                          return $this->result;
  4301                  if (!$this->_req_header($tcode))
  4302                          return $this->result;
  4303
  4304                  // required
  4305                  if (!$this->_pack_field_12()) // Local Transaction Time
  4306                          return $this->result;
  4307                  if (!$this->_pack_field_13()) // Local Transaction Date
  4308                          return $this->result;
  4309                  if (!$this->_pack_field_42()) // Merchant & Terminal ID
  4310                          return $this->result;
  4311                  if (!$this->_pack_card($card))
  4312                          return $this->result;
  4313                  if (!$this->_pack_field_EA()) // Source Code
  4314                          return $this->result;
  4315
  4316                  // optional
  4317                  if (!$this->_pack_field_09()) // User 1
  4318                          return $this->result;
  4319                  if (!$this->_pack_field_10()) // User 2
  4320                          return $this->result;
  4321                  if (!$this->_pack_field_15()) // 15 Terminal Transaction Number
  4322                          return $this->result;
  4323                  if (!$this->_pack_field_44()) // Alternate Merchant Number
  4324                          return $this->result;
  4325                  // 53 Post Date
  4326                  // 55 Transaction Postal Code
  4327                  // 62 Clerk ID
  4328                  // 0A Device ID
  4329                  // 0B IP Address
  4330                  // 0C Originating IP Address
  4331                  // 0D System ID
  4332                  // 7F Echo Back
  4333                  // AA Account Origin
  4334                  // AC Foreign Access Code
  4335                  if (!$this->_pack_field_F3()) // Merchant Key ID
  4336                          return $this->result;
  4337
  4338                  if (!$this->_req_do($cmd, 1, 1))
  4339                          return $this->result;
  4340
  4341                  // RESPONSE required
  4342                  // 11 System Trace Number
  4343                  // 38 Authorization Code
  4344                  // 39 Response Code
  4345                  // 42 Merchant & Terminal ID
  4346                  // 44 Alternate Merchant Number
  4347                  // 70 Embossed Card Number
  4348                  // 75 Previous Balance
  4349                  // 76 New Balance
  4350                  // 78 Lock Amount
  4351                  // A0 Expiration Date
  4352                  // B0 Card Class
  4353                  // C0 Local Currency
  4354                  // F6 Original Transaction Request
  4355
  4356                  // RESPONSE optional
  4357                  // 08 Reference Number
  4358                  // F2 Promotion Code
  4359
  4360                  $previous_balance = $this->response_field["75"];
  4361                  $new_balance = $this->response_field["76"];
  4362                  $taken = $previous_balance - $new_balance;
  4363                  $amt = $taken / 100.0;
  4364                  if ($this->parameters["cardextra"]) {
  4365                          $card["card_extra"] = array();
  4366                          $card["card_extra"]["txn_auth_code"] = $this->response_field["38"];
  4367                          $card["card_extra"]["txn_resp_code"] = $this->response_field["39"];
  4368                          $card["balance"] = $new_balance;
  4369                          $card["card_extra"]["txn_req_code"] = (string)$tcode;
  4370                  }
  4371                  return true;
  4372          }
  4373
  4374  /*
  4375          // delayed activation (v1 only) step 1
  4376          function default_enHodl($parameters, &$card, $amt)
  4377          {
  4378                  return true; // no-op
  4379          }
  4380
  4381          // delayed activation (v1 only) step 2
  4382          function default_unHodl($parameters, &$card, $amt)
  4383          {
  4384                  return $this->activateCard($parameters, $card, $amt);
  4385          }
  4386
  4387          if (is_callable(array($ctsvp['_model'], "enHodl"), true))
  4388                  $ctsvp['_model']->enHodl($parameters, $card, $amt);
  4389          else
  4390                  default_enHodl($parameters, $card, $amt);
  4391
  4392          if (is_callable(array($ctsvp['_model'], "unHodl"), true))
  4393                  $ctsvp['_model']->unHodl($parameters, $card, $amt);
  4394          else
  4395                  default_unHodl($parameters, $card, $amt);
  4396
  4397  */
  4398
  4399          // delayed activation (v1 or v2) step 1
  4400          function enHodl($parameters, &$card, $amt)
  4401          {
  4402                  if (!$this->_preamble($parameters, $card))
  4403                          return $this->result;
  4404                  $x = $this->parameters['delayed_activation'];
  4405                  if ($x == "v1")
  4406                          return true; // no-op
  4407                  if ($x == "v2") {
  4408                          $status = $this->activateCard($parameters, $card, $amt);
  4409                          if (!$status && ($this->response_field["39"] != "08"))
  4410                                  return false; // error other than: "Already active. The card is already active and does not need to be reactivated"
  4411                          $status = $this->t_2003($parameters, $card, $amt);
  4412                          if (!$status && ($this->response_field["39"] != "10"))
  4413                                  return false; // error other than: "Lost or stolen card. The transaction could not be completed because the account was previously reported as lost or stolen."
  4414                          return true; // card already frozen
  4415
  4416                  }
  4417                  $this->result['errmsg'] = "'".$x."' is not a valid setting for delayed_activation.";
  4418                  $this->result['errnum'] = SVP_ERRNUM_INTERNAL;
  4419                  return false;
  4420          }
  4421
  4422          // could the opt-in to v2 simply refuse to work if there are any outstanding v1 orders?
  4423          // b2c cards in v1 mode will "always" be in flight, thwart this ^^^ check
  4424
  4425          // delayed activation (v1 or v2) step 2
  4426          function unHodl($parameters, &$card, $amt)
  4427          {
  4428                  if (!$this->_preamble($parameters, $card))
  4429                          return $this->result;
  4430                  $x = $this->parameters['delayed_activation'];
  4431                  if ($x == "v1")
  4432                          return $this->activateCard($parameters, $card, $amt);
  4433                  if ($x == "v2") {
  4434                          return $this->t_2016($parameters, $card, $amt);
  4435  /*
  4436  to deal with v1 -> v2 transition
  4437                          $status = $this->t_2016($parameters, $card, $amt);
  4438                          if ($status === true)
  4439                                  return true;
  4440                          if ($this->response_field["39"] != "04")
  4441                                  return false; // error other than: "Inactive account. The account has not been activated by an approved location."
  4442                          // v1 card - never frozen, not yet activated
  4443                          return $this->activateCard($parameters, $card, $amt);
  4444  */
  4445                  }
  4446                  $this->result['errmsg'] = "'".$x."' is not a valid setting for delayed_activation.";
  4447                  $this->result['errnum'] = SVP_ERRNUM_INTERNAL;
  4448                  return false;
  4449          }
  4450  }
root@www0:~/tw/giftcard_admin/install/vxnapi# ./test.php fanatics_eu_production 2010 1
Array
(
    [merchant_number] => 99031229997
    [MID] => 99031229997
    [TID] => 00000000001
    [DID] => 00255731298987935119
    [SVCID] => 104
    [mwk] => 0838FBE34F37737A894F2376250E2C100838FBE34F37737A
    [mwkid] => 7
    [logtofile] => /tmp/firstdata.log
    [debug] => 1
)
Array
(
    [merchant_number] => 99031229997
    [MID] => 99031229997
    [TID] => 00000000001
    [DID] => 00255731298987935119
    [SVCID] => 104
    [mwk] => 7D70BF8246931B4C27E4FA8BE3AE2911E6298465F71C9F90A488ECC8A4C823AEC91317D87B613D2D
    [mwkid] => 7
    [logtofile] => /tmp/firstdata.log
    [debug] => 1
    [source_code] => 30
)
PHP Fatal error:  Call to a member function getOverrides() on null in /root/tw/giftcard/source/wgiftcard/application/models/firstdata_model.php on line 1725
root@www0:~/tw/giftcard_admin/install/vxnapi# ./test.php fanatics_eu_production 2010 1
Array
(
    [merchant_number] => 99031229997
    [MID] => 99031229997
    [TID] => 00000000001
    [DID] => 00255731298987935119
    [SVCID] => 104
    [mwk] => 0838FBE34F37737A894F2376250E2C100838FBE34F37737A
    [mwkid] => 7
    [logtofile] => /tmp/firstdata.log
    [debug] => 1
)
Array
(
    [merchant_number] => 99031229997
    [MID] => 99031229997
    [TID] => 00000000001
    [DID] => 00255731298987935119
    [SVCID] => 104
    [mwk] => 7D70BF8246931B4C27E4FA8BE3AE2911E6298465F71C9F90A488ECC8A4C823AEC91317D87B613D2D
    [mwkid] => 7
    [logtofile] => /tmp/firstdata.log
    [debug] => 1
    [source_code] => 30
)
PHP Fatal error:  Call to a member function getOverrides() on null in /root/tw/giftcard/source/wgiftcard/application/models/firstdata_model.php on line 1725
root@www0:~/tw/giftcard_admin/install/vxnapi#
