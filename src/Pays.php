<?php

namespace Baidu\Pay;

class Pays
{
	private $appKey;
	private $payappKey;
	private $dealId;
	private $rsaPriKeyStr;
	private $signFieldsRange;
	private $rsaPubKeyStr;
	private $applyOrderRefundUrl = 'https://openapi.baidu.com/rest/2.0/smartapp/pay/paymentservice/applyOrderRefund';
	protected $findByTpOrderIdUrl = "https://openapi.baidu.com/rest/2.0/smartapp/pay/paymentservice/findByTpOrderId";
	private $appSecret;

	public function __construct($config)
	{
		$this->appKey = $config['appkey'];
		$this->payappKey = $config['payappKey'];
		$this->dealId = $config['dealId'];
		$this->rsaPriKeyStr = $config['rsaPriKeyStr'];
		$this->signFieldsRange = isset($config['signFieldsRange']) ? $config['signFieldsRange'] : 1;
		$this->rsaPubKeyStr = isset($config['rsaPubKeyStr']) ? $config['rsaPubKeyStr'] : "";
		$this->appSecret = isset($config['appSecret']) ? $config['appSecret'] : "";
	}
	public function getOrderParm($order_no, $money, $title)
	{
		$data = [
			'dealId' => $this->dealId,
			'appKey' => $this->payappKey,
			'totalAmount' => $money * 100,
			'tpOrderId' => $order_no,
		];
		$sign = self::sign($data, $this->rsaPriKeyStr);
		$data['dealTitle'] = $title;
		$data['rsaSign'] = $sign;
		$data['signFieldsRange'] = $this->signFieldsRange;
		return $data;
	}
	public function getToken()
	{
		$url = "https://openapi.baidu.com/oauth/2.0/token?grant_type=client_credentials&client_id=" . $this->appKey . "&client_secret=" . $this->appSecret . "&scope=smartapp_snsapi_base";
		$token = file_get_contents($url);
		$result = json_decode($token, true);
		return $result;
	}
	/**
	 * @desc 异步回调
	 * @param array data 回调参数$_POST
	 * @return bool true 验签通过|false 验签不通过
	 */
	public function notifyCheck($data)
	{
		return self::checkSign($data, $this->rsaPubKeyStr);
	}
	/**
	 * 申请退款
	 *
	 */
	public function applyOrderRefund($order)
	{
		$data = [
			'access_token' => $order['access_token'],
			'bizRefundBatchId' => time(),
			'isSkipAudit' => $order['isSkipAudit'],
			'orderId' => $order['orderId'],
			'refundReason' => $order['refundReason'],
			'refundType' => $order['refundType'],
			'tpOrderId' => $order['tpOrderId'],
			'userId' => $order['userId'],
			'pmAppKey' => $this->payappKey,
		];

		// dd($data);
		$result = json_decode($this->curl_post($this->applyOrderRefundUrl, $data), true);
		if ($result['errno'] == 0) {
			return true;
		} else {
			return false;
		}
	}
	/**
	 * @desc 异步回调
	 * @param array order 参数组合
	 * @return array 订单信息
	 */
	public function findOrder($order)
	{
		if (empty($order)) {
			return false;
		}
		$string = "?access_token=" . $order['access_token'] . "&tpOrderId=" . $order['tpOrderId'] . "&pmAppKey=" . $this->payappKey;
		$result = json_decode($this->curl_get($this->findByTpOrderIdUrl . $string), true);
		if ($result['errno'] == 0) {
			return $result['data'];
		}
		return false;
	}
	protected static function curl_get($url)
	{
		$ch = curl_init();
		//设置选项，包括URL
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HEADER, 0);

		//执行并获取HTML文档内容
		$output = curl_exec($ch);
		//释放curl句柄
		curl_close($ch);
		return $output;
	}
	/**
	 * @desc post 用于退款
	 */
	protected static function curl_post($url, $data = array())
	{
		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $url);

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);

		// POST数据

		curl_setopt($ch, CURLOPT_POST, 1);

		// 把post的变量加上

		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

		$output = curl_exec($ch);

		curl_close($ch);

		return $output;
	}
	/**
	 * @desc 使用私钥生成签名字符串
	 * @param array $assocArr 入参数组
	 * @param string $rsaPriKeyStr 私钥原始字符串，不含PEM格式前后缀
	 * @return string 签名结果字符串
	 * @throws Exception
	 */
	public static function sign(array $assocArr, $rsaPriKeyStr)
	{

		$sign = '';
		if (empty($rsaPriKeyStr) || empty($assocArr)) {
			return $sign;
		}

		if (!function_exists('openssl_pkey_get_private') || !function_exists('openssl_sign')) {
			throw new \Exception("openssl扩展不存在");
		}

		$rsaPriKeyPem = self::convertRSAKeyStr2Pem($rsaPriKeyStr, 1);

		$priKey = openssl_pkey_get_private($rsaPriKeyPem);

		if (isset($assocArr['sign'])) {
			unset($assocArr['sign']);
		}
		// 参数按字典顺序排序
		ksort($assocArr);
		$parts = array();
		foreach ($assocArr as $k => $v) {
			$parts[] = $k . '=' . $v;
		}
		$str = implode('&', $parts);
		openssl_sign($str, $sign, $priKey);
		openssl_free_key($priKey);
		return base64_encode($sign);
	}
	/**
	 * @desc 使用公钥校验签名
	 * @param array $assocArr 入参数据，签名属性名固定为rsaSign
	 * @param string $rsaPubKeyStr 公钥原始字符串，不含PEM格式前后缀
	 * @return bool true 验签通过|false 验签不通过
	 * @throws Exception
	 */
	public static function checkSign(array $assocArr, $rsaPubKeyStr)
	{

		if (!isset($assocArr['rsaSign']) || empty($assocArr) || empty($rsaPubKeyStr)) {

			return false;
		}
		if (!function_exists('openssl_pkey_get_public') || !function_exists('openssl_verify')) {

			throw new \Exception("openssl扩展不存在");
		}
		$sign = $assocArr['rsaSign'];

		unset($assocArr['rsaSign']);

		if (empty($assocArr)) {
			return false;
		}
		// 参数按字典顺序排序
		ksort($assocArr);

		$parts = array();

		foreach ($assocArr as $k => $v) {

			$parts[] = $k . '=' . $v;
		}
		$str = implode('&', $parts);

		$sign = base64_decode($sign);

		$rsaPubKeyPem = self::convertRSAKeyStr2Pem($rsaPubKeyStr);

		$pubKey = openssl_pkey_get_public($rsaPubKeyPem);

		$result = (bool)openssl_verify($str, $sign, $pubKey);


		return $result;
	}
	/**
	 * @desc 将密钥由字符串（不换行）转为PEM格式
	 * @param string $rsaKeyStr 原始密钥字符串
	 * @param int $keyType 0 公钥|1 私钥，默认0
	 * @return string PEM格式密钥
	 * @throws Exception
	 */
	public static function convertRSAKeyStr2Pem($rsaKeyStr, $keyType = 0)
	{

		$pemWidth = 64;
		$rsaKeyPem = '';
		$begin = '-----BEGIN ';
		$end = '-----END ';
		$key = ' KEY-----';
		$type = $keyType ? 'PRIVATE' : 'PUBLIC';
		$rsa = $keyType ? 'RSA ' : '';
		$keyPrefix = $begin . $rsa . $type . $key;
		$keySuffix = $end . $rsa . $type . $key;
		$rsaKeyPem .= $keyPrefix . "\n";
		$rsaKeyPem .= wordwrap($rsaKeyStr, $pemWidth, "\n", true) . "\n";
		$rsaKeyPem .= $keySuffix;

		if (!function_exists('openssl_pkey_get_public') || !function_exists('openssl_pkey_get_private')) {
			return false;
		}

		if ($keyType == 0 && false == openssl_pkey_get_public($rsaKeyPem)) {
			return false;
		}

		if ($keyType == 1 && false == openssl_pkey_get_private($rsaKeyPem)) {
			return false;
		}

		return $rsaKeyPem;
	}
}
