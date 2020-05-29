<?php

include_once dirname(__FILE__) . '/../../lib/PestJSON.php';

class AdminNeoshipController extends ModuleAdminController {

    CONST SERVICE_URL = 'https://api.neoship.sk/';
    CONST OAUTH_URL   = 'oauth/v2';

    public function __construct() {
        $this->bootstrap  = true;
        $this->display    = 'view';
        $this->meta_title = $this->l('Upload Order to Neoship');
        parent::__construct();
        if (!$this->module->active) {
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminHome'));
        }
    }

    public function initToolBarTitle() {
        $this->toolbar_title[] = $this->l('Export Order to Neoship');
    }

    public function renderView() {
        $restResponse = null;
        $packageMat   = array();

        if (Tools::isSubmit('exportOrders') || (isset($_SESSION['oauth']) && !isset($_SESSION['getOauth'])) || (!isset($_SESSION['oauth']) && isset($_SESSION['getOauth'])) || isset($_SESSION['refreshOauth'])) {
            if (Tools::getValue('ups-order')) {
                $orderData = Tools::getValue('ups-order');

                $_SESSION['ups-orders'] = $orderData;
            }

            $restResponse = $this->restAction();
        }

        $packageMat['boxes'] = [];

        $orderIDs = Tools::getValue('orders');

        $this->tpl_view_vars['orders']     = !empty($orderIDs) ? $this->getOrdersByIds($orderIDs) : array();
        $this->tpl_view_vars['packageMat'] = $packageMat;
        $this->tpl_view_vars['backLink']   = $this->context->link->getAdminLink('AdminOrders');

        $this->base_tpl_view = 'view.tpl';

        if ($restResponse) {
            $this->tpl_view_vars['result'] = $restResponse;
            $this->base_tpl_view           = 'view-result.tpl';
        }

        return parent::renderView();
    }

    private function restAction() {
        $_SESSION['getOauth'] = true;
        $orderData            = isset($_SESSION['ups-orders']) ? $_SESSION['ups-orders'] : null;
        $clientID             = Configuration::get('CLIENT_ID'); //config
        $clientSecret         = Configuration::get('CLIENT_SECRET'); //config
        $result               = array();
        if ($clientID && $clientSecret) {
            $redirect = $this->getRedirectUrl();
            $oauth    = isset($_SESSION['oauth']) ? $_SESSION['oauth'] : null;
            
            
            if ($oauth == null) {
                $url      = "/token?client_id=" . $clientID . "&client_secret=" . $clientSecret . "&grant_type=client_credentials";
                $restAuth = new Pest(self::SERVICE_URL . self::OAUTH_URL);
                
                try {
                    $token                  = json_decode($restAuth->get($url));
                    $oauth["access_token"]  = $token->access_token;
                    $oauth["expires_in"]    = $token->expires_in;
                    $oauth["token_type"]    = $token->token_type;
                    $oauth["scope"]         = $token->scope;
                    $_SESSION['oauth']      = $oauth;
                    
                    //if exist oauth unset getOauth parameter
                    unset($_SESSION['getOauth']);
                    unset($_SESSION['refreshOauth']);
                    
                    return $this->restAction();
                } catch (Exception $ex) {
                    throw $ex;
                }
                
            } else {
                $username = Configuration::get('CLIENT_USERNAME');
                try {

                    $data = array();
                    $data["access_token"]  = $oauth["access_token"];
                    $data["token_type"]    = $oauth["token_type"];
                    $data["expires_in"]    = $oauth["expires_in"];
                    
                    $rest = new PestJSON(self::SERVICE_URL);
                    $user = $rest->get('/user/', $data);

                    if ($username == $user['username']) {
                        $states     = $rest->get('/state/', $data);
                        $currencies = $rest->get('/currency/', $data);
                        $break      = false;

                        foreach ($orderData as $orderID => $package) {
                            $order           = new Order((int) ($orderID));
                            $customer = new Customer((int)$order->id_customer);
                            $deliveryAddress = new Address($order->id_address_delivery);

                            $deliveryStreet = trim($deliveryAddress->address1);

                            if (is_numeric($deliveryStreet)) {
                                $str    = $deliveryAddress->city;
                                $number = $deliveryStreet;
                            } elseif (preg_match("/(.*)\s([a-zA-Z]?[0-9\/]+[a-zA-Z]?)$/", $deliveryStreet, $addressText)) {

                                $str    = (is_numeric($addressText[1])) ? $deliveryAddress->city : $addressText[1];
                                $str    = trim($str);
                                $number = (is_numeric($addressText[1])) ? $addressText[1] : isset($addressText[2]) ? $addressText[2] : '';
                                if (empty($str)) {
                                    throw new Exception("Empty street");
                                }
                            } elseif (count(explode(" ", $deliveryStreet) == 1)) {
                                $str    = $deliveryStreet;
                                $number = '';
                            } else {
                                throw new Exception("Invalid street match - " . $deliveryStreet);
                            }

                            $numberArr = explode("/", trim($number));
                            $number    = array_shift($numberArr);

                            $numberExt = implode(" ", $numberArr);
                            $numberExt = trim($numberExt);

                            $state = null;
                            foreach ($states as $s) {
                                $country = new Country((int) $deliveryAddress->id_country);
                                if ($s['code'] == $country->iso_code) {
                                    $state = $s['id'];
                                    break;
                                }
                            }

                            $currencyID = null;
                            foreach ($currencies as $cur) {
                                $currency = new Currency((int) $order->id_currency);
                                if ($currency->iso_code == $cur['code']) {
                                    $currencyID = $cur['id'];
                                    break;
                                }
                            }

                            $packageData = array(
                                'package' => array(
                                    'variableNumber' => $package['variablenumber'],
                                    'sender'         => array(
                                        'name'           => $user['address']['name'],
                                        'company'        => (isset($user['address']['company'])) ? $user['address']['company'] : null,
                                        'street'         => $user['address']['street'],
                                        'city'           => $user['address']['city'],
                                        'houseNumber'    => $user['address']['houseNumber'],
                                        'houseNumberExt' => (isset($user['address']['hoseNumberExt'])) ? $user['address']['houseNumberExt'] : null,
                                        'zIP'            => $user['address']['zip'],
                                        'state'          => $user['address']['state']['id'],
                                        'email'          => (isset($user['address']['email'])) ? $user['address']['email'] : null,
                                        'phone'          => (isset($user['address']['phone'])) ? $user['address']['phone'] : null,
                                    ),
                                    'reciever'       => array(
                                        'name'           => $deliveryAddress->firstname . ' ' . $deliveryAddress->lastname,
                                        'company'        => '',
                                        'street'         => $str,
                                        'city'           => $deliveryAddress->city,
                                        'houseNumber'    => $number,
                                        'houseNumberExt' => $numberExt,
                                        'zIP'            => $deliveryAddress->postcode,
                                        'state'          => $state,
                                        'email'          => $customer->email,
                                        'phone'          => ($deliveryAddress->phone_mobile != '' ? $deliveryAddress->phone_mobile : $deliveryAddress->phone),
                                    ),
                                ),
                            );

                            if (isset($package['cod-check'])) {
                                $packageData['package']['cashOnDeliveryPrice']    = $package['cod'];
                                $packageData['package']['cashOnDeliveryCurrency'] = $currencyID;
                            }

                            if (isset($package['notification']) && !empty($package['notification'])) {
                                $packageData['package']['notification'] = $package['notification'];
                            }

                            if (isset($package['express'])) {
                                $packageData['package']['express'] = $package['express'];
                            }

                            if (isset($package['saturday'])) {
                                $satval                                     = isset($package['saturday']) ? "1" : null;
                                $packageData['package']['saturdayDelivery'] = $satval;
                            }

                            if (isset($package['reverse'])) {
                                $reverseval                        = isset($package['reverse']) ? "1" : null;
                                $packageData['package']['reverse'] = $reverseval;
                            }

                            if (isset($package['attachment'])) {
                                $attachmentval                        = isset($package['attachment']) ? "1" : null;
                                $packageData['package']['attachment'] = $attachmentval;
                            }

                            $result[$orderID]['data'] = $packageData;
                            for ($i = 1; $i <= $package['parts']; $i++) {
                                $index = $i-1;
                                $vn                                       = (($package['parts'] > 1) && ($i != 1)) ? $package['variablenumber'] . $index : $package['variablenumber'];
                                $packageData['package']['variableNumber'] = $vn;
                                if (isset($packageData['package']['mainPackageNumber']) || ($package['parts'] > 1 && $i > 1)) {
                                    $packageData['package']['mainPackageNumber'] = (isset($packageData['package']['mainPackageNumber'])) ? $packageData['package']['mainPackageNumber'] : $package['variablenumber'];
                                    if ($package['parts'] > 1) {
                                        $packageData['package']['cashOnDeliveryPrice']    = null;
                                        $packageData['package']['cashOnDeliveryCurrency'] = null;
                                        $packageData['package']['insurance']              = null;
                                        $packageData['package']['insuranceCurrency']      = null;
                                    }
                                }

                                try {
                                    $result[$orderID]['result'][$i] = $rest->post('/package/' . '?' . http_build_query($data), $packageData);
                                    unset($_SESSION['ups-orders'][$orderID]);
                                } catch (Pest_Forbidden $ex) {
                                    $message = json_decode($ex->getMessage());

                                    if (isset($message->message)) {
                                        $result[$orderID]['exception'][$i] = $message->message;
                                    }
                                } catch (Pest_Json_Decode $ex) {
                                    $result[$orderID]['result'][$i] = "OK";
                                } catch (Exception $ex) {
                                    $message = json_decode($ex->getMessage());

                                    if (isset($message->message)) {
                                        $result[$orderID]['exception'][$i] = $message->message;
                                    }

                                    if (isset($message->errors)) {
                                        foreach ($message->errors as $err) {
                                            foreach ($err as $msg) {
                                                if (is_string($msg)) {
                                                    $result[$orderID]['exception'][$i] = $msg;
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    } elseif ($username != $user['username']) {
                        $_SESSION['oauth'] = null;
                        throw new Pest_ClientError();
                    } else {
                        $this->errors[] = $this->l("Error - contact your API provider");
                    }
                } catch (Pest_ClientError $ex) {
                    if (in_array($rest->lastStatus(), array('401', '406')) || $username != $user['username']) {
                        $url      = "/token?client_id=" . $clientID . "&client_secret=" . $clientSecret . "&grant_type=client_credentials";
                        $restAuth = new Pest(self::SERVICE_URL . self::OAUTH_URL);

                        try {
                            $token                  = json_decode($restAuth->get($url));
                            $oauth["access_token"]  = $token->access_token;
                            $oauth["expires_in"]    = $token->expires_in;
                            $oauth["token_type"]    = $token->token_type;
                            $oauth["scope"]         = $token->scope;
                            $_SESSION['oauth']      = $oauth;

                            unset($_SESSION['getOauth']);
                            unset($_SESSION['refreshOauth']);
                            Tools::redirect($redirect);
                        } catch (Exception $ex) {
                            throw $ex;
                        }
                    }
                } catch (Pest_Exception $ex) {
                    $this->errors[] = $ex->getMessage();
                } catch (Exception $ex) {
                    throw $ex;
                }
            }
        }

        return $result;
    }

    /**
     * Return redirect link
     *
     * @return string
     */
    private function getRedirectUrl() {
        $scriptNameItems = explode('/', $_SERVER['SCRIPT_NAME']);

        $prefix = isset($scriptNameItems[1]) ? $scriptNameItems[1] : 'admin';

//        return _PS_BASE_URL_ . '/' . $prefix . '/' . $this->context->link->getAdminLink('AdminNeoship');
        return $this->context->link->getAdminLink('AdminNeoship');
    }

    /**
     * Get all orders by IDs
     *
     * @param integer $id_lang Language id for status name
     *
     * @return array Orders
     */
    private function getOrdersByIds($orderIDs = array()) {
        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
			SELECT *, CONCAT(a.firstname, " ", a.lastname) AS deliveryName
			FROM `' . _DB_PREFIX_ . 'orders` o
			LEFT JOIN `' . _DB_PREFIX_ . 'address` a ON o.id_address_delivery = a.id_address
			WHERE id_order IN (' . implode(',', $orderIDs) . ')');

        return $result;
    }
}
