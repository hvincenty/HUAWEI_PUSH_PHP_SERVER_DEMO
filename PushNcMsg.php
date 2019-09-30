<?php

class PushNcMsg
{
    private static  $appSecret = "your app secret";
    private static  $appId = "you app id"; //用户在华为开发者联盟申请的appId和appSecret（会员中心->应用管理，点击应用名称的链接）
    private static  $tokenUrl = "https://login.vmall.com/oauth2/token"; //获取认证Token的URL
    private static  $apiUrl = "https://api.push.hicloud.com/pushsend.do"; //应用级消息下发API
    private static  $accessToken; //下发通知消息的认证Token
    private static $tokenExpiredTime;  //accessToken的过期时间

    public static function refreshToken()
    {
        $msgArray = [
            'grant_type' => 'client_credentials',
            'client_secret' => self::$appSecret,
            'client_id' => self::$appId,
        ];
        $msgBody = http_build_query($msgArray);
        $response = self::httpPost(self::$tokenUrl, $msgBody, 5000, 5000);
        $res = json_decode($response, true);
        echo "access token result : " . print_r($res, true);
        if (is_array($res)) {
            self::$accessToken = $res['access_token'];
            self::$tokenExpiredTime = round(time() + $res['expires_in'] - 5 * 60 * 1000);
        }
    }
    public static function sendPushMessage()
    {
        if (self::$tokenExpiredTime <= time()) {
            self::refreshToken();
        }
        /*PushManager.requestToken为客户端申请token的方法，可以调用多次以防止申请token失败*/
        /*PushToken不支持手动编写，需使用客户端的onToken方法获取*/
        $deviceTokens[] = 'test';

        //仅通知栏消息需要设置标题和内容，透传消息key和value为用户自定义
        $body = [
            "title" => "Push message title", //消息标题
            "content" => "Push message content", //消息内容体
        ];

        $param = ["appPkgName" => "your app pakge name"]; //定义需要打开的appPkgName

        $action = [
            "type" => "3", //类型3为打开APP，其他行为请参考接口文档设置
            "param" => $param //消息点击动作参数
        ];

        $msg = [
            "type" => "3", //3: 通知栏消息，异步透传消息请根据接口文档设置
            "action" => $action, //消息点击动作
            "body" => $body, //通知栏消息body内容
        ];
        $ext = [
            'customize' => [
                ['xxxx' => 111],
                ['ss' => 22]
            ]
        ];
        //华为PUSH消息总结构体
        $hps = [
            "msg" => $msg,
            "ext" => $ext,
        ];
        $payload = [
            "hps" => $hps
        ];

        $postBodyArray = [
            'access_token' => self::$accessToken,
            'nsp_svc' => "openpush.message.api.send",
            'nsp_ts' => time(),
            'device_token_list' => self::toString($deviceTokens),
            'payload' => json_encode($payload),
        ];

        $postBody = http_build_query($postBodyArray);
        $postUrl = self::$apiUrl . "?nsp_ctx=" . urlencode(json_encode(['ver' => 1, 'appId' => self::$appId]));
        $result = self::httpPost($postUrl, $postBody, 5000, 5000);
        $res = json_decode($result, true);
        echo "Push result : " . print_r($res, true);
    }

    public static function httpPost($httpUrl, $data, $connectTimeout, $readTimeout)
    {

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $httpUrl);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_TIMEOUT, $readTimeout);   //只需要设置一个秒的数量就可以
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $connectTimeout);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1)');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type:application/x-www-form-urlencoded; charset=UTF-8"
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // 对认证证书来源的检查
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 1); // 从证书中检查SSL加密算法是否存在

        $data = curl_exec($ch);
        if (curl_errno($ch)) {
            echo 'Curl error: ' . curl_error($ch);
            die;
        }
        curl_close($ch);
        return $data;
    }

    public static function toString($array)
    {
        if ($array) {
            $regIDSStr = '[';
            foreach ($array as $value) {
                $regIDSStr .= "'{$value}',";
            }
            $regIDSStr = trim($regIDSStr, ',');
            $regIDSStr .= ']';
        }
        return $regIDSStr;
    }
}
PushNcMsg::refreshToken();
PushNcMsg::sendPushMessage();
