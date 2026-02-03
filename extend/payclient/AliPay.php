<?php

declare(strict_types=1);

namespace payclient;

class AliPay
{
    private $account;
    private $secret;
    private $timestamp;
    private $sign;
    private $title;
    private $msg;
    private $payway = 'alipay';

    function __construct(array $info, array $config)
    {
        $this->account = $config['account'];
        $this->secret = $config['key'];
        $this->timestamp = $info['time'];
        $this->sign = $info['sign'];
        $data = json_decode($info['data'], true);
        $this->title = $data['title'];
        $this->msg = $data['msg'];
    }
    // 收款通知
    public function notify()
    {
        $sign = $this->generateSign();
        if ($sign !== $this->sign) return ['code' => 1, 'msg' => '签名错误'];
        $money_info = $this->getPrice();
        if (!$money_info) return ['code' => 2, 'msg' => '金额提取失败'];
        $channel = $this->payway . $money_info['type'] . '#' . $this->account;
        $orders = [];
        $order = [];
        // 平台订单流水号
        $order['order_no'] = $this->payway . $this->timestamp;
        // 支付类型
        $order['payway'] = $this->payway;
        // 收款金额
        $order['price'] = $money_info['money'];
        // 收款渠道(二维码编号)
        $order['channel'] = $channel;
        // 添加到订单列表
        $orders[] = $order;
        return ['code' => 0, 'msg' => 'success', 'data' => $orders];
    }
    // 生成签名
    public function generateSign(): string
    {
        $beforeSign = $this->timestamp . "\n" . $this->secret;
        $sign = hash_hmac('sha256', $beforeSign, $this->secret, true);
        return urlencode(base64_encode($sign));
    }
    // 提取金额
    public function getPrice(): array
    {
        // 提取金额
        if ($this->title == '收款通知') {
            $patt_money = '/(\d+(?:\.\d{1,2})?)(?=元)/';
            preg_match($patt_money, $this->msg, $info);
        }else{
            $patt_money = '/(?<=成功收款)(\d+(?:\.\d{1,2})?)(?=元)/';
            preg_match($patt_money, $this->title, $info); 
        }
        // 收钱码特征 1, 经营码特征 2
        // $patt_code = '/商家用经营码/';
        // $result = match (preg_match($patt_code, $msgbody)) {
        //     1 => ['type' => 1, 'money' => $money],
        //     0 => ['type' => 2, 'money' => $money],
        //     default => []
        // };
        $money = 0;
        if ($info) $money = $info[1];
        if ($money == 0) return [];
        $result2 = ['type' => 1, 'money' => $money]; // 支付宝收款暂时无法区别 1 2 暂时返回 1 方便测试
        return $result2;
    }
}
