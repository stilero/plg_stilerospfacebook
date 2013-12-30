<?php
/**
 * Authorizing methods - Contacts FB and exchanges tokens
 * @version 1.1
 * @package AutoFBook Plugin
 * @author    Daniel Eliasson Stilero AB - http://www.stilero.com
 * @copyright	Copyright (c) 2011 Stilero AB. All rights reserved.
 * @license	GPLv2
 * 
*/
// no direct access
define('_JEXEC', 1); 
if(!defined('DS')){
    define('DS', DIRECTORY_SEPARATOR);
}
define('PATH_FBLIBRARY_FBOAUTH', '..'.DS.'library'.DS.'fblibrary'.DS.'fboauth'.DS);
define('PATH_FBLIBRARY_OAUTH', '..'.DS.'library'.DS.'fblibrary'.DS.'oauth'.DS);
require_once PATH_FBLIBRARY_OAUTH.'communicator.php';
require_once PATH_FBLIBRARY_OAUTH.'client.php';
require_once PATH_FBLIBRARY_FBOAUTH.'accesstoken.php';
require_once PATH_FBLIBRARY_FBOAUTH.'code.php';
require_once PATH_FBLIBRARY_FBOAUTH.'app.php';
require_once PATH_FBLIBRARY_FBOAUTH.'jerror.php';
require_once PATH_FBLIBRARY_FBOAUTH.'response.php';
$appID = StileroSPFBOauthCode::sanitizeInt($_POST['client_id']);
$appSecret = StileroSPFBOauthCode::sanitizeString($_POST['client_secret']);
$code = StileroSPFBOauthCode::sanitizeString($_POST['code']);
$redirectURI = StileroSPFBOauthCode::sanitizeUrl($_POST['redirect_uri']);
$FBApp = new StileroSPFBOauthApp($appID, $appSecret);
$AccessToken = new StileroSPFBOauthAccesstoken($FBApp);
$json = $AccessToken->getTokenFromCode($code, $redirectURI);
$response = StileroSPFBOauthResponse::handle($json);
$AccessToken->tokenFromResponse($response);
$token = $AccessToken->token;
if($AccessToken->isShortTerm($token)){
    $json = $AccessToken->extend();
    $response = StileroSPFBOauthResponse::handle($json);
    $AccessToken->tokenFromResponse($response);
    $token = $AccessToken->token;
}
$jsonResponse = <<<EOD
{
   "access_token": "$token"
}
EOD;
    print $jsonResponse;
?>