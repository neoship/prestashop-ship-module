<?php

include_once dirname(__FILE__) . '/../../lib/PestJSON.php';

class AdminNeoshipController extends ModuleAdminController {

    CONST SERVICE_URL = 'https://www.neoship.sk/';
    CONST OAUTH_URL   = 'oauth/v2';

    public $errors = array();

    public function __construct() {
        $this->bootstrap  = true;
        $this->display    = 'view';
        $this->meta_title = $this->l('Upload Order to Neoship');
        parent::__construct();
        if (!$this->module->active) {
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminHome'));
        }
    }

    public function renderView() {
        $packageMat = array();
        
        if (Tools::isSubmit('exportOrders')) {
            $orderData = Tools::getValue('ups-order');

            $_SESSION['ups-orders'] = $orderData;
        }

        $this->executeRest();

        $pest  = new PestJSON(self::SERVICE_URL . "publicapi/rest");
        $boxes = $pest->get('/packagemat/boxes');

        $packageMat['boxes'] = $boxes;

        $orderIDs = Tools::getValue('orders');

        $this->tpl_view_vars['orders']     = !empty($orderIDs) ? $this->getOrdersByIds($orderIDs) : array();
        $this->tpl_view_vars['packageMat'] = $packageMat;
        $this->tpl_view_vars['backLink']   = $this->context->link->getAdminLink('AdminOrders');


        $this->base_tpl_view = 'view.tpl';

        return parent::renderView();
    }

    public function executeRest() {
        $orderData    = isset($_SESSION['ups-orders']) ? $_SESSION['ups-orders'] : null;
        $clientID     = Configuration::get('CLIENT_ID'); //config
        $clientSecret = Configuration::get('CLIENT_SECRET'); //config
        $packageMat   = array();
        $result       = array();

        if ($clientID && $clientSecret && $orderData) {
            $redirect     = $this->getRedirectUrl();
            $code         = Tools::getValue('code');
            $this->errors = array();

            if ($code) {

                $url      = "/token?client_id=" . $clientID . "&client_secret=" . $clientSecret . "&grant_type=authorization_code&code=" . $code . "&redirect_uri=" . urlencode($redirect);
                $pestAuth = new Pest(self::SERVICE_URL . self::OAUTH_URL);

                try {
                    $token                  = json_decode($pestAuth->get($url));
                    $oauth["access_token"]  = $token->access_token;
                    $oauth["expires_in"]    = $token->expires_in;
                    $oauth["token_type"]    = $token->token_type;
                    $oauth["scope"]         = $token->scope;
                    $oauth["refresh_token"] = $token->refresh_token;
                    $_SESSION['oauth']      = $oauth;

                    Tools::redirect($redirect);
                } catch (Exception $ex) {
                    throw $ex;
                }
            } else {
                $username     = Configuration::get('CLIENT_USERNAME');
                $statesConfig = array();
                try {
                    $oauth = isset($_SESSION['oauth']) ? $_SESSION['oauth'] : null;

                    $data  = array();
                    if ($oauth) {
                        $data["access_token"]  = $oauth["access_token"];
                        $data["refresh_token"] = $oauth["refresh_token"];
                        $data["token_type"]    = $oauth["token_type"];
                        $data["expires_in"]    = $oauth["expires_in"];
                    }

                    $pest        = new PestJSON(self::SERVICE_URL . "api/rest");
                    $user        = $pest->get('/user/', $data);

                    $kreditlimit = (isset($user['kredit_limit'])) ? $user['kredit_limit'] : 0;
                    if ($username == $user['username'] && $user['kredit'] >= $kreditlimit) {
                        $this->user = $user;
                        $states     = $pest->get('/state/', $data);
                        $currencies = $pest->get('/currency/', $data);
                        $break      = false;
                        foreach ($orderData as $orderID => $package) {
                            $order = new Order((int)($orderID));
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

                            $numberArr = explode(" ", trim($number));
                            $number    = array_shift($numberArr);
                            $numberExt = implode(" ", $numberArr);
                            $numberExt = trim($numberExt);

                            $state = null;

                            foreach ($states as $s) {
                                $stateInstance = new State((int)$deliveryAddress->id_state);
                                if ($s['code'] == $stateInstance->iso_code) {
                                    $state = $s['id'];
                                    break;
                                }
                            }
                            
                            $currencyID = null;
                            foreach ($currencies as $cur) {
                                $currency = new Currency((int)$order->id_currency);
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
                                        'email'          => $order->mobile_theme,
                                        'phone'          => ($deliveryAddress->phone_mobile != '' ? $deliveryAddress->phone_mobile : $deliveryAddress->phone),
                                    ),
                                ),
                            );

//                            if (!empty($packageMat) && in_array($order->getPaymentShippingId(), $packageMat) && isset($package['packagematreciever'])) {
//                                $packageData['package']['packageMatRecieverName']     = $package['packagematreciever'];
//                                $packageData['package']['packageMatBox']              = $package['box'];
//                                $orderAttribute                                       = $order->getAttribute("neoship-packagemat");
//                                $publicpest                                           = new PestJSON(self::SERVICE_URL . "publicapi/rest");
//                                $packageMatInfo                                       = $publicpest->get('/packagemat/' . $orderAttribute->getValue());
//                                $packageData['package']['reciever']['name']           = $packageMatInfo['address']['name'];
//                                $packageData['package']['reciever']['company']        = $packageMatInfo['address']['company'];
//                                $packageData['package']['reciever']['street']         = $packageMatInfo['address']['street'];
//                                $packageData['package']['reciever']['city']           = $packageMatInfo['address']['city'];
//                                $packageData['package']['reciever']['houseNumber']    = $packageMatInfo['address']['houseNumber'];
//                                $packageData['package']['reciever']['houseNumberExt'] = $packageMatInfo['address']['houseNumberExt'];
//                                $packageData['package']['reciever']['zIP']            = $packageMatInfo['virtualzip'];
//                                $packageData['package']['reciever']['state']          = $packageMatInfo['address']['state']['id'];
//                            }

                            if (isset($package['cod-check'])) {
                                $packageData['package']['cashOnDeliveryPrice']    = $package['cod'];
                                $packageData['package']['cashOnDeliveryCurrency'] = $currencyID;
                            }

//                            $insurance_defaut_price = sfConfig::get('mod_nwsordershipadmin_insurance_default_price', false);
//                            if (isset($package['insurance-check']) || $insurance_defaut_price) {
//                                $insurance_price = (isset($package['insurance-check']) && $package['insurance-check']) ? $package['insurance'] : $insurance_defaut_price;
//                                if ($insurance_price) {
//                                    $packageData['package']['insurance']         = $insurance_price;
//                                    $packageData['package']['insuranceCurrency'] = $currencyID;
//                                }
//                            }
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

                            if (isset($package['mainpackage-check'])) {
                                $packageData['package']['mainPackageNumber'] = $package['mainpackage'];
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
                                $vn                                       = ($package['parts'] > 1) ? $package['variablenumber'] . $i : $package['variablenumber'];
                                $packageData['package']['variableNumber'] = $vn;
                                if (isset($packageData['package']['mainPackageNumber']) || ($package['parts'] > 1 && $i > 1)) {
                                    $packageData['package']['mainPackageNumber'] = (isset($packageData['package']['mainPackageNumber'])) ? $packageData['package']['mainPackageNumber'] : $package['variablenumber'] . 1;
                                    if ($package['parts'] > 1) {
                                        $packageData['package']['cashOnDeliveryPrice']    = null;
                                        $packageData['package']['cashOnDeliveryCurrency'] = null;
                                        $packageData['package']['insurance']              = null;
                                        $packageData['package']['insuranceCurrency']      = null;
                                    }
                                }
                                try {
                                    $result[$orderID]['result'][$i] = $pest->post('/package/' . '?' . http_build_query($data), $packageData);
                                } catch (Pest_Forbidden $ex) {
                                    $this->errors[] = $ex;
                                    $break          = true;
                                    break;
                                } catch (Pest_Json_Decode $ex) {
                                    $result[$orderID]['result'][$i] = "OK"; // @FIXME: check PEST version to implement follow redirects
                                } catch (Exception $ex) {
                                    $result[$orderID]['exception'][$i] = $ex;
                                }
                            }

                            if ($break) {
                                break;
                            }
                        }
                    } elseif ($username == $user['username'] && $user['kredit'] <= $kreditlimit) {
                        $this->errors[] = new \Exception("Your credit is low to import packages", 403);
                    } elseif ($username != $user['username']) {
                        $_SESSION['oauth'] = null;
                        throw new Pest_ClientError();
                        //redirect to login if wrong user
                    } else {
                        $this->errors[] = new \Exception("Other error", 403);
                    }
                } catch (Pest_ClientError $ex) {
                    if (in_array($pest->lastStatus(), array('401', '406')) || $username != $user['username']) {
                        $url = "/auth?client_id=" . $clientID . "&response_type=code&redirect_uri=" . urlencode($redirect);
                        Tools::redirect(self::SERVICE_URL . self::OAUTH_URL . $url);
                    }
                } catch (Pest_Exception $ex) {
                    echo "<pre>";
                    var_dump($ex);
                    $this->errors[] = $ex;
                    die();
                } catch (Exception $ex) {
                    throw $ex;
                }
            }
        }

        $this->tpl_view_vars['result'] = $result;
    }

    /**
     * Return redirect link
     *
     * @return string
     */
    private function getRedirectUrl()
    {
        $addUrl = '';
        foreach (Tools::getValue('orders') as $id) {
            $addUrl .= '&orders[]=' . $id;
        }

        return _PS_BASE_URL_ .'/admin3706dezat/'. $this->context->link->getAdminLink('AdminNeoship').$addUrl;
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
