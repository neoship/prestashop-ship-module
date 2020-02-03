<?php

namespace Neoship\Controller\Admin;

use Neoship\Entity\Package;
use Neoship\Entity\Packages;
use Neoship\Form\Type\PackagesFormType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use Neoship\Service\Neoshipapi;

class NeoshipController extends FrameworkBundleAdminController
{

    public function printSticker(Request $request) {
        $orders = $this->getOrdersByIds( $request->query->get('orders') );
        $ref = array();
        foreach ($orders as $order) {
            $ref[] = $order['reference'];
        }
        try {
            $api = $this->get('neoship.neoshipapi');
            $api->login();
            $user_address = $api->printSticker( intval($request->query->get('printtype')), $ref );
        } catch (\Exception $e) {
            $this->addFlash('error', $e->getMessage());
            return $this->redirect( $this->getAdminLink('export_to_neoship_result', array()) );
        }
    }

    public function acceptanceProtocol(Request $request) {
        $orders = $this->getOrdersByIds( $request->query->get('orders') );
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
        $orders = $this->getOrdersByIds( $request->query->get('orders') );
        $packages = new Packages();
        
        $i = 0;
        foreach ($orders as $order) {
            $package = new Package();
            $package->setVariableNumber($order['reference']);
            $package->setCodprice($order['total_paid']);
            if (strpos($order['module'], 'cashondelivery') !== false) {
                $package->setCod(true);
            }
            $package->setIndex($i);
            $packages->getPackages()->add($package);
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
                        'express' => $package->getDelivery() ? $package->getDelivery() : null,
                        'insurance'         => $package->getInsurance(),
                        'insuranceCurrency' => $currencies['EUR'],
                        'sender'         => $user_address,
                        'reciever'       => array(
                            'name'           => $orders[$package->getIndex()]['deliveryName'],
                            'company'        => $orders[$package->getIndex()]['company'],
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
                
                if (strpos($orders[$package->getIndex()]['alias'], 'Parcelshop') !== false) {
                    $name = \explode(' ', $orders[$package->getIndex()]['alias']);
                    $packageData['package']['parcelShopRecieverName'] = $orders[$package->getIndex()]['deliveryName'];
                    $packageData['package']['reciever']['name'] = $name[1];
                }
                else{
                    $packageData['package']['countOfPackages'] = $package->getparts();
                }

                if ($package->getCod()) {
                    $packageData['package']['cashOnDeliveryPrice']    = $package->getCodprice();
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
            
            try {
                $response = $api->createPackages($packages);
            } catch (\Exception $e) {
                $this->addFlash('error', $e->getMessage());
                return $this->redirect( $this->getAdminLink('export_to_neoship_result', array()) );
            }
            
            foreach ( $response as $value ) {
                $res = json_decode( $value['responseContent'], true );
                $status = null;
				if ( 201 === $value['responseCode'] ) {
                    if ($status == null) {
                        $status = $this->getState('Exported to neoship');
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
    private function getOrdersByIds($orderIDs = array()) {
        $em = $this->getDoctrine()->getManager();

        foreach ($orderIDs as &$id) {
            $id = intval($id);
        }

        $query = '
            SELECT o.id_order, o.reference, o.total_paid, o.date_add, o.module, o.id_address_delivery, CONCAT(a.firstname, " ", a.lastname) AS deliveryName,
                    a.address1, a.alias, a.city, a.postcode, c.iso_code, a.phone_mobile, a.phone, cm.email, a.company, crc.iso_code as currency
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
}