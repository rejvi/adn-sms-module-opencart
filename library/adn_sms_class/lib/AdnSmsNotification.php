<?php
namespace AdnSms;

require_once(__DIR__ . "/AbstractAdnSms.php");


class AdnSmsNotification extends AbstractAdnSms
{
    /**
     * AdnSmsNotification constructor.
     */
    public function __construct($domain,$api_key,$api_secret)
    {
        $this->config = include(__DIR__ . '/../config/config.php');

//        $this->setApiKey($this->config['apiCredentials']['api_key']);
//        $this->setApiSecret($this->config['apiCredentials']['api_secret']);
        $this->setApiKey($api_key);
        $this->setApiSecret($api_secret);
        $this->setApiDomain($domain);
    }

    public function checkBalance()
    {
        $this->setApiUrl($this->config['apiUrl']['check_balance']);

        $data = [
            'api_key' => $this->getApiKey(),
            'api_secret' => $this->getApiSecret()
        ];

        return $this->callToApi($data);
    }

    public function sendSms($requestType, $message, $recipient, $messageType, $campaignTitle = null)
    {
        $this->setApiUrl($this->config['apiUrl']['send_sms']);
        $this->setRequestType($requestType);
        $this->setMessageBody($message);
        $this->setRecipient($recipient);
        $this->setMessageType($messageType);
        $this->setCampaignTitle($campaignTitle);

        $data = [
            'api_key' => $this->getApiKey(),
            'api_secret' => $this->getApiSecret(),
            'request_type' => $this->getRequestType(),
            'message_type' => $this->getMessageType(),
            'mobile' => $this->getRecipient(),
            'message_body' => $this->getMessageBody()
        ];

        if ($this->getRequestType() == 'GENERAL_CAMPAIGN') {
            if ($campaignTitle == null) {
                throw new \Exception('Campaign Title is required for bulk campaign');
            }
            $data['campaign_title'] = $this->getCampaignTitle();
        }

        return $this->callToApi($data);
    }

    public function sendBulkSms($message, $recipient, $messageType, $campaignTitle)
    {
        $this->setApiUrl($this->config['apiUrl']['send_sms']);
        $this->setRequestType('GENERAL_CAMPAIGN');
        $this->setMessageBody($message);
        $this->setRecipient($recipient);
        $this->setMessageType($messageType);
        $this->setCampaignTitle($campaignTitle);

        $data = [
            'api_key' => $this->getApiKey(),
            'api_secret' => $this->getApiSecret(),
            'request_type' => $this->getRequestType(),
            'message_type' => $this->getMessageType(),
            'mobile' => $this->getRecipient(),
            'message_body' => $this->getMessageBody()
        ];

        if ($campaignTitle == null) {
            throw new \Exception('Campaign Title is required for bulk campaign');
        }
        $data['campaign_title'] = $this->getCampaignTitle();

        return $this->callToApi($data);
    }

    public function multibodyCampaign($smsArray, $messageType, $campaignTitle = null)
    {
        $this->setApiUrl($this->config['apiUrl']['send_sms']);
        $this->setRequestType('MULTIBODY_CAMPAIGN');
        $this->setMessageType($messageType);
        $this->setCampaignTitle($campaignTitle);

        $data = [
            'api_key' => $this->getApiKey(),
            'api_secret' => $this->getApiSecret(),
            'request_type' => $this->getRequestType(),
            'message_type' => $this->getMessageType(),
        ];

        if ($campaignTitle != null) {
            $data['campaign_title'] = $this->getCampaignTitle();
        }

        foreach ($smsArray as $key => $sms) {
            if (!(isset($sms['mobile']) && isset($sms['message_body']))) {
                throw new \Exception('SMS Array format is not correct.');
            }
            $data["sms[$key][mobile]"] = $sms['mobile'];
            $data["sms[$key][message_body]"] = $sms['message_body'];
        }

        return $this->callToApi($data);
    }

    /**
     * @param $campaignUid
     */
    public function checkCampaignStatus($campaignUid)
    {
        $this->setApiUrl($this->config['apiUrl']['check_campaign_status']);

        $data = [
            'api_key' => $this->getApiKey(),
            'api_secret' => $this->getApiSecret(),
            'campaign_uid' => $campaignUid
        ];


       return $this->callToApi($data);
    }

    /**
     * @param $smsUid
     */
    public function checkSmsStatus($smsUid)
    {
        $this->setApiUrl($this->config['apiUrl']['check_sms_status']);

        $data = [
            'api_key' => $this->getApiKey(),
            'api_secret' => $this->getApiSecret(),
            'sms_uid' => $smsUid
        ];

        return $this->callToApi($data);
    }
}
