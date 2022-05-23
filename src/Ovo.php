<?php

namespace Borntobeyours\Ovo;

class Ovo
{
    public static function BASE_API()
    {
        return config('ovo.BASE_API');
    }

    public static function AGW_API()
    {
        return config('ovo.AGW_API');
    }

    public static function AWS_API()
    {
        return config('ovo.AWS_API');
    }
    
    public static function os()
    {
        return config('ovo.os');
    }

    public static function app_version()
    {
        return config('ovo.app_version');
    }

    public static function client_id()
    {
        return config('ovo.client_id');
    }

    public static function user_agent()
    {
        return config('ovo.user_agent');
    }
    
    /*
    @ Device ID (UUIDV4)
    @ Generated from self::generateUUIDV4();
    */
    // const device_id = "8656831F-23DB-4A69-8F46-D91046A07849";
    
    /*
    @ Push Notification ID (SHA256 Hash)
    @ Generated from self::generateRandomSHA256();
    */
    // const push_notification_id = "e35f5a9fc1b61d0ab0c83ee5ca05ce155f82dcffee0605f1c70de38e662db362";
    
    protected $auth_token, $hmac_hash, $hmac_hash_random;
    
    public function __construct($device_id = false, $push_id = false, $auth_token = false)
    {
        $this->device_id = $device_id;
        $this->auth_token = $auth_token;
        $this->push_id = $push_id;
    }
    
    /*
    @ generateUUIDV4
    @ generate random UUIDV4 for device ID
    */
    public function generateUUIDV4()
    {
        $data    = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
        return strtoupper(vsprintf("%s%s-%s-%s-%s-%s%s%s", str_split(bin2hex($data), 4)));
    }
    
    /*
    @ generateRandomSHA256
    @ generate random SHA256 hash for push notification ID
    */
    public function generateRandomSHA256()
    {
        return hash_hmac("sha256", time(), "ovo-apps");
    }
    
    /*
    @ headers
    @ OVO cutsom headers
    */
    protected function headers($bearer = false)
    {
        $headers = array(
            'content-type: application/json',
            'accept: */*',
            'app-version: ' . self::app_version(),
            'client-id: ' . self::client_id(),
            'device-id: ' . $this->device_id,
            'os: ' . self::os(),
            'user-agent: ' . self::user_agent()
        );
        
        if ($this->auth_token) {
            array_push($headers, 'authorization: ' . $bearer . ' ' . $this->auth_token);
        }
        
        return $headers;
    }
    
    /*
    @ sendOtp
    @ param (string phone_number)
    @ AGW ENDPOINT POST("/v3/user/accounts/otp")
    */
    public function sendOtp($phone_number)
    {
        $field = array(
            'msisdn' => $phone_number,
            'device_id' => $this->device_id,
            'otp' => array(
                'locale' => 'EN',
                'sms_hash' => 'abc'
            ),
            'channel_code' => 'ovo_ios'
        );
        
        return self::request(self::AGW_API() . '/v3/user/accounts/otp', $field, $this->headers());
    }
    
    /*
    @ OTPVerify
    @ param (string phone_number, string otp_ref_id, string otp_code)
    @ AGW ENDPOINT POST("/v3/user/accounts/otp/validation")
    */
    public function OTPVerify($phone_number, $otp_ref_id, $otp_code)
    {
        $field = array(
            'channel_code' => 'ovo_ios',
            'otp' => array(
                'otp_ref_id' => $otp_ref_id,
                'otp' => $otp_code,
                'type' => 'LOGIN'
            ),
            'msisdn' => $phone_number,
            'device_id' => $this->device_id
        );
        
        return self::request(self::AGW_API() . '/v3/user/accounts/otp/validation', $field, $this->headers());
    }
    
    /*
    @ getAuthToken
    @ param (string phone_number, string otp_ref_id, string otp_token, string pin)
    @ AGW ENDPOINT POST("/v3/user/accounts/login")
    */
    public function getAuthToken($phone_number, $otp_ref_id, $otp_token, $pin)
    {
        $field = array(
            'msisdn' => $phone_number,
            'device_id' => $this->device_id,
            'push_notification_id' => $this->push_id,
            'credentials' => array(
                'otp_token' => $otp_token,
                'password' => array(
                    'value' => self::hashPassword($phone_number, $otp_ref_id, $pin),
                    'format' => 'rsa'
                )
            ),
            'channel_code' => 'ovo_ios'
        );
        
        return self::request(self::AGW_API() . '/v3/user/accounts/login', $field, $this->headers());
    }
    
    /*
    @ getPublicKeys
    @ AGW ENDPOINT GET("/v3/user/public_keys")
    */
    public function getPublicKeys()
    {
        return self::request(self::AGW_API() . '/v3/user/public_keys', false, $this->headers());
    }
    
    /*
    @ getLastTransactions
    @ param (int limit)
    @ BASE ENDPOINT GET("/wallet/transaction/last")
    */
    public function getLastTransactions($limit = 5)
    {
        return self::request(self::BASE_API() . '/wallet/transaction/last?limit=' . $limit . '&transaction_type=TRANSFER&transaction_type=EXTERNAL%20TRANSFER', false, $this->headers());
    }
    
    /*
    @ getTransactionDetails
    @ param (string merchant_id. string merchant_invoice)
    @ BASE ENDPOINT GET("/wallet/transaction/{merchant_id}/{merchant_invoice}")
    */
    public function getTransactionDetails($merchant_id, $merchant_invoice)
    {
        return self::request(self::BASE_API() . '/wallet/transaction/' . $merchant_id . '/' . $merchant_invoice . '', false, $this->headers());
    }
    
    /*
    @ getFavoriteTransfer
    @ AWS ENDPOINT GET("/user-profiling/favorite-transfer")
    */
    public function getFavoriteTransfer()
    {
        return self::request(self::AWS_API() . '/user-profiling/favorite-transfer', false, $this->headers());
    }
    
    /*
    @ hashPassword
    @ param (string phone_number, string otp_ref_id, string pin)
    @ return base64_encoded string
    */
    protected function hashPassword($phone_number, $otp_ref_id, $pin)
    {
        $rsa_key = self::parse(self::getPublicKeys(), true)['data']['keys'][0]['key'];
        $data = join("|", array(
            'LOGIN',
            $pin,
            time(),
            $this->device_id,
            $phone_number,
            $this->device_id,
            $otp_ref_id
        ));
        openssl_public_encrypt($data, $output, $rsa_key);
        return base64_encode($output);
    }
    
    /*
    @ getEmail
    @ return account email detail
    */
    public function getEmail()
    {
        return self::request(self::AGW_API() . '/v3/user/accounts/email', false, $this->headers());
    }
    
    /*
    @ transactionHistory
    @ param (int page, int limit)
    @ AGW ENDPOINT GET("/payment/orders/v1/list")
    */
    public function transactionHistory($page = 1, $limit = 10)
    {
        return self::request(self::AGW_API() . "/payment/orders/v1/list?limit=$limit&page=$page", false, $this->headers('Bearer'));
    }
    
    /*
    @ walletInquiry
    @ BASE ENDPOINT GET("/wallet/inquiry")
    */
    public function walletInquiry()
    {
        return self::request(self::BASE_API() . '/wallet/inquiry', false, $this->headers());
    }
    
    /*
    @ getOvoCash (Ovo Balance)
    @ parse self::walletInquiry()
    */
    public function getOvoCash()
    {
        return self::parse(self::walletInquiry(), false)->data->{'001'}->card_balance;
    }
    
    /*
    @ getOvoCashCardNumber (Ovo Cash)
    @ parse self::walletInquiry()
    */
    public function getOvoCashCardNumber()
    {
        return self::parse(self::walletInquiry(), false)->data->{'001'}->card_no;
    }
    
    /*
    @ getOvoPointsCardNumber (Ovo Points)
    @ parse self::walletInquiry()
    */
    public function getOvoPointsCardNumber()
    {
        return self::parse(self::walletInquiry(), false)->data->{'600'}->card_no;
    }
    
    /*
    @ getOvoPoints
    @ parse self::walletInquiry()
    */
    public function getOvoPoints()
    {
        return self::parse(self::walletInquiry(), false)->data->{'600'}->card_balance;
    }
    
    /*
    @ getPointDetails
    @ AGW ENDPOINT GET("/api/v1/get-expired-webview")
    */
    public function getPointDetails()
    {
        $json                   = base64_decode(json_decode(self::getHmac())->encrypted_string);
        $json                   = json_decode($json);
        $this->hmac_hash        = $json->hmac;
        $this->hmac_hash_random = $json->random;
        return self::request(self::AGW_API() . "/api/v1/get-expired-webview", false, self::commander_headers());
    }
    
    /*
    @ getHmac
    @ GET("https://commander.ovo.id/api/v1/get-expired-webview")
    */
    protected function getHmac()
    {
        return self::request("https://commander.ovo.id/api/v1/auth/hmac?type=1&encoded=", false, self::commander_headers());
    }
    
    /*
    @ getBillerList (get category or biller data)
    @ AWS ENDPOINT GET("/gpdm/ovo/1/v1/billpay/catalogue/getCategories")
    */
    public function getBillerList()
    {
        return self::request(self::AWS_API() . "/gpdm/ovo/1/v1/billpay/catalogue/getCategories?categoryID=0&level=1", false, $this->headers());
    }
    
    /*
    @ getBillerCategory (get biller by category ID)
    @ param (int category_id)
    @ AWS ENDPOINT GET("/gpdm/ovo/ID/v2/billpay/get-billers")
    */
    public function getBillerCategory($category_id)
    {
        return self::request(self::AWS_API() . "/gpdm/ovo/ID/v2/billpay/get-billers?categoryID={$category_id}", false, $this->headers());
    }
    
    /*
    @ getDenominations
    @ param (int product_id)
    @ AWS ENDPOINT GET("/gpdm/ovo/ID/v1/billpay/get-denominations/{product_id}")
    */
    public function getDenominations($product_id)
    {
        return self::request(self::AWS_API() . "/gpdm/ovo/ID/v1/billpay/get-denominations/{$product_id}", false, $this->headers());
    }
    
    /*
    @ getBankList
    @ BASE ENDPOINT GET("/v1.0/reference/master/ref_bank")
    */
    public function getBankList()
    {
        return self::request(self::BASE_API() . "/v1.0/reference/master/ref_bank", false, $this->headers());
    }
    
    /*
    @ getUnreadNotifications
    @ BASE ENDPOINT GET("/v1.0/notification/status/count/UNREAD")
    */
    public function getUnreadNotifications()
    {
        return self::request(self::BASE_API() . "/v1.0/notification/status/count/UNREAD", false, $this->headers());
    }
    
    /*
    @ getAllNotifications
    @ BASE ENDPOINT GET("/v1.0/notification/status/all")
    */
    public function getAllNotifications()
    {
        return self::request(self::BASE_API() . "/v1.0/notification/status/all", false, $this->headers());
    }
    
    /*
    @ getInvestment
    @ GET("https://investment.ovo.id/customer")
    */
    public function getInvestment()
    {
        return self::request("https://investment.ovo.id/customer", false, $this->headers());
    }
    
    /*
    @ billerInquiry
    @ param (string phone_number, string otp_ref_id, string otp_code)
    @ AWS ENDPOINT POST("/gpdm/ovo/ID/v2/billpay/inquiry")
    */
    public function billerInquiry($biller_id, $product_id, $denomination_id, $customer_id)
    {
        $field = array(
            'product_id' => $product_id,
            'biller_id' => $biller_id,
            'customer_number' => $customer_id,
            'denomination_id' => $denomination_id,
            'period' => 0,
            'payment_method' => array(
                '001',
                '600',
                'SPLIT'
            ),
            'customer_id' => $customer_id,
            'phone_number' => $customer_id
        );
        
        return self::request(self::AWS_API() . '/gpdm/ovo/ID/v2/billpay/inquiry?isFavorite=false', $field, $this->headers());
    }
    
    /*
    @ billerPay
    @ param (string biller_id, string product_id, string order_id, int amount, string customer_id)
    @ AWS ENDPOINT POST("/gpdm/ovo/ID/v1/billpay/pay")
    */
    public function billerPay($biller_id, $product_id, $order_id, $amount, $customer_id)
    {
        $field = array(
            "bundling_request" => array(
                array(
                    "product_id" => $product_id,
                    "biller_id" => $biller_id,
                    "order_id" => $order_id,
                    "customer_id" => $customer_id,
                    "parent_id" => "",
                    "payment" => array(
                        array(
                            "amount" => (int) $amount,
                            "card_type" => "001"
                        ),
                        array(
                            "card_type" => "600",
                            "amount" => 0
                        )
                    )
                )
            ),
            "phone_number" => $customer_id
        );
        
        return self::request(self::AWS_API() . '/gpdm/ovo/ID/v1/billpay/pay', $field, $this->headers());
    }
    
    /*
    @ isOvo
    @ param (int amount, string phone_number)
    @ BASE ENDPOINT POST("/v1.1/api/auth/customer/isOVO")
    */
    public function isOVO($amount, $phone_number)
    {
        $field = array(
            'amount' => $amount,
            'mobile' => $phone_number
        );
        
        return self::request(self::BASE_API() . '/v1.1/api/auth/customer/isOVO', $field, $this->headers());
    }
    
    /*
    @ generateTrxId
    @ param (int amount, string action_mark)
    @ BASE ENDPOINT POST("/v1.0/api/auth/customer/genTrxId")
    */
    public function generateTrxId($amount, $action_mark = "OVO Cash")
    {
        $field = array(
            'amount' => $amount,
            'actionMark' => $action_mark
        );
        
        return self::request(self::BASE_API() . '/v1.0/api/auth/customer/genTrxId', $field, $this->headers());
    }
    
    /*
    @ generateSignature
    @ param (int amount, string trx_id)
    @ generate unlockAndValidateTrxId signature
    */
    protected function generateSignature($amount, $trx_id)
    {
        return sha1(join('||', array(
            $trx_id,
            $amount,
            $this->device_id
        )));
    }
    
    /*
    @ unlockAndValidateTrxId
    @ param (int amount, string trx_id, string pin)
    @ BASE ENDPOINT POST("/v1.0/api/auth/customer/genTrxId")
    */
    public function unlockAndValidateTrxId($amount, $trx_id, $pin)
    {
        $field = array(
            'trxId' => $trx_id,
            'securityCode' => $pin,
			'appVersion' => self::app_version(),
            'signature' => self::generateSignature($amount, $trx_id)
        );
        
        return self::request(self::BASE_API() . '/v1.0/api/auth/customer/unlockAndValidateTrxId', $field, $this->headers());
    }
    
    /*
    @ transferOVO
    @ param (int/string amount, string phone_number, string, trx_id, string message)
    @ BASE ENDPOINT POST("/v1.0/api/customers/transfer")
    */
    public function transferOVO($amount, $phone_number, $trx_id, $message = "")
    {
        $field = array(
            'amount' => $amount,
            'to' => $phone_number,
            'trxId' => $trx_id,
            'message' => $message
        );
        
        return self::request(self::BASE_API() . '/v1.0/api/customers/transfer', $field, $this->headers());
    }
    
    /*
    @ transferBankInquiry
    @ param (string bank_code, string bank_number, string amount, string message)
    @ BASE ENDPOINT POST("/transfer/inquiry")
    */
    public function transferBankInquiry($bank_code, $bank_number, $amount, $message = "")
    {
        $field = array(
            'bankCode' => $bank_code,
            'accountNo' => $bank_number,
            'amount' => (string) $amount,
            'message' => $message
        );
        
        return self::request(self::BASE_API() . '/transfer/inquiry/', $field, $this->headers());
    }
    
    /*
    @ transferBankDirect
    @ param (string bank_code, string bank_number, string amount, string notes)
    @ BASE ENDPOINT POST("/transfer/direct")
    */
    public function transferBankDirect($bank_code, $bank_number, $bank_name, $bank_account_name, $trx_id, $amount, $notes = "")
    {
        $field = array(
            'bankCode' => $bank_code,
            'accountNo' => self::getOvoCashCardNumber(),
            'amount' => (string) $amount,
            'accountNoDestination' => $bank_number,
            'bankName' => $bank_name,
            'accountName' => $bank_account_name,
            'notes' => $notes,
            'transactionId' => $trx_id
        );
        
        return self::request(self::BASE_API() . '/transfer/direct', $field, $this->headers());
    }
    
    /*
    @ QrisPay
    @ param (int amount, string trx_id, string qrid)
    @ BASE ENDPOINT POST("/wallet/purchase/qr")
    */
    public function QrisPay($amount, $trx_id, $qrid)
    {
        $field = array(
            'qrPayload' => $qrid,
            'locationInfo' => array(
                'accuracy' => 11.00483309472351,
                'verticalAccuracy' => 3,
                'longitude' => 84.90665207978246,
                'heading' => 11.704396994254495,
                'latitude' => -9.432921591875759,
                'altitude' => 84.28827400936305,
                'speed' => 0.11528167128562927
            ),
            'deviceInfo' => array(
                'deviceBrand' => 'Apple',
                'deviceModel' => 'iPhone',
                'appVersion' => self::app_version(),
                'deviceToken' => $this->push_id
            ),
            'paymentDetail' => array(
                array(
                    'amount' => $amount,
                    'id' => '001',
                    'name' => 'OVO Cash'
                )
            ),
            'transactionId' => $trx_id,
            'appsource' => 'OVO-APPS'
        );
        
        return self::request(self::BASE_API() . '/wallet/purchase/qr?qrid=' . urlencode($qrid), $field, $this->headers());
    }
    
    /*
    @ parse
    @ parse JSON response
    */
    public function parse($json, $true = true)
    {
        return json_decode($json, $true);
    }
    
    /*
    @ Request
    @ Curl http request
    */
    protected function request($url, $post = false, $headers = false)
    {
        $ch = curl_init();
        
        curl_setopt_array($ch, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0
        ));
        
        if ($post) {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post));
        }
        
        if ($headers) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }
    
    /*
    @ commander API headers
    @ OVO Commander cutsom headers
    */
    protected function commander_headers()
    {
        $headers = array(
            'accept: application/json, text/plain, */*',
            'app-id: webview-pointexpiry',
            'client-id: ' . self::client_id(),
            'accept-language: id',
            'service: police',
            'origin: https://webview.ovo.id',
            'user-agent: ' . self::user_agent(),
            'referer: https://webview.ovo.id/pointexpiry?version=3.43.0'
        );
        
        if ($this->auth_token) {
            array_push($headers, 'authorization: Bearer ' . $this->auth_token);
        }
        
        if ($this->hmac_hash) {
            array_push($headers, 'hmac: ' . $this->hmac_hash);
        }
        
        if ($this->hmac_hash_random) {
            array_push($headers, 'random: ' . $this->hmac_hash_random);
        }
        
        return $headers;
    }
}
