<?php
/**
 * Copyright 2011 Kawet, Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License"); you may
 * not use this file except in compliance with the License. You may obtain
 * a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
 * WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
 * License for the specific language governing permissions and limitations
 * under the License.
 */

define('CASHEW_API_URL', 'http://api.cashew.dev.madebykawet.com/api/');
define('CASHEW_HTTPS_CONNECT', 'https://api.cashew.dev.madebykawet.com/connect/');

if (!function_exists('curl_init'))
	throw new Exception('Cashew API needs the CURL PHP extension.');

if (!function_exists('json_decode'))
	throw new Exception('Cashew API needs the JSON PHP extension.');

Class CashewWrapper
{
	public $_apiKey;
	public $_apiSecret;
	public $_accessToken;
	private $_ch;
	public $_format;
	public $_logs;
	
	function __construct($apiKey, $secret, $requestToken, $logs = false)
	{
		$this->_apiKey = $apiKey;
		$this->_apiSecret = $secret;
		$this->_ch = curl_init();
		$this->_format = 'json';
		$this->_accessToken = false;
		$this->_appId = false;
		$this->_logs = $logs;
		$params['request_token'] = $requestToken;
		$result = $this->sendRequest(CASHEW_API_URL.'auth/getAccessToken', $params, 'GET');
		if(isset($result->access_token))
			$this->_accessToken = $result->access_token;
	}
	
	function setAppId($appId)
	{
		$this->_appId = $appId;
	}
	
	function sendRequest($url, $params = array(), $method = false)
	{
		$params['API-Key'] = $this->_apiKey;
		$params['format'] = $this->_format;
		if ($this->_appId)
			$params['app_id'] = $this->_appId;
		if ($this->_accessToken)
			$params['token'] = $this->_accessToken;
		$params['api_sig'] = $this->apiSign($params);
		if (strtoupper($method) == 'POST')
		{
			curl_setopt($this->_ch, CURLOPT_POST, 1);
			curl_setopt($this->_ch, CURLOPT_POSTFIELDS, $params);
		}
		else
		{
			if (substr($url, -1) == '/')
				$url = substr($url, 0, -1);
			foreach($params as $k => $v)
				$url .= "/$k/$v";
			curl_setopt($this->_ch, CURLOPT_POST, 0);
		}
        curl_setopt($this->_ch, CURLOPT_URL, $url);
        curl_setopt($this->_ch, CURLOPT_RETURNTRANSFER,1);
		
		if (substr($url, 0, 5) == 'https')
		{
			curl_setopt($this->_ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($this->_ch, CURLOPT_SSL_VERIFYHOST, false);
		}
		$return  = curl_exec($this->_ch);
		$result = json_decode($return);
		if($this->_logs && $result->error) {
			echo 'url => '.$url.'<br>params => ';
			var_dump($params);
			echo '<br>error => '.$result->error.'<br><br>';
		}
        return $result;
	}
	
	private function apiSign($params = array())
	{
		if (!count($params))
			return '';
		$toEncode = '';
		ksort($params);
		foreach ($params as $k => $v)
			$toEncode .= $k.$v;
		$toEncode .= $this->_apiSecret;
		return md5($toEncode);
	}
}
?>