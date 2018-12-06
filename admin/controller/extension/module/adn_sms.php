<?php
/**
 * Created by PhpStorm.
 * User: rejvi
 * Date: 11/20/18
 * Time: 11:12 AM
 */
include('../library/adn_sms_class/lib/AdnSmsNotification.php');
use AdnSms\AdnSmsNotification;
class ControllerExtensionModuleADNSms extends Controller {

    //   protected $version = '1.0.0';
    protected $error = array();

    public function index() {

        $this->load->language('extension/module/adn_sms');

        $this->document->setTitle(strip_tags($this->language->get('heading_title')));

        $this->getList();

    }
    protected function getList() {
        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        if (isset($this->session->data['success'])) {
            $data['success'] = $this->session->data['success'];

            unset($this->session->data['success']);
        } else {
            $data['success'] = '';
        }
        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text'      => $this->language->get('text_home'),
            'href'      => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text'      => $this->language->get('text_module'),
            'href'      => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true)
        );

        $data['breadcrumbs'][] = array(
            'text'      => $this->language->get('heading_title'),
            'href'      => $this->url->link('extension/module/adn_sms', 'user_token=' . $this->session->data['user_token'], true)
        );
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        $data['user_token'] = $this->session->data['user_token'];
        $settings = $this->db->query("SELECT * FROM " . DB_PREFIX . "adn_sms_setting");

        foreach($settings->rows as $setting){
            $data['settings'][$setting['type']]=  $setting;
        }
        $data['sms_balance']=$this->sms_balance();

        $this->load->model('setting/setting');
        $data['config']=$this->model_setting_setting->getSetting('adn_sms');

        $this->response->setOutput($this->load->view('extension/module/adn_sms', $data));


    }
    public function sms_balance()
    {
        $this->load->model('setting/setting');
        $apiInfo=$this->model_setting_setting->getSetting('adn_sms');

        if($apiInfo!=null){

            $sms= New AdnSmsNotification($apiInfo['adn_sms_api_url'],$apiInfo['adn_sms_auth_key'],$apiInfo['adn_sms_auth_secret']);

            $sms =json_decode($sms->checkBalance());
            if($sms->api_response_code==200){
                $smsInfo=array();
                if($sms->balance->sms!==null){
                    $smsInfo['balance']=$sms->balance->sms;
                }else{
                    $smsInfo['balance']='Postpaid';
                }
                $smsInfo['total_usage']=$sms->usage->total_usage;
                $smsInfo['current_month_usage']=$sms->usage->current_month_usage;
                $smsInfo['api_response_code']=$sms->api_response_code;
                return $smsInfo;
            }
            else{
                return $sms;
            }
        }
    }
    public function adnAjaxCustomSMS(){
        $this->load->model('setting/setting');
        $apiInfo=$this->model_setting_setting->getSetting('adn_sms');
        if($apiInfo!=null) {
            $sms = New AdnSmsNotification($apiInfo['adn_sms_api_url'], $apiInfo['adn_sms_auth_key'], $apiInfo['adn_sms_auth_secret']);
            $message = $_REQUEST['custom_msg'];

            if ($_REQUEST['type'] == 'single') {

                $recipient = $_REQUEST['number'];       // For SINGLE_SMS or OTP
                $requestType = 'SINGLE_SMS';    // options available: "SINGLE_SMS", "OTP"
                $messageType = 'TEXT';         // options available: "TEXT", "UNICODE"
                if ($recipient != null) {

                    $sms = $sms->sendSms($requestType, $message, $recipient, $messageType);
                    $result = json_decode($sms);
                    if (isset($result)) {
                        if ($result->api_response_code == 200) {
                            $array= array('status' => 1, 'massage' => 'SMS Send Successfully.');
                        } else {
                            $array= array('status' => 0, 'massage' => $result->error->error_message);
                        }
                    } else {
                        $array= array('status' => 0, 'massage' => 'Something went wrong please try again later');
                    }


                } else {

                    $array= array('status' => 0, 'massage' => 'Please Enter Your Number');
                }
            }else {
                $customers = $this->db->query("SELECT customer_id,telephone FROM " . DB_PREFIX . "customer");
                $new_arr = "";
                foreach ($customers->rows as $myrow) {
                    if (is_array($myrow)) {
                        $new_arr .= $myrow['telephone'] . ',';
                    }
                }
                $recipient = substr($new_arr, 0, strlen($new_arr) - 1);// For bulk sms i.e. general campaign

                $messageType = 'TEXT'; // option available: "TEXT", "UNICODE"
                $campaignTitle = $_REQUEST['campaign_title']; // set a meaningful campaign title
                if ($recipient != null) {
                    $sms = $sms->sendBulkSms($message, $recipient, $messageType, $campaignTitle);

                    $result = json_decode($sms);
                    if ($result->api_response_code == 200) {
                        $array=array('status' => 1, 'massage' => $_REQUEST['campaign_title'] . ' Campaign Successfully Completed.');
                    } else {
                        $array=array('status' => 0, 'massage' => $result->error->error_message);
                    }


                } else {
                    $array=array('status' => 1, 'massage' => 'No Number Found.');
                }
            }
        }

        $this->response->setOutput(json_encode($array));
    }
    public function adnAjaxConfig() {
        if ($this->validatePermission()) {
            $this->load->model('setting/setting');
            $this->load->language('extension/module/adn_sms');
            if($_REQUEST['type']=='live'){
                $url='https://portal.adnsms.com';
            }else{
                $url='http://xplorer.rocks/';
            }
            $postData['adn_sms_type']=$_REQUEST['type'];
            $postData['adn_sms_api_url']=$url;
            $postData['adn_sms_auth_key']=$_REQUEST['auth_key'];
            $postData['adn_sms_auth_secret']=$_REQUEST['auth_secret'];
            $this->model_setting_setting->editSetting('adn_sms', $postData);

            $this->session->data['success'] = $this->language->get('text_success');
            $this->response->setOutput(json_encode());
        }
    }
    public function adnAjaxNotification() {
        if ($this->validatePermission()) {
            $this->load->language('extension/module/adn_sms');
            $this->db->query("UPDATE " . DB_PREFIX . "adn_sms_setting SET status = '" . $_REQUEST['send_sms_registration'] . "', message ='" . $_REQUEST['registration_msg'] . "' WHERE type = 'register'");
            $this->db->query("UPDATE " . DB_PREFIX . "adn_sms_setting SET status = '" . $_REQUEST['send_sms_password_reset'] . "', message ='" . $_REQUEST['password_reset_msg'] . "' WHERE type = 'password_reset'");
            $this->db->query("UPDATE " . DB_PREFIX . "adn_sms_setting SET status = '" . $_REQUEST['send_sms_shipped'] . "', message ='" . $_REQUEST['shipped_msg'] . "' WHERE type = 'shipped'");
            $this->db->query("UPDATE " . DB_PREFIX . "adn_sms_setting SET status = '" . $_REQUEST['send_sms_pending'] . "', message ='" . $_REQUEST['pending_msg'] . "' WHERE type = 'pending'");
            $this->db->query("UPDATE " . DB_PREFIX . "adn_sms_setting SET status = '" . $_REQUEST['send_sms_processing'] . "', message ='" . $_REQUEST['processing_msg'] . "' WHERE type = 'processing'");
            $this->db->query("UPDATE " . DB_PREFIX . "adn_sms_setting SET status = '" . $_REQUEST['send_sms_failed'] . "', message ='" . $_REQUEST['failed_msg'] . "' WHERE type = 'failed'");
            $this->db->query("UPDATE " . DB_PREFIX . "adn_sms_setting SET status = '" . $_REQUEST['send_sms_complete'] . "', message ='" . $_REQUEST['complete_msg'] . "' WHERE type = 'complete'");
            $this->db->query("UPDATE " . DB_PREFIX . "adn_sms_setting SET status = '" . $_REQUEST['send_sms_cancelled'] . "', message ='" . $_REQUEST['cancelled_msg'] . "' WHERE type = 'cancelled'");
            $this->db->query("UPDATE " . DB_PREFIX . "adn_sms_setting SET status = '" . $_REQUEST['send_sms_refunded'] . "', message ='" . $_REQUEST['refunded_msg'] . "' WHERE type = 'refunded'");

            $this->session->data['success'] = $this->language->get('text_success');
            $this->response->setOutput(json_encode());
        }
    }

    protected function validatePermission() {
        if (!$this->user->hasPermission('modify', 'extension/module/adn_sms')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if (!$this->error) {
            return true;
        } else {
            return false;
        }
    }

    public function install() {
        if (!$this->user->hasPermission('modify', 'extension/extension/module')) {
            return;
        }

        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "adn_sms_setting` (
		  `adn_sms_id` int(11) NOT NULL AUTO_INCREMENT,
		  `type` varchar(255) NOT NULL,
		  `status` int(11) NOT NULL,
		  `name` varchar(32) NOT NULL,
		  `message` varchar(255) NOT NULL,
		  PRIMARY KEY (`adn_sms_id`)
		) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=4 ;");

        $this->db->query("INSERT INTO `" . DB_PREFIX . "adn_sms_setting` (`adn_sms_id`, `type`, `status`, `name`, `message`) VALUES
			(1, 'register', 0, 'Send SMS on Registration ', 'Dear [USER_NAME], thank you for registering at [SITE_NAME].'),
			(2, 'password_reset', 0, 'Send SMS on Password Reset', 'Dear [USER_NAME], you have reset your password successfully. If it’s not you please contact our Operators.'),
			(3, 'shipped', 0, 'Send SMS on Shipped', 'Dear [USER_NAME], your order [ORDER_ID] has been shipped successfully!'),
			(4, 'refunded', 0, 'Send SMS on Refunded', 'Dear [USER_NAME], sorry for the inconvenience. We are processing your refund request against your order [ORDER_ID].'),
			(5, 'pending', 0, 'Send SMS on Pending', 'Dear [USER_NAME], your order [ORDER_ID] is in review now! Please keep patience.'),
			(6, 'processing', 0, 'Send SMS on Processing', 'Dear [USER_NAME], thanks for confirming your payment [AMOUNT] against the order [ORDER_ID].'),
			(7, 'failed', 0, 'Send SMS on Failed', 'Dear [USER_NAME], we are sorry that your order [ORDER_ID] submission is failed!'),
			(8, 'complete', 0, 'Send SMS on Complete', 'Dear [USER_NAME], thank you for shopping with us. We are happy delivering you the service!'),
			(9, 'cancelled', 0, 'Send SMS on Cancelled', 'Dear [USER_NAME], we are sorry that the order [ORDER_ID] is cancelled!');
		");

        $this->load->model('setting/setting');
        $this->model_setting_setting->editSetting('module_adn_sms', ['module_adn_sms_status'=>1]);

        $this->load->model('setting/event');

        $this->model_setting_event->addEvent('module_adnsms', 'catalog/model/account/customer/addCustomer/after', 'extension/module/adn_sms/eventModelAccountCustomerAdd');
        $this->model_setting_event->addEvent('module_adnsms', 'catalog/model/account/customer/editPassword/after', 'extension/module/adn_sms/eventModelAccountEditPassword');
        $this->model_setting_event->addEvent('module_adnsms', 'catalog/model/checkout/order/addOrderHistory/after', 'extension/module/adn_sms/eventModelCheckoutOrderStatus');
    }
    public function uninstall() {
        if (!$this->user->hasPermission('modify', 'extension/extension/module')) {
            return;
        }

        $this->db->query("DROP TABLE IF EXISTS " . DB_PREFIX . "adn_sms_setting");

        $this->load->model('setting/event');

        $this->model_setting_event->deleteEventByCode('module_adnsms');

        $this->load->model('setting/setting');
        $this->model_setting_setting->deleteSetting('adn_sms');
        $this->model_setting_setting->deleteSetting('deleteSetting');
    }

}