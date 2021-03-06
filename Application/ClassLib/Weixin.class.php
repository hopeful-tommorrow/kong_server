<?php
namespace ClassLib;

class Weixin {
	private $appId = 'wx442b38f8564d0f25';
	private $appSecret = '5c089c6078e1cebe2c04d2083ba9ace0';

	public function getSignPackage() {
		$jsapiTicket = $this->getJsApiTicket();
		$url = "http://{$_SERVER[HTTP_HOST]}{$_SERVER[REQUEST_URI]}";
		$timestamp = time();
		$nonceStr = $this->createNonceStr();

		// 这里参数的顺序要按照 key 值 ASCII 码升序排序
		$string = "jsapi_ticket={$jsapiTicket}&noncestr={$nonceStr}&timestamp={$timestamp}&url={$url}";

		$signature = sha1($string);

		$signPackage = array(
			"appId"     => $this->appId,
			"nonceStr"  => $nonceStr,
			"timestamp" => $timestamp,
			"url"       => $url,
			"signature" => $signature,
			"rawString" => $string
		);
		return $signPackage; 
	}

	private function createNonceStr($length = 16) {
		$chars = "sha.bi.neng.chi.shi.neng";
		$str = "";
		for ($i = 0; $i < $length; $i++) {
			$str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
		}
		return $str;
	}

	private function getJsApiTicket() {
		// jsapi_ticket 应该全局存储与更新，以下代码以写入到文件中做示例
		$data = json_decode(file_get_contents("Application/Mobile/weixin/jsapi_ticket.json"));
		if ($data->expire_time < time()) {
			$accessToken = $this->getAccessToken();
			$url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?type=jsapi&access_token={$accessToken}";
			$res = json_decode($this->httpGet($url));
			$ticket = $res->ticket;
			if ($ticket) {
				$data->expire_time = time() + 7000;
				$data->jsapi_ticket = $ticket;
				$fp = fopen("Application/Mobile/weixin/jsapi_ticket.json", "w");
				fwrite($fp, json_encode($data));
				fclose($fp);
			}
		} else {
			$ticket = $data->jsapi_ticket;
		}
		return $ticket;
	}

	public function getAccessToken() {
		// access_token 应该全局存储与更新，以下代码以写入到文件中做示例
		$data = json_decode(file_get_contents("Application/Mobile/weixin/access_token.json"));
		if ($data->expire_time < time()) {
			$url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$this->appId}&secret={$this->appSecret}";
			$res = json_decode($this->httpGet($url));
			$access_token = $res->access_token;
			if ($access_token) {
				$data->expire_time = time() + 7000;
				$data->access_token = $access_token;
				$fp = fopen("Application/Mobile/weixin/access_token.json", "w");
				fwrite($fp, json_encode($data));
				fclose($fp);
			}
		} else {
			$access_token = $data->access_token;
		}
		return $access_token;
	}

	private function httpGet($url) {
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_TIMEOUT, 500);
		curl_setopt($curl, CURLOPT_URL, $url);

		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);//这个是重点。
		$res = curl_exec($curl);
		curl_close($curl);

		return $res;
	}
}