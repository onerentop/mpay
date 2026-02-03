<?php

declare(strict_types=1);

namespace payclient;

class WxPay
{
    private $account;
    private $secret;
    private $timestamp;
    private $sign;
    private $title;
    private $msg;
    private $info;
    private $payway = 'wxpay';

    function __construct(array $info, array $config)
    {
        $this->account = $config['account'];
        $this->secret = $config['key'];
        $this->timestamp = $info['time'];
        $this->sign = $info['sign'];
        $data = json_decode($info['data'], true);
        if ($info['action'] == 'mpay') {
            $this->title = $data['title'];
            $this->msg = $data['msg'];
        }
        if ($info['action'] == 'mpaypc') {
            $this->info = $data;
        }
    }
    // 收款通知
    public function notify(): array
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
    public function pcNotify(): array
    {
        $sign = $this->generateSign();
        if ($sign !== $this->sign) return ['code' => 1, 'msg' => '签名错误'];
        $info = $this->info;
        $channel = $this->payway . $info['chan'] . '#' . $this->account;
        $orders = [];
        $order = [];
        // 平台订单流水号
        $order['order_no'] = $this->payway . $this->timestamp;
        // 支付类型
        $order['payway'] = $this->payway;
        // 收款金额
        $order['price'] = $info['money'];
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
        $patt_money = '/(\d+(?:\.\d{1,2})?)(?=元)/';
        preg_match($patt_money, $this->msg, $info);
        $money = 0;
        if ($info) {
            $money = $info[1];
        } else {
            // 特殊情况
            if (preg_match('/个人收款码到账/', $this->msg)) {
                preg_match('/(\d+(?:\.\d{1,2})?)$/', $this->msg, $info_x);
                if ($info_x) {
                    $money = $info_x[1];
                    return ['type' => 1, 'money' => $money];
                } else {
                    return [];
                }
            } else {
                return [];
            }
        }
        if ($money == 0) return [];
        // 经营码收款金额 3
        // $patt_business = '/(?<=微信支付收款)(\d+(?:\.\d{1,2})?)(?=.*元)/';
        $result = match ($this->title) {
            '微信收款助手' => ['type' => 1, 'money' => $money],
            '微信支付' => ['type' => 2, 'money' => $money],
            // '微信收款助手' => ['type' => 3, 'money' => $money],
            '微信收款商业版' => ['type' => 4, 'money' => $money],
            default => []
        };
        return $result;
    }
}
