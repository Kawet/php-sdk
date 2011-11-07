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

require '../src/CashewWrapper.php';

define('API_KEY', '');	// Your API key
define('API_SECRET', ''); // Your API secret
define('URL_CALLBACK', ''); // The url of the exempleCashewConnect.php file on your server

if(!isset($_GET['request_token'])) {
	$url = CASHEW_HTTPS_CONNECT.API_KEY.'/'.strtr(rtrim(base64_encode(URL_CALLBACK), '='), '+/', '-_');
	echo '<h2>Please login to <a href="'.$url.'">Cashew</a></h2>';
	exit(0);
}

// Instantiate the wrapper with your API key, API secret and the request token
$wrapper = new CashewWrapper(API_KEY, API_SECRET, $_GET['request_token']); 

// You can enable logs if necessary
$wrapper->enableLogs();


// App parameters
$appParameters = array(
	'title' => 'app '.time() // Don't forget to change the title every time you create an app, as app titles are unique in Cashew !
);

// Create app
$app = $wrapper->sendRequest(CASHEW_API_URL.'apps/create', $appParameters);

// Set the appId to avoid passing it in every following requests
$wrapper->setAppId($app->app_id);


// First tab parameters
$firstTabParameters = array(
	'title' => 'Home',
	'icon_id' => 217 // an integer between 165 & 314
);

// Create first tab
$firstTab = $wrapper->sendRequest(CASHEW_API_URL.'tabs/createView', $firstTabParameters);


// Header item parameters
$headerParameters = array(
	'parent_id' => $firstTab->tab_id,
	'title' => 'A title !'
);

// Create header item
$wrapper->sendRequest(CASHEW_API_URL.'items/createTitle', $headerParameters);


// Picture parameters
$pictureParameters = array(
	'Filedata' => '@./images/mayday-2011.jpg'
);

// Create picture
$picture = $wrapper->sendRequest(CASHEW_API_URL.'pictures/file', $pictureParameters);


// Picture item parameters
$pictureItemParameters = array(
	'parent_id' => $firstTab->tab_id,
	'picture_id' => $picture->picture_id
);

// Create picture item
$wrapper->sendRequest(CASHEW_API_URL.'items/createPicture', $pictureItemParameters);


// Text item parameters
$textItemParameters = array(
	'parent_id' => $firstTab->tab_id,
	'details' => 'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor '.
				 'incididunt ut labore et dolore magna aliqua.'
);

// Create text item
$wrapper->sendRequest(CASHEW_API_URL.'items/createText', $textItemParameters);


// Second tab parameters
$secondTabParameters = array(
	'title' => 'Twitter',
	'icon_id' => 187,
	'twitter_search' => 'madebykawet'
);

// Create second tab
$secondTab = $wrapper->sendRequest(CASHEW_API_URL.'tabs/createTwitter', $secondTabParameters);


// Icon parameters
$iconParameters = array(
	'Filedata' => '@./images/icon.png'
);

// Upload icon image
$wrapper->sendRequest(CASHEW_API_URL.'designs/uploadIcon', $iconParameters);


// Get app credentials
$credentials = $wrapper->sendRequest(CASHEW_API_URL.'apps/getCredentials', array('app_id' => $app->app_id), 'GET');

if($credentials->login)
	echo "<b>".$appParameters['title']."</b> created<br><br>login : $credentials->login / password : $credentials->pass";
?>