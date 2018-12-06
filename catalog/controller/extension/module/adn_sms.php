<?php
/**
 * Created by PhpStorm.
 * User: rejvi
 * Date: 11/20/18
 * Time: 11:12 AM
 */
include('library/adn_sms_class/lib/AdnSmsNotification.php');
use AdnSms\AdnSmsNotification;
class ControllerExtensionModuleADNSms extends Controller {

    public function eventModelAccountCustomerAdd($route, $args, $output){
        $this->load->model('account/customer');

        $customer_info = $this->model_account_customer->getCustomer($output);
        $settings = $this->db->query("SELECT * FROM " . DB_PREFIX . "adn_sms_setting WHERE type = 'register'");
        if($settings->row['status']==0){
            $this->load->model('setting/setting');
            $apiInfo=$this->model_setting_setting->getSetting('adn_sms');
            if($apiInfo!=null) {
                $sms = New AdnSmsNotification($apiInfo['adn_sms_api_url'], $apiInfo['adn_sms_auth_key'], $apiInfo['adn_sms_auth_secret']);
                $recipient = $customer_info['telephone'];       // For SINGLE_SMS or OTP
                $requestType = 'SINGLE_SMS';    // options available: "SINGLE_SMS", "OTP"
                $messageType = 'TEXT';         // options available: "TEXT", "UNICODE"
                $message = $settings->row['message'];

                $costumer_name = $customer_info['firstname'].' '.$customer_info['lastname']; //customer name
                $site_name=$this->config->get('config_name');

                if (strpos($message, '[USER_NAME]') !== false) {
                    $message = str_replace("[USER_NAME]",$costumer_name,$message);
                }
                if (strpos($message, '[SITE_NAME]') !== false) {
                    $message = str_replace("[SITE_NAME]",$site_name,$message);
                }

                $sms->sendSms($requestType, $message, $recipient, $messageType);
            }
        }

    }
    public function eventModelAccountEditPassword($route, $args, $output){
        $this->load->model('account/customer');
        $customer_info = $this->model_account_customer->getCustomerByEmail($args[0]);
        $settings = $this->db->query("SELECT * FROM " . DB_PREFIX . "adn_sms_setting WHERE type = 'password_reset'");
        if($settings->row['status']==0){
            $this->load->model('setting/setting');
            $apiInfo=$this->model_setting_setting->getSetting('adn_sms');
            if($apiInfo!=null) {
                $sms = New AdnSmsNotification($apiInfo['adn_sms_api_url'], $apiInfo['adn_sms_auth_key'], $apiInfo['adn_sms_auth_secret']);
                $recipient = $customer_info['telephone'];       // For SINGLE_SMS or OTP
                $requestType = 'SINGLE_SMS';    // options available: "SINGLE_SMS", "OTP"
                $messageType = 'TEXT';         // options available: "TEXT", "UNICODE"
                $message = $settings->row['message'];

                $costumer_name = $customer_info['firstname'].' '.$customer_info['lastname']; //customer name
                $site_name=$this->config->get('config_name');

                if (strpos($message, '[USER_NAME]') !== false) {
                    $message = str_replace("[USER_NAME]",$costumer_name,$message);
                }
                if (strpos($message, '[SITE_NAME]') !== false) {
                    $message = str_replace("[SITE_NAME]",$site_name,$message);
                }

                $sms->sendSms($requestType, $message, $recipient, $messageType);
            }
        }

    }
    public function eventModelCheckoutOrderStatus($route, $args){
        if (isset($args[1])) {
            $order_status_id = $args[1];
            if ($order_status_id == 1) {
                $type = 'pending';
            } else if ($order_status_id == 2) {
                $type = 'processing';
            } else if ($order_status_id == 3) {
                $type = 'shipped';
            } else if ($order_status_id == 5) {
                $type = 'complete';
            } else if ($order_status_id == 7) {
                $type = 'cancelled';
            } else if ($order_status_id == 10) {
                $type = 'failed';
            } else if ($order_status_id == 11) {
                $type = 'refunded';
            } else {
                $type = null;
            }
        }
        if (isset($args[0])) {
            $order_id = $args[0];

            $order_info = $this->model_checkout_order->getOrder($order_id);


            if($type!=null){

                $settings = $this->db->query("SELECT * FROM " . DB_PREFIX . "adn_sms_setting WHERE type = '".$type."'");
                if ($settings->row['status'] == 0) {
                    $this->load->model('setting/setting');
                    $apiInfo = $this->model_setting_setting->getSetting('adn_sms');
                    if ($apiInfo != null) {
                        $sms = New AdnSmsNotification($apiInfo['adn_sms_api_url'], $apiInfo['adn_sms_auth_key'], $apiInfo['adn_sms_auth_secret']);
                        $recipient = $order_info['telephone'];       // For SINGLE_SMS or OTP
                        $requestType = 'SINGLE_SMS';    // options available: "SINGLE_SMS", "OTP"
                        $messageType = 'TEXT';         // options available: "TEXT", "UNICODE"
                        $message = $settings->row['message'];
                        $amount = $order_info['total'];
                        $orderID = $order_info['order_id'];
                        $costumer_name = $order_info['firstname'] . ' ' . $order_info['lastname']; //customer name
                        $site_name = $this->config->get('config_name');

                        //dynamic sms body
                        if (strpos($message, '[USER_NAME]') !== false) {
                            $message = str_replace("[USER_NAME]", $costumer_name, $message);
                        }
                        if (strpos($message, '[AMOUNT]') !== false) {
                            $message = str_replace("[AMOUNT]", $amount, $message);
                        }
                        if (strpos($message, '[ORDER_ID]') !== false) {
                            $message = str_replace("[ORDER_ID]", $orderID, $message);
                        }
                        if (strpos($message, '[SITE_NAME]') !== false) {
                            $message = str_replace("[SITE_NAME]", $site_name, $message);
                        }

                        $sms->sendSms($requestType, $message, $recipient, $messageType);
                    }
                }
            }
        }

    }

}
