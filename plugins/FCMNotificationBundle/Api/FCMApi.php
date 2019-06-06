<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\FCMNotificationBundle\Api;

use Joomla\Http\Response;
use Mautic\NotificationBundle\Entity\Notification;
use Mautic\FCMNotificationBundle\Exception\MissingApiKeyException;
use Mautic\FCMNotificationBundle\Exception\MissingProjectIDException;
use Mautic\FCMNotificationBundle\Exception\MissingMessagingSenderIdException;
use Mautic\FCMNotificationBundle\Exception\MissingServiceAccountJsonException;
use Mautic\NotificationBundle\Api\AbstractNotificationApi;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Plokko\Firebase\FCM\Exceptions\FcmErrorException;
use Plokko\Firebase\FCM\Message;
use Plokko\Firebase\FCM\Request;
use Plokko\Firebase\FCM\Targets\Token;
use Plokko\Firebase\ServiceAccount;
use Google\Auth\Cache\MemoryCacheItemPool;

class FCMApi extends AbstractNotificationApi
{
    /**
     * @var string
     */    
    protected $apiKey = '';
    protected $projectId = '';
    protected $messagingSenderId = '';
    protected $serviceAccount = '';
    protected $notificationIcon = '';

    public function __construct() {
        $args = func_get_args();
        call_user_func_array(array($this, 'parent::__construct'), $args);

        $integration = $this->integrationHelper->getIntegrationObject('FCM');
        if (!$integration || $integration->getIntegrationSettings()->getIsPublished() === false) {
            return;
        }
        
        $this->apiKeys    = $this->integrationHelper->getIntegrationObject('FCM')->getKeys();
        
        if (!empty($this->apiKeys['apiKey'])){
            $this->apiKey      = $this->apiKeys['apiKey'];    
        }else{
            throw new MissingApiKeyException();   
        }

        if (!empty($this->apiKeys['projectId'])){
            $this->projectId      = $this->apiKeys['projectId'];    
        }else{
            throw new MissingProjectIDException();   
        }

        if (!empty($this->apiKeys['messagingSenderId'])){
            $this->messagingSenderId      = $this->apiKeys['messagingSenderId'];    
        }else{
            throw new MissingMessagingSenderIdException();   
        }

        if (!empty($this->apiKeys['service_account_json'])){            
             //-- Init the service account --//    
            $this->serviceAccount = new ServiceAccount($this->apiKeys['service_account_json']);
            $cacheHandler = new MemoryCacheItemPool();
            $this->serviceAccount->setCacheHandler($cacheHandler);    
        }else{
            throw new MissingServiceAccountJsonException();   
        }   

        $settings          = $this->integrationHelper->getIntegrationObject('FCM')->getIntegrationSettings();        
        $featureSettings   = $settings->getFeatureSettings(); 

        if (!empty($featureSettings['notification_icon'])){
            $this->notificationIcon = $featureSettings['notification_icon'];
        }
    }



   


    /**     
     * @param string $token    FCM token of the targeted user
     * @param string $data     JSON encoded array of data to send
     *
     * @return Response
     *
     * @throws MissingAppIDException
     * @throws MissingApiKeyException
     */
    public function send($token, $data, $validate_only = false, $debug = false){    
        $message = new Message();        
              
        $message->android->data->fill($data['android']['data']);
        $message->apns->headers = $data['apns']['headers'];
        $message->apns->payload->aps->alert = $data['apns']['payload']['aps']['alert'];
        $message->webpush->data = $data['webpush']['data'];

        $message->setTarget(new Token($token));        

        $client = new Client(['debug' => $debug]);

        //If true the validate_only is set to true the message will not be submitted but just checked with FCM
                
        //Create a request
        $rq = new Request($this->serviceAccount,$validate_only,$client);
        
        try{
            //Use the request to submit the message
            return $message->send($rq);
            //You can force the validate_only flag via the validate method, the request will be left intact
            //$message->validate($rq);
        }
        /** Catch all the exceptions @see https://firebase.google.com/docs/reference/fcm/rest/v1/ErrorCode **/
        //Like this
        catch(FcmErrorException $e){
            switch($e->getErrorCode()){
                default:
                case 'UNSPECIFIED_ERROR':
                case 'INVALID_ARGUMENT':
                case 'UNREGISTERED':
                case 'SENDER_ID_MISMATCH':
                case 'QUOTA_EXCEEDED':
                case 'APNS_AUTH_ERROR':
                case 'UNAVAILABLE':
                case 'INTERNAL':
            }
            return 'FCM error ['.$e->getErrorCode().']: '.$e->getMessage();            
        }
        catch(RequestException $e){
            //HTTP response error
            $response = $e->getResponse();
            return 'Got an http response error:'.$response->getStatusCode().':'.$response->getReasonPhrase();                        
        }
        catch(GuzzleException $e){
            //GuzzleHttp generic error
            return  'Got an http error:'.$e->getMessage();            
        }

    }

   
    /**
     * @param string|array $playerId     Player ID as string, or an array of player ID's
     * @param Notification $notification
     *
     * @return Response
     *
     * @throws \Exception
     */
    public function sendNotification($playerId, Notification $notification, $notificationId)
    {        
        $buttonId = $notification->getHeading();
        $title    = $notification->getHeading();
        $url      = $notification->getUrl();
        $button   = $notification->getButton();
        $message  = $notification->getMessage();

        if (!is_array($playerId)) {
            $playerId = [$playerId];
        }

        foreach ($playerId as $token){
            $data = [];
           
            $data['android']['data'] = [
                //'title' => $title,
                //'body' => $message,                
                'notification_id' => (string)$notificationId
            ];
            $data['apns']['headers'] = [
                "apns-priority"=> "5"
            ];
            $data['apns']['payload']['aps']['alert'] = [
                //'title' => $title,
                //'body' => $message,                
                'notification_id' => (string)$notificationId
            ];
            $data['webpush']['data'] = [
                'title' => $title,
                'body' => $message,
                'notification_id' => (string)$notificationId
            ];

            if (!empty($this->notificationIcon)){
                $data['data']['icon'] = $this->notificationIcon;
                $data['android']['data']['icon'] = $this->notificationIcon;
                $data['apns']['payload']['aps']['alert']['icon'] = $this->notificationIcon;
                $data['webpush']['data']['icon'] = $this->notificationIcon;                
            }


            if (!empty($url)) {                
                $data['data']['url'] = $url;
                $data['android']['data']['url'] = $url;
                $data['apns']['payload']['aps']['alert']['url'] = $url;
                $data['webpush']['data']['url'] = $url;

                $data['data']['click_action'] = $url;
                $data['android']['data']['click_action'] = $url;
                $data['apns']['payload']['aps']['alert']['click_action'] = $url;
                $data['webpush']['data']['click_action'] = $url;
            }             
            
            if (!empty($button)) {
                if (!empty($url)){
                    $data['data']['button'] = ['id' => $buttonId, 'text' => $button, 'url' => $url];    
                    $data['android']['data']['button'] = ['id' => $buttonId, 'text' => $button, 'url' => $url];    
                    $data['apns']['payload']['aps']['alert']['button'] = ['id' => $buttonId, 'text' => $button, 'url' => $url];    
                    $data['webpush']['data']['button'] = ['id' => $buttonId, 'text' => $button, 'url' => $url];    
                }else{
                    $data['data']['button']= ['id' => $buttonId, 'text' => $button];    
                    $data['android']['data']['button'] = ['id' => $buttonId, 'text' => $button];    
                    $data['apns']['payload']['aps']['alert']['button'] = ['id' => $buttonId, 'text' => $button];    
                    $data['webpush']['data']['button'] = ['id' => $buttonId, 'text' => $button];    
                }
            }
            
            if ($notification->isMobile()) {            
                $this->addMobileData($data, $notification->getMobileSettings());
            }



            $result = $this->send($token, $data);
        }

        //returning only last result;
        return $result;
    }

    /**
     * @param array $data
     * @param array $mobileConfig
     */
    protected function addMobileData(array &$data, array $mobileConfig)
    {
        foreach ($mobileConfig as $key => $value) {
            switch ($key) {
                case 'ios_subtitle':
                    $data['apns']['payload']['aps']['alert']['subtitle'] = $value;
                    break;
                case 'ios_sound':
                    $data['apns']['payload']['aps']['alert']['ios_sound'] = $value ?: 'default';
                    break;
                case 'ios_badges':
                    $data['apns']['payload']['aps']['alert']['ios_badgeType'] = $value;
                    break;
                case 'ios_badgeCount':
                    $data['apns']['payload']['aps']['alert']['ios_badgeCount'] = (int) $value;
                    break;
                case 'ios_contentAvailable':
                    $data['apns']['payload']['aps']['alert']['content_available'] = (bool) $value;
                    break;
                case 'ios_media':
                    $data['apns']['payload']['aps']['alert']['ios_attachments'] = [uniqid('id_') => $value];
                    break;
                case 'ios_mutableContent':
                    $data['apns']['payload']['aps']['alert']['mutable_content'] = (bool) $value;
                    break;
                case 'android_sound':
                    $data['android']['data']['android_sound'] = $value;
                    break;
                case 'android_small_icon':
                    $data['android']['data']['small_icon'] = $value;
                    break;
                case 'android_large_icon':
                    $data['android']['data']['large_icon'] = $value;
                    break;
                case 'android_big_picture':
                    $data['android']['data']['big_picture'] = $value;
                    break;
                case 'android_led_color':
                    $data['android']['data']['android_led_color'] = 'FF'.strtoupper($value);
                    break;
                case 'android_accent_color':
                    $data['android']['data']['android_accent_color'] = 'FF'.strtoupper($value);
                    break;
                case 'android_group_key':
                    $data['android']['data']['android_group'] = $value;
                    break;
                case 'android_lockscreen_visibility':
                    $data['android']['data']['android_visibility'] = (int) $value;
                    break;
                case 'additional_data':
                    $data['data'] = array_merge($data['data'], $value['list']);
                    break;
            }
        }
    }
}
