<?php

namespace Neoship\Controller\Admin;

use Neoship\Entity\Package;
use Neoship\Entity\Packages;
use Neoship\Form\Type\PackagesFormType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use Neoship\Service\Neoshipapi;
use OpenSSLCertificateSigningRequest;

class NeoshipController extends FrameworkBundleAdminController
{
    public function printStickerZebraVertical(Request $request)
    {
        return $this->printSticker($request, 1);
    }

    public function printStickerZebraHorizontal(Request $request)
    {
        return $this->printSticker($request, 2);
    }

    public function printSticker(Request $request, $printType = 0) {
        $orders = $this->getOrdersByIds( $request->request->get('order_orders_bulk', []) );
        $ref = array();
        foreach ($orders as $order) {
            $ref[] = $order['reference'];
        }
        try {
            $api = $this->get('neoship.neoshipapi');
            $api->login();
            $user_address = $api->printSticker( $printType, $ref );
        } catch (\Exception $e) {
            $this->addFlash('error', $e->getMessage());
            return $this->redirect( $this->getAdminLink('export_to_neoship_result', array()) );
        }
    }

    public function printGlsSticker(Request $request) {
        $orders = $this->getOrdersByIds( $request->request->get('order_orders_bulk', []) );
        $ref = array();
        foreach ($orders as $order) {
            $ref[] = $order['reference'];
        }
        
        try {
            $api = $this->get('neoship.neoshipapi');
            $api->login();
            $labelsErrors = $api->printGlsSticker( $ref );

            if ( $labelsErrors['labels'] != '' ) {
                $this->addFlash('success', '
                    <div id="neoship_download_glssticker_link">
                    </div>
                    <script>
                        var link = document.createElement("a");
                        link.classList.add("btn");
                        link.classList.add("btn-success");
                        link.innerHTML = "<strong>Opätovne vytlačiť vygenerované štítky</strong>";
                        link.download = "stickers.pdf";
                        link.href = "data:application/pdf;base64,' . $labelsErrors['labels'] . '";
                        link.click();
                        document.getElementById("neoship_download_glssticker_link").appendChild(link);
                    </script>
                ');
            }
            
            if ( count( $labelsErrors['errors'] ) > 0 ) {
                foreach ( $labelsErrors['errors'] as $key => $value ) {
                    $value = \is_array($value) ? implode( ', ', $value) : $value;
                    $this->addFlash('error', '<strong>' . $key . '</strong>: ' . $value );
                }
            }

        } catch (\Exception $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirect( $this->getAdminLink('export_to_neoship_result', array()) );
    }

    public function acceptanceProtocol(Request $request) {
        $orders = $this->getOrdersByIds( $request->request->get('order_orders_bulk', []) );
        $ref = array();
        foreach ($orders as $order) {
            $ref[] = $order['reference'];
        }
        try {
            $api = $this->get('neoship.neoshipapi');
            $api->login();
            $user_address = $api->printAcceptanceProtocol( $ref );
        } catch (\Exception $e) {
            $this->addFlash('error', $e->getMessage());
            return $this->redirect( $this->getAdminLink('export_to_neoship_result', array()) );
        }
    }

    public function export(Request $request)
    {
        $ids = $request->request->get('order_orders_bulk', []);
        if ( $request->request->has('packages') ) {
            $ids = [];
            foreach ($request->request->get('packages') as $value) {
                $ids[] = $value['id'];
            }
        }

        $orders = $this->getOrdersByIds( $ids );
        $packages = new Packages();
        
        $i = 0;
        foreach ($orders as $order) {
            $package = new Package();
            $package->setId($order['id_order']);
            $package->setVariableNumber($order['reference']);
            $package->setCodprice($order['total_paid']);
            if (strpos($order['module'], 'cashondelivery') !== false) {
                $package->setCod(true);
            }
            $package->setIndex($i);
            $packages->getPackages()->add($package);
            if ( \in_array( $order['alias'], [ 'gls_parcelshop', 'gls_courier' ] ) ) {
                $package->setIsGls(true);
            }
            $i++;
        }
        
        $form = $this->createForm(PackagesFormType::class, $packages);
        $form->handleRequest($request);
      
        if($form->isSubmitted() && $form->isValid())
        {
            $api = $this->get('neoship.neoshipapi');

            try {
                $api->login();
                $user_address = $api->getUserAddress();
                $states       = $api->getStatesIds();
                $currencies   = $api->getCurrenciesIds();
            } catch (\Exception $e) {
                $this->addFlash('error', $e->getMessage());
                return $this->redirect( $this->getAdminLink('export_to_neoship_result', array()) );
            }
            
            $packages = array();
            $glsPackages = array();
            
            foreach ($form->getData()->getPackages() as $package) {
                $deliveryStreet = trim($orders[$package->getIndex()]['address1']);
    
                if (is_numeric($deliveryStreet)) {
                    $str    = $orders[$package->getIndex()]['city'];
                    $number = $deliveryStreet;
                } elseif (preg_match("/(.*)\s([a-zA-Z]?[0-9\/]+[a-zA-Z]?)$/", $deliveryStreet, $addressText)) {
    
                    $str    = (is_numeric($addressText[1])) ? $orders[$package->getIndex()]['city'] : $addressText[1];
                    $str    = trim($str);
                    $number = (is_numeric($addressText[1])) ? $addressText[1] : isset($addressText[2]) ? $addressText[2] : '';
                    if (empty($str)) {
                        throw new Exception("Empty street");
                    }
    
                } elseif (count(explode(" ", $deliveryStreet)) == 1 || strpos($orders[$package->getIndex()]['alias'], 'Parcelshop') !== false) {
                    $str    = $deliveryStreet;
                    $number = '';
                } elseif (!empty($deliveryStreet)) {
                    $str = trim($deliveryStreet);
                } else {
                    throw new \Exception("Invalid street match - " . $deliveryStreet);
                }

                $numberArr = explode("/", trim($number));
                $number    = array_shift($numberArr);
                $numberExt = implode(" ", $numberArr);
                $numberExt = trim($numberExt);
       
                $packageData = array(
                    'package' => array(
                        'variableNumber' => $package->getVariableNumber(),
                        'insurance'         => $package->getInsurance(),
                        'sender'         => $user_address,
                        'reciever'       => array(
                            'name'           => $orders[$package->getIndex()]['deliveryName'],
                            //'company'        => $orders[$package->getIndex()]['company'],
                            'street'         => $str,
                            'city'           => $orders[$package->getIndex()]['city'],
                            'houseNumber'    => $number,
                            'houseNumberExt' => $numberExt,
                            'zIP'            => $orders[$package->getIndex()]['postcode'],
                            'state'          => $states[ $orders[ $package->getIndex() ]['iso_code'] ],
                            'email'          => $orders[$package->getIndex()]['email'],
                            'phone'          => ($orders[$package->getIndex()]['phone_mobile'] != '' ? $orders[$package->getIndex()]['phone_mobile'] : $orders[$package->getIndex()]['phone']),
                        ),
                    ),
                );
                
                if ( !$package->getIsGls() && strpos($orders[$package->getIndex()]['alias'], 'Parcelshop') !== false ) {
                    $name = \explode(' ', $orders[$package->getIndex()]['alias']);
                    $packageData['package']['parcelShopRecieverName'] = $orders[$package->getIndex()]['deliveryName'];
                    $packageData['package']['reciever']['name'] = $name[1];
                }
                else{
                    $packageData['package']['countOfPackages'] = $package->getparts();
                }

                if ($package->getCod()) {
                    $packageData['package']['cashOnDeliveryPrice']    = $package->getCodprice();
                }
                
                if ( !$package->getIsGls() ) {
                    $packageData['package']['reciever']['company'] = $orders[$package->getIndex()]['company'];
					$packageData['package']['insuranceCurrency'] = $currencies['EUR'];
					$packageData['package']['express'] = $package->getDelivery() ? $package->getDelivery() : null;
                    
					$packageData['cashOnDeliveryPayment'] = '';
                    if ($package->getCod()) {
                        $packageData['package']['cashOnDeliveryCurrency'] = $currencies[ $orders[ $package->getIndex() ]['currency'] ];
                    }
                    
                    $notification = array();
                    if ( $package->getEmail() ) {
                        $notification[] = 'email';
                    }
                    if ( $package->getSms() ) {
                        $notification[] = 'sms';
                    }
                    if ( $package->getPhone() ) {
                        $notification[] = 'phone';
                    }
    
                    $packageData['package']['notification'] = $notification;
                    
                    if ( $package->getSaturday() ) {
                        $packageData['package']['saturdayDelivery'] = true;
                    }
    
                    if ( $package->getHolddelivery() ) {
                        $packageData['package']['holdDelivery'] = true;
                    }
    
                    if ( $package->getAttachment() ) {
                        $packageData['package']['attachment'] = true;
                    }

                    $packages[] = $packageData;
                }
                else {

                    if ( $orders[$package->getIndex()]['alias'] == 'gls_parcelshop' ) {
						$packageData['package']['parcelshopId'] = $orders[$package->getIndex()]['address2'];
                    }
                    
					unset( $packageData['package']['sender']['company'] );

					$glsPackages[] = $packageData;
                }
            }
            
            $response = [];
            try {

                if ( count( $packages ) ) {
                    $response = array_merge( $response, $api->createPackages( $packages ) );
                }
                
                if ( count( $glsPackages ) ) {
                    $response = array_merge( $response, $api->createPackages( $glsPackages, true ) );
                }

            } catch (\Exception $e) {
                $this->addFlash('error', $e->getMessage());
                return $this->redirect( $this->getAdminLink('export_to_neoship_result', array()) );
            }
            
            foreach ( $response as $value ) {
                $res = json_decode( $value['responseContent'], true );
                $status = null;
				if ( 201 === $value['responseCode'] ) {
                    if ($status == null) {
                        $status = $this->getState('Exportovaná do Neoshipu');
                    }
                    $history = new \OrderHistory();
                    foreach ($form->getData()->getPackages() as $package) {
                        if($package->getVariableNumber() == $res['variableNumber']) {
                            $history->id_order = (int)$orders[$package->getIndex()]['id_order'];
                            $history->changeIdOrderState($status['id_order_state'], (int)$orders[$package->getIndex()]['id_order']);
                            break;
                        }
                    }
                    $this->addFlash('success', '<strong>' . $res['variableNumber'] . '</strong>' . ': ' . 'Export úspešný');
				} else {
                    $this->addFlash('error', '<strong>' . $res['variableNumber'] . '</strong>' . ': ' . $res['result']);
				}
            }

            return $this->redirect( $this->getAdminLink('export_to_neoship_result', array()) );
        }

        return $this->render('@Modules/neoship/views/admin/export.html.twig', [
            'form' => $form->createView(),
            'orders' => $orders,
            'backLink' => $this->getAdminLink('AdminOrders', array()),
        ]);
    }

    public function result(Request $request)
    {
        return $this->render('@Modules/neoship/views/admin/result.html.twig', [
            'backLink' => $this->getAdminLink('AdminOrders', array()),
        ]);
    }


    /**
     * Get all orders by IDs
     *
     * @param integer $id_lang Language id for status name
     *
     * @return array Orders
     */
    private function getOrdersByIds($requestIds = array()) {
        $orderIDs = array_map(function ($oId) {
            return (int) $oId;
        }, $requestIds);

        $em = $this->getDoctrine()->getManager();

        foreach ($orderIDs as &$id) {
            $id = intval($id);
        }

        $query = '
            SELECT o.id_order, o.reference, o.total_paid, o.date_add, o.module, o.id_address_delivery, CONCAT(a.firstname, " ", a.lastname) AS deliveryName,
                    a.address1, a.address2, a.alias, a.city, a.postcode, c.iso_code, a.phone_mobile, a.phone, cm.email, a.company, crc.iso_code as currency
			FROM `' . _DB_PREFIX_ . 'orders` o
			LEFT JOIN `' . _DB_PREFIX_ . 'address` a ON o.id_address_delivery = a.id_address
			LEFT JOIN `' . _DB_PREFIX_ . 'country` c ON a.id_country = c.id_country
			LEFT JOIN `' . _DB_PREFIX_ . 'customer` cm ON cm.id_customer = o.id_customer
			LEFT JOIN `' . _DB_PREFIX_ . 'currency` crc ON crc.id_currency = o.id_currency
            WHERE id_order IN ('.implode(',', $orderIDs).')
        ';

        $statement = $em->getConnection()->prepare($query);
        $statement->execute();

        return $statement->fetchAll();
    }

    /**
     * Create new order status if not exist and return it.
     *
     * @param integer $id_lang Language id for status name
     *
     * @return string name Status name.
     */
    private function getState($name)
    {
        $state_exist = false;
        $states = \OrderState::getOrderStates(1);
 
        // check if order state exist
        foreach ($states as $state) {
            if ($state['name'] == $name ) {
                return $state;
            }
        }

        if (!$state_exist) {        // If the state does not exist, we create it.

            // create new order state
            $order_state = new \OrderState();
            $order_state->color = '#ed4f2f';
            $order_state->send_email = false;
            $order_state->module_name = 'neoship';
            //$order_state->template = 'name of your email template';
            $order_state->name = array();
            $languages = \Language::getLanguages(false);
            foreach ($languages as $language)
                $order_state->name[ $language['id_lang'] ] = $name;
 
            // Update object
            $order_state->add();
            return $order_state;
        }
    }

    public function tracking(Request $request)
    {
        $orderId = $request->query->get('orderId', null);
        $order = new \Order($orderId);
        $api = $this->get('neoship.neoshipapi');
        $addressDelivery = new \Address( $order->id_address_delivery );

        try {
            $api->login();
            $userID = $api->getUserId();

            if ( in_array( $addressDelivery->alias, [ 'gls_parcelshop', 'gls_courier' ] ) ) {
                $url = NEOSHIP_TRACKING_URL . '/glstracking/packageReference/' . $userID . '/' . $order->reference;
            } else{
                $url = NEOSHIP_TRACKING_URL . '/tracking/packageReference/' . $userID . '/' . $order->reference;
            }
            return $this->redirect($url);
        } catch (\Exception $e) {
            unset($e);
            return null;
        }
    }
}