# Documentation
### Generate UUIDV4 for Device ID
```php
use Borntobeyours\Ovo\Ovo;

function generate_device_id(){
    $app = new Ovo();
    $data = $app->generateUUIDV4();
    return $data;
}
```

### Generate generateRandomSHA256 for push_notification_id
```php
use Borntobeyours\Ovo\Ovo;

function generate_push_id(){
    $app = new Ovo();
    $data = $app->generateRandomSHA256();
    return $data;
}
```

### login
```php
use Borntobeyours\Ovo\Ovo;

function generate_device_id(){
    $app = new Ovo();
    $data = $app->generateUUIDV4();
    return $data;
}

function generate_push_id(){
    $app = new Ovo();
    $data = $app->generateRandomSHA256();
    return $data;
}

function sendOTP($phone_number){
    $app = new Ovo(self::generate_device_id(), self::generate_push_id());
    $data = $app->sendOtp($phone_number);;
    return $data['otp_ref_id'];
}

function OTPVerify($phone_number, $otp_code){
    $app = new Ovo(self::generate_device_id(), self::generate_push_id());
    $data = $app->OTPVerify($phone_number, self::sendOTP(), $otp_code);
    return $data['otp_token'];
}

function login($phone_number, $pin){
    $app = new Ovo(self::generate_device_id(), self::generate_push_id());
    $data = $app->getAuthToken($phone_number, self::sendOTP(), self::OTPVerify(), $pin);
    return $data['access_token'];
}
```

### get history transaction
```php
use Borntobeyours\Ovo\Ovo;


public function history_trx()
    {
        $device_id = 'A69CB666-4908-4439-884F-82XX';
        $push_id   = 'd977e10f4caedbd46b4exxx';
        $access_token = 'eyJhbGciOiJSUzI1NiJ9.eyJleHBpcnlJbk1pbGxpU2Vj....';

        $app = new Ovo($device_id, $push_id, $access_token);
        $data = $app->transactionHistory();
        return response()->json(json_decode($data, true), 200);

    }
```

### get history transaction
```php
use Borntobeyours\Ovo\Ovo;


public function history_trx()
    {
        $device_id = 'A69CB666-4908-4439-884F-82XX';
        $push_id   = 'd977e10f4caedbd46b4exxx';
        $access_token = 'eyJhbGciOiJSUzI1NiJ9.eyJleHBpcnlJbk1pbGxpU2Vj....';

        $app = new Ovo($device_id, $push_id, $access_token);
        $data = $app->transactionHistory();
        return response()->json(json_decode($data, true), 200);

    }
```

### get wallet
```php
use Borntobeyours\Ovo\Ovo;


public function wallet()
    {
        $device_id = 'A69CB666-4908-4439-884F-82XX';
        $push_id   = 'd977e10f4caedbd46b4exxx';
        $access_token = 'eyJhbGciOiJSUzI1NiJ9.eyJleHBpcnlJbk1pbGxpU2Vj....';

        $app = new Ovo($device_id, $push_id, $access_token);
        $data = $app->walletInquiry();
        return response()->json(json_decode($data, true), 200);

    }
```

### get ovo cash saldo
```php
use Borntobeyours\Ovo\Ovo;


public function ovo_cash()
    {
        $device_id = 'A69CB666-4908-4439-884F-82XX';
        $push_id   = 'd977e10f4caedbd46b4exxx';
        $access_token = 'eyJhbGciOiJSUzI1NiJ9.eyJleHBpcnlJbk1pbGxpU2Vj....';

        $app = new Ovo($device_id, $push_id, $access_token);
        $data = $app->getOvoCash();
        return response()->json(json_decode($data, true), 200);

    }
```

### check OVO Number
```php
use Borntobeyours\Ovo\Ovo;


public function checkOvo($phone_number)
    {
        $device_id = 'A69CB666-4908-4439-884F-82XX';
        $push_id   = 'd977e10f4caedbd46b4exxx';
        $access_token = 'eyJhbGciOiJSUzI1NiJ9.eyJleHBpcnlJbk1pbGxpU2Vj....';

        $app = new Ovo($device_id, $push_id, $access_token);
        $data = $app->isOVO(10, $phone_number);
        return response()->json(json_decode($data, true), 200);

    }
```

### transfer OVO
```php
use Borntobeyours\Ovo\Ovo;


public function generateTrxId($amount)
    {
        $device_id = 'A69CB666-4908-4439-884F-82XX';
        $push_id   = 'd977e10f4caedbd46b4exxx';
        $access_token = 'eyJhbGciOiJSUzI1NiJ9.eyJleHBpcnlJbk1pbGxpU2Vj....';

        $app = new Ovo($device_id, $push_id, $access_token);
        $data = $app->generateTrxId($amount, "Ovo Cash")->trxId;
        return response()->json(json_decode($data, true), 200);

    }

public function transferOvo($amount, $pin, $to){
    $device_id = 'A69CB666-4908-4439-884F-82XX';
        $push_id   = 'd977e10f4caedbd46b4exxx';
        $access_token = 'eyJhbGciOiJSUzI1NiJ9.eyJleHBpcnlJbk1pbGxpU2Vj....';

        $app = new Ovo($device_id, $push_id, $access_token);
        $unlock = $app->unlockAndValidateTrxId($amount, self::generateTrxId(), $pin);

        if($unlock->isAuthorized){
            $data = $app->transferOVO($amount, self::generateTrxId(), $trx_id);
        }

        return response()->json(json_decode('transfer ovo error'), 401);
}
```

### transfer Bank
```php
use Borntobeyours\Ovo\Ovo;


public function bankInquiry($bankCode, $noRekening, $amount)
    {
        $device_id = 'A69CB666-4908-4439-884F-82XX';
        $push_id   = 'd977e10f4caedbd46b4exxx';
        $access_token = 'eyJhbGciOiJSUzI1NiJ9.eyJleHBpcnlJbk1pbGxpU2Vj....';

        $app = new Ovo($device_id, $push_id, $access_token);
        $data = $app->transferBankInquiry($bankCode,$noRekening, $amount);
        return response()->json(json_decode($data, true), 200);

    }

public function generateTrxId($amount)
    {
        $device_id = 'A69CB666-4908-4439-884F-82XX';
        $push_id   = 'd977e10f4caedbd46b4exxx';
        $access_token = 'eyJhbGciOiJSUzI1NiJ9.eyJleHBpcnlJbk1pbGxpU2Vj....';

        $app = new Ovo($device_id, $push_id, $access_token);
        $data = $app->generateTrxId($amount, "Ovo Cash")->trxId;
        return response()->json(json_decode($data, true), 200);

    }

public function transferBank($bankCode, $noRekening, $amount, $pin){
    $device_id = 'A69CB666-4908-4439-884F-82XX';
        $push_id   = 'd977e10f4caedbd46b4exxx';
        $access_token = 'eyJhbGciOiJSUzI1NiJ9.eyJleHBpcnlJbk1pbGxpU2Vj....';

        $app = new Ovo($device_id, $push_id, $access_token);
        $unlock = $app->unlockAndValidateTrxId($amount, self::generateTrxId(), $pin);

        if($unlock->isAuthorized){
            $data = $app->transferBankDirect($bankCode,$noRekening,self::bankInquiry()->bankName,self::bankInquiry()->accountName, self::generateTrxId(), $amount);
        }

        return response()->json(json_decode('transfer ovo error'), 401);
}
```
