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

define('CASHEW_API_URL', 'http://cashew.madebykawet.com/api/');
define('CASHEW_API_HTTPS_URL', 'https://cashew.madebykawet.com/api/');
define('CASHEW_HTTPS_CONNECT', 'https://cashew.madebykawet.com/connect/');

if (!function_exists('curl_init'))
	throw new Exception('Cashew API needs the CURL PHP extension.');

if (!function_exists('json_decode'))
	throw new Exception('Cashew API needs the JSON PHP extension.');

Class CashewWrapper
{
	private   $_ch;
	protected $_accessToken = false;
	protected $_apiKey;
	protected $_apiSecret;
	protected $_appId = false;
	protected $_logs = true;
	
	function __construct($apiKey, $secret, $requestToken = false)
	{
		$this->_apiKey = $apiKey;
		$this->_apiSecret = $secret;
		$this->_ch = curl_init();
		$requestToken && $this->getAccessToken($requestToken);
	}
	
	function enableLogs()
	{
		$this->_logs = true;
	}
	
	function getAccessToken($requestToken)
	{
		$params['request_token'] = $requestToken;
		$result = $this->sendRequest(CASHEW_API_URL.'auth/getAccessToken', $params, 'GET');
		if(isset($result->access_token))
		{
			$this->_accessToken = $result->access_token;
			return true;
		}
		return false;
	}
	
	function setAppId($appId)
	{
		$this->_appId = $appId;
	}
	
	function login($username, $password)
	{
		$params = array('username' => $username, 'password' => $password);
		$result = $this->sendRequest(CASHEW_API_HTTPS_URL.'auth/login', $params);
		if(isset($result->request_token) && isset($result->user_id) && $this->getAccessToken($result->request_token))
			return $result->user_id;
		return false;
	}
	
	function sendRequest($url, $params = array(), $method = 'POST')
	{
		$params['API-Key'] = $this->_apiKey;
		$this->_appId && $params['app_id'] = $this->_appId;
		$this->_accessToken && $params['token'] = $this->_accessToken;
		$params['api_sig'] = $this->apiSign($params);
		if (strtoupper($method) == 'POST')
		{
			$useragent="Cashew API Wrapper (PHP)";

			curl_setopt($this->_ch, CURLOPT_USERAGENT, $useragent);
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
		if($this->_logs)
			echo 'url => '.$url.'<br>params => '.var_export($params, true);
		$return  = curl_exec($this->_ch);
		$result = json_decode($return);
		if($result == NULL)
			$result = $return;
		if($this->_logs) {
			if(isset($result->error))
				$log = '<br><b>error => '.$result->error.'</b>';
			else
				$log = '<br>result => '.var_export($result, true);
			echo $log.'<br><br>';
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
			if($k != 'Filedata')
				$toEncode .= $k.$v;
		$toEncode .= $this->_apiSecret;
		return sha1(htmlentities(str_replace(array("\r\n", "\n", "\r"), '', $toEncode)));
	}
}
?>