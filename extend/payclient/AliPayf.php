<?php

declare(strict_types=1);

namespace payclient;

require_once __DIR__ . '/AopClient.php';
// alipayb插件

class AliPayf
{
    // 收款平台
    private $pay_type = 'alipayf';
    // 收款平台账号
    private $pid;
    // 平台登陆密码
    private $appid;
    // 当前时间戳
    private $now;
    // 插件密钥
    private $plugin_key = '88806729d52aeb16d2f7d3599227bac3';

    function __construct(array $config)
    {
        // 支付宝PID
        $this->pid = $config['username'];
        // 支付宝APPID
        $this->appid = $config['password'];
        $this->now = time();
        $this->auth(true);
    }
    // 获取订单信息
    public function getOrderInfo(array $cert): array
    {
        if (!$this->auth()) return ['code' => 1, 'msg' => '插件未授权'];
        $order_list = $this->queryOrder($cert);
        $orders = [];
        if (!$order_list) return ['code' => 2, 'msg' => '查询列表为空'];
        foreach ($order_list as $_value) {
            $value = (array) $_value;
            if (!isset($value['type'])) continue;
            if ($value['type'] != '转账') continue;
            $order = [];
            // 平台订单流水号
            $order['order_no'] = $value['alipay_order_no'];
            // 支付类型
            $order['payway'] = 'alipay';
            // 收款金额
            $order['price'] = (float)$value['trans_amount'];
            // 收款终端编号
            $order['channel'] = 'alipay4#' . $this->pid;
            // 标识
            $order['remark'] = $value['trans_memo'];
            // 添加到订单列表
            $orders[] = $order;
        }
        return ['code' => 0, 'msg' => 'ok', 'data' => $orders];
    }
    // 获取收款地址
    static public function getPayUrl($order_no, $money, $pid): array
    {
        if (!$pid || !$order_no || !$money) return ['code' => 1, 'msg' => '参数错误'];
        $baseurl = 'https://render.alipay.com/p/yuyan/180020010001206672/rent-index.html?formData=';
        $formdata = '%7B%22productCode%22%3A%20%22TRANSFER_TO_ALIPAY_ACCOUNT%22%2C%22bizScene%22%3A%20%22YUEBAO%22%2C%22transAmount%22%3A%20%22' . $money . '%22%2C%22remark%22%3A%20%22' . $order_no . '%22%2C%22businessParams%22%3A%20%7B%22returnUrl%22%3A%20%22alipays%3A%2F%2Fplatformapi%2FstartApp%3FappId%3D2021001167654035%26nbupdate%3Dsyncforce%22%7D%2C%22payeeInfo%22%3A%20%7B%22identity%22%3A%20%22' . $pid . '%22%2C%22identityType%22%3A%20%22ALIPAY_USER_ID%22%7D%7D';
        $payurl = $baseurl . $formdata;
        return ['code' => 0, 'msg' => 'ok', 'data' => $payurl];
    }
    // 授权
    private function auth($init = false)
    {
        if ($init) {
            $dir_path = runtime_path() . "auth/";
            if (!is_dir($dir_path)) mkdir($dir_path, 755, true);
            $auth_path = $dir_path . md5($this->pay_type . __CLASS__) . '.json';
            if (!file_exists($auth_path)) file_put_contents($auth_path, json_encode(['authcode' => 'ok']));
        } else {
            $auth_path = runtime_path() . "auth/" . md5($this->pay_type . __CLASS__) . '.json';
            $auth_info = json_decode(file_get_contents($auth_path), true);
            $authcode = $auth_info['authcode'];
            // 当前站点域名
            $host = parse_url('http://' . $_SERVER['HTTP_HOST'], PHP_URL_HOST);
            $key = $this->plugin_key;
            $sign = md5($host  . $key . $host);
            return $sign === $authcode;
        }
    }
    // 查询订单
    private function queryOrder(array $cert): array
    {
        $aop = new AopClient();
        $aop->appId = $this->appid;
        $aop->rsaPrivateKey = $cert['private_key'];
        $aop->alipayrsaPublicKey = $cert['public_key'];
        $aop->signType = 'RSA2';

        $requestwo = new AlipayDataBillAccountlogQueryRequest();
        $bizContent = [
            "start_time" => date("Y-m-d H:i:s", $this->now - 180),
            "end_time" => date("Y-m-d H:i:s", $this->now),
            "page_no" => "1",
            "page_size" => "100",
            "bill_user_id" => $this->pid
        ];
        $requestwo->setBizContent(json_encode($bizContent));
        $resultwo = $aop->execute($requestwo);
        $responseNode = str_replace(".", "_", $requestwo->getApiMethodName()) . "_response";
        $data = $resultwo->$responseNode;
        $detailList = [];
        if ($data->code == '10000' && isset($data->detail_list)) {
            $detailList = (array) $data->detail_list;
        }
        return $detailList;
    }
}
