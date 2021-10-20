# 百度小程序支付

[官方接口文档](https://smartprogram.baidu.com/docs/develop/function/parameter/)


# Config 参数
 | 参数名字     | 类型   | 必须 | 说明                                   |
 | ------------ | ------ | ---- | -------------------------------------- |
 | appkey       | string | 是   | 百度小程序appkey                       |
 | payappKey    | string | 是   | 百度小程序支付appkey                   |
 | appSecret    | string | 是   | 百度小程序aapSecret                    |
 | dealId       | int    | 是   | 百度小程序支付凭证                     |
 | rsaPriKeyStr | string | 是   | 私钥（只需要一行长串，不需要文件）     |
 | rsaPubKeyStr | string | 是   | 百度小程序支付的平台公钥(支付回调需要) |

## Demo
```php
	$config = [
		'appkey' => 'abcdef',
		'payappKey' => "MMMMMMMM",
		'appSecret' => 'ABCDEF',
		'dealId' => 123456,
		'rsaPriKeyStr' => "ABCDEF",
		'rsaPubKeyStr' => 'ABCDEF',
	];
```
# 初始化
```php
    //use Baidu\Pay\Pays;
	$Baidu = new Pays($config);
```
## 获取token
```php
    $Baidu = new Pays($config);
	$result = $Baidu->getToken();
```
# 支付参数配置
 | 参数名字 | 类型   | 必须 | 说明       |
 | -------- | ------ | ---- | ---------- |
 | order    | string | 是   | 平台订单号 |
 | money    | int    | 是   | 金额       |
 | desc     | string | 是   | 订单描述   |
## Demo
```php

	$result = $Baidu->getOrderParm($order, $money, $desc);

```
# 订单查询参数配置
 | 参数名字     | 类型   | 必须 | 说明                |
 | ------------ | ------ | ---- | ------------------- |
 | access_token | string | 是   | 根据上面的获取token |
 | tpOrderId    | string | 是   | 平台订单号          |

```php
    $order = [
		'access_token' => 'abcde',
		'tpOrderId' => '123456',
	];
	$result = $Baidu->findOrder($order);

```
# 退款参数配置
 | 参数名字         | 类型   | 必须 | 说明                                                                                               |
 | ---------------- | ------ | ---- | -------------------------------------------------------------------------------------------------- |
 | token            | string | 是   | 根据上面的获取token                                                                                |
 | bizRefundBatchId | int    | 是   | 百度平台的订单号                                                                                   |
 | isSkipAudit      | int    | 是   | 默认为0； 0：不跳过开发者业务方审核；1：跳过开发者业务方审核。                                     |
 | orderId          | int    | 是   | 百度平台的订单号                                                                                   |
 | refundReason     | string | 是   | 退款描述                                                                                           |
 | refundType       | int    | 是   | 退款类型 1：用户发起退款；2：开发者业务方客服退款；3：开发者服务异常退款。百度小程序支付的平台公钥 |
 | tpOrderId        | string | 是   | 自己平台订单号                                                                                     |
 | userId           | int    | 是   | 用户uid（不是自己平台uid）                                                                         |
## Demo
```php
    $data = [
		'token' => 'abcd',
		'bizRefundBatchId' => 123456,//百度平台订单号
		'isSkipAudit' => 1,
		'orderId' => 123456,
		'refundReason' => '测试退款',
		'refundType' => 2,//
		'tpOrderId' => '123',//自己平台订单号
		'userId' => 123,
	];
	$result = $Baidu->applyOrderRefund($data);

```
