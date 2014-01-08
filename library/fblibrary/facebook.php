<?php
/**
 * Facebook class as a starter point
 * 
 * POSTS TO PERSONAL WALL
 * To post a status message to your personal wall:
 * 1. Retrieve an access token and authorize your app:
 *      $Facebook = new StileroFBFacebook($appID, $appSecret, $redirectURI);
 *      $Facebook->init();
 *      $token = $Facebook->getToken();
 * 2. Store the token for future calls, otherwise you will need to reauthorise
 * 3. Do the API Calls with your token
 *      $Facebook = new StileroFBFacebook($appID, $appSecret, $redirectURI);
 *      $Facebook->setAccessTokenFromToken($token);
 *      $Facebook->init();
 *      $response = $Facebook->Feed->postLink('http://www.streetpeople.se');
 *      $debug = StileroFBResponse::handle($response);
 *      var_dump($debug);
 *      $updatedToken = $Facebook->getToken();
 * 4. Store the updated token and use for future calls. 
 * 
 * POSTS TO PAGE WALL
 * To post a photo to your page wall:
 * 1. Retrieve an access token and authorize your app (only done once):
 *      $Facebook = new StileroFBFacebook($appID, $appSecret, $redirectURI);
 *      $Facebook->init();
 *      $token = $Facebook->getToken();
 * 2. Store the token for future calls, otherwise you will need to reauthorise
 * 3. Do the API Calls with your token
 *      $Facebook = new StileroFBFacebook($appID, $appSecret, $redirectURI);
 *      $Facebook->setAccessTokenFromToken($token);
 *      $Facebook->init();
 *      $pageToken = $Facebook->User->getTokenForPageWithId($pageID);
 *      $Facebook->Feed->setToken($pageToken);
 *      $response = $Facebook->Photos->publishFromUrl('http://ilovephoto.se/images/portfolio/bestphotos/portfolio-photography-corporate-12-13120909.jpg');
 *      $debug = StileroFBResponse::handle($response);
 *      var_dump($debug);
 *      $updatedToken = $Facebook->getToken();
 * 4. Store the updated token and use for future calls. 
 *
 * @version  1.0
 * @package Stilero
 * @subpackage class-oauth-fb
 * @author Daniel Eliasson <daniel at stilero.com>
 * @copyright  (C) 2013-dec-20 Stilero Webdesign (http://www.stilero.com)
 * @license	GNU General Public License version 2 or later.
 * @link http://www.stilero.com
 */

// no direct access
defined('_JEXEC') or die('Restricted access'); 

class StileroSPFBFacebook{
    protected $Comments;
    protected $Feed;
    protected $Likes;
    protected $Photos;
    protected $User;
    protected $App;
    protected $AccessToken;
    protected $redirectUri;
    protected $userId = 'me';   
    
    /**
     * The Controller/Wrapper for the entire Facebook class
     * @param string $appId The Facebook App ID received from developers.facebook.com
     * @param string $appSecret The Facebook App Secret received from developers.facebook.com
     * @param string $redirectUri The redirect url is typically the absolute url to the page where this script is run (http://www.mypage.com/index.php)
     * @param string $access_token Access token
     */
    public function __construct($appId, $appSecret, $redirectUri, $access_token=null) {
        $this->App = new StileroSPFBOauthApp($appId, $appSecret);
        $this->redirectUri = $redirectUri;
        if(isset($access_token)){
            $AccessToken = new StileroSPFBOauthAccesstoken($this->App);
            $AccessToken->setToken($access_token);
            $this->AccessToken = $AccessToken;
        }
        $this->init();
    }
    
    /**
     * Returns the Login Dialog URL for authorising apps at Facebook
     * @return string Login Dialog Url
     */
    protected function loginDialogUrl(){
        $Dialog = new StileroSPFBLoginDialog($this->App, $this->redirectUri);
        $csfrState = StileroSPFBOauthEncryption::EncryptedCSFRState($this->App->id, $this->App->secret);
        $responseType = StileroSPFBLoginDialogResponseType::CODE;
        $scope = StileroFBPermisisonsPagesGroupsUsers::permissionList();
        $url = $Dialog->url($csfrState, $responseType, $scope);
        return $url;
    }
    
    /**
     * Redirects the user to the FB LoginDialog by printing out a JScript.
     */
    protected function redirectToLoginDialog(){
        $url = $this->loginDialogUrl();
        print "<script> top.location.href='".$url."'</script>";
    }
    
    /**
     * The starting point for this api. In no AccessToken is set the user is redirected
     * to the Login Dialog. This method will also catch any access codes returned, 
     * and sets it to the AccessToken.
     * Don't forget to call the getAccessToken and save the token for future calls
     * to avoid the need of reauthorisation.
     * @param integer $userId The User/Wall/group id to send posts to
     */
    public function init(){
        if(!isset($this->AccessToken) && (!StileroSPFBOauthCode::hasCodeInGetRequest())){
            $this->redirectToLoginDialog();
        }else if(StileroSPFBOauthCode::hasCodeInGetRequest()){
            $Code = new StileroSPFBOauthCode();
            $Code->fetchCode();
            $AccessToken = new StileroSPFBOauthAccesstoken($this->App);
            $response = $AccessToken->getTokenFromCode($Code->code, $this->redirectUri);
            $AccessToken->tokenFromResponse($response);
            $this->setAccessTokenFromToken($AccessToken->token);
        }
        $this->renewToken();
        $this->Feed = new StileroSPFBEndpointFeed($this->AccessToken, $this->userId);
        $this->User = new StileroSPFBEndpointUser($this->AccessToken, $this->userId);
        $this->Photos = new StileroSPFBEndpointPhotos($this->AccessToken, $this->userId);
    }
    
    /**
     * Checks if a token will expire and extends it if not permanent
     */
    protected function renewToken(){
        if(!$this->AccessToken->willNeverExpire($this->AccessToken->token)){
            $this->AccessToken->extend();
        }
    }
    
    /**
     * Sets the user id
     * @param int $userId The User/Page/Group id
     */
    public function setUserId($userId){
        $this->userId = $userId;
    }
    
    /**
     * Takes a token and creates an AccessToken object for this class.
     * @param string $token token string
     */
    public function setAccessTokenFromToken($token){
        $AccessToken = new StileroSPFBOauthAccesstoken($this->App);
        $AccessToken->setToken($token);
        $this->AccessToken = $AccessToken;
    }
    
    /**
     * Returns the token of the AccessToken object
     * @return string token
     */
    public function getToken(){
        return $this->AccessToken->token;
    }
    
    /**
     * ENDPOINT WRAPPERS
     */
    /**
     * Returns a Feed object for easy access
     * @return \StileroFBEndpointFeed
     */
    public function Feed(){
        if(isset($this->Feed)){
            return $this->Feed;
}
        $Feed = new StileroSPFBEndpointFeed($this->AccessToken);
        $this->Feed = $Feed;
        return $Feed;
    }
    /**
     * Returns a User object for easy access
     * @return \StileroFBEndpointUser
     */
    public function User(){
        if(isset($this->User)){
            return $this->User;
        }
        $User = new StileroSPFBEndpointUser($this->AccessToken);
        $this->User = $User;
        return $User;
    }
    /**
     * Returns a photos endpoint for easy access
     * @return \StileroFBEndpointPhotos
     */
    public function Photos(){
        if(isset($this->Photos)){
            return $this->Photos;
        }
        $Photos = new StileroSPFBEndpointPhotos($this->AccessToken);
        $this->Photos = $Photos;
        return $Photos;
    }
    /**
     * Returns a Comments endpoint
     * @param integer $postid Facebook post id
     * @return \StileroFBEndpointComments
     */
    public function Comments($postid){
        if(isset($this->Comments)){
            return $this->Comments;
        }
        $Comments = new StileroSPFBEndpointComments($this->AccessToken, $postid);
        $this->Comments = $Comments;
        return $Comments;
    }
    /**
     * Returns a Likes endpoint
     * @param integer $postid Facebook Post id
     * @return \StileroFBEndpointLikes
     */
    public function Likes($postid){
        if(isset($this->Likes)){
            return $this->Likes;
        }
        $Likes = new StileroSPFBEndpointLikes($this->AccessToken, $postid);
        $this->Likes = $Likes;
        return $Likes;
    }
}
