<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once dirname(__FILE__) . '/src/Neoshipapi.php';

class neoship extends Module {

    const PREFIX = 'neoship_';

    protected $_carriers = array(
        'SPS Parcelshop' => 'sps_parcelshop',
    );

    public function __construct() {
        $this->name      = 'neoship';
        $this->bootstrap = true;
        $this->tab       = 'others';
        $this->version   = '2.0'; //updated for prestashop version 1.7
        $this->author    = 'Neoship s.r.o.';

        parent::__construct();

        $this->displayName = $this->l('Neoship Order Ship');

        if (!Configuration::get('CLIENT_ID') || !Configuration::get('CLIENT_SECRET')) {
            $this->warning = $this->l('Login credentials not set!');
        }

        $this->ps_versions_compliancy = [
            'min' => '1.7.3.0',
            'max' => _PS_VERSION_,
        ];
    }

    protected function createCarriers()
    {
        foreach ($this->_carriers as $key => $value) {
            $tmp_carrier = Configuration::get(self::PREFIX . $value);

            if ($tmp_carrier) {
                $tmp_carrier = new Carrier($tmp_carrier);
                if($tmp_carrier->deleted){
                    $tmp_carrier = null;
                }
            }

            if(!$tmp_carrier)
            {
                //Create new carrier
                $carrier = new Carrier();
                $carrier->name = $key;
                $carrier->active = TRUE;
                $carrier->deleted = 0;
                $carrier->shipping_handling = FALSE;
                $carrier->range_behavior = 0;
                $carrier->delay[Configuration::get('PS_LANG_DEFAULT')] = $key;
                $carrier->shipping_external = TRUE;
                $carrier->is_module = TRUE;
                $carrier->external_module_name = $this->name;
                $carrier->need_range = TRUE;
        
                if ($carrier->add()) {
                    $groups = Group::getGroups(true);
                    foreach ($groups as $group) {
                        Db::getInstance()->insert('carrier_group', array(
                            'id_carrier' => (int) $carrier->id,
                            'id_group' => (int) $group['id_group']
                        ));
                    }
        
                    $rangePrice = new RangePrice();
                    $rangePrice->id_carrier = $carrier->id;
                    $rangePrice->delimiter1 = '0';
                    $rangePrice->delimiter2 = '1000000';
                    $rangePrice->add();
        
                    $rangeWeight = new RangeWeight();
                    $rangeWeight->id_carrier = $carrier->id;
                    $rangeWeight->delimiter1 = '0';
                    $rangeWeight->delimiter2 = '1000000';
                    $rangeWeight->add();
        
                    $zones = Zone::getZones(true);

                    foreach ($zones as $z) {
                        Db::getInstance()->insert('carrier_zone',
                            array('id_carrier' => (int) $carrier->id, 'id_zone' => (int) $z['id_zone']));
                        Db::getInstance()->insert('delivery',
                            array('id_carrier' => $carrier->id, 'id_range_price' => (int) $rangePrice->id, 'id_range_weight' => NULL, 'id_zone' => (int) $z['id_zone'], 'price' => '0'));
                        Db::getInstance()->insert('delivery',
                            array('id_carrier' => $carrier->id, 'id_range_price' => NULL, 'id_range_weight' => (int) $rangeWeight->id, 'id_zone' => (int) $z['id_zone'], 'price' => '0'));
                    }
        
                    Configuration::updateValue(self::PREFIX . $value, $carrier->id);
                    Configuration::updateValue(self::PREFIX . $value . '_reference', $carrier->id);
                }
            }
        }
    
        return TRUE;
    }

    public function getOrderShippingCost($params, $shipping_cost)
    {
        return $shipping_cost;
    }
    
    public function getOrderShippingCostExternal($params)
    {
        return $this->getOrderShippingCost($params, 10);
    }

    protected function deleteCarriers()
    {
        foreach ($this->_carriers as $value) {
            $tmp_carrier_id = Configuration::get(self::PREFIX . $value);
            $carrier = new Carrier($tmp_carrier_id);
            $carrier->delete();
        }

        return TRUE;
    }

    public function isUsingNewTranslationSystem()
    {
        return true;
    }

    public function install()
    {

		if (!$this->createCarriers()) { //function for creating new currier
			return false;
		}

        if (!parent::install() ||
            !$this->registerHook('adminAdminOrdersControllerCore') ||
            !$this->registerHook('displayCarrierExtraContent') ||
            !$this->registerHook('actionValidateOrder') ||
			!$this->registerHook('actionValidateStepComplete')
        ) {
            return false;
        }

        return true;
    }

    public function hookDisplayCarrierExtraContent($param) {
        $this->context->controller->addJs(($this->_path).'script.js', 'all'); 
        if ( $param['carrier']['id'] == Configuration::get(self::PREFIX . 'sps_parcelshop') ) {
            $this->smarty->assign('parcelshops', \Neoship\Neoshipapi::getParcelShops() );
			return $this->fetch('module:neoship/views/hook/parcelshop.tpl');
        }

    }

    public function hookActionValidateOrder($params)
    {
        if ($params['cart']->id_carrier != Configuration::get(self::PREFIX . 'sps_parcelshop')) {
			return;
        }
        $parcelId = Context::getContext()->cookie->neoship_parcelshop_id;
        $parcelshops = \Neoship\Neoshipapi::getParcelShops(true);
        $addressInvoice = new Address(intval($params['cart']->id_address_invoice));
        $address = new Address();
        $address->alias = 'Parcelshop ' . $parcelshops[$parcelId] ['address']['name'];
        $address->firstname = $addressInvoice->firstname;
        $address->lastname = $addressInvoice->lastname;
        $address->phone = $addressInvoice->phone;
        $address->company = $parcelshops[$parcelId] ['address']['company'];
        $address->city = $parcelshops[$parcelId] ['address']['city'];
        $address->postcode = $parcelshops[$parcelId] ['address']['zip'];
        $address->address1 = $parcelshops[$parcelId] ['address']['street'];
        $address->id_country = Country::getByIso( $parcelshops[$parcelId] ['address']['state']['code'] );
        $address->save();
        $params['order']->id_address_delivery = $address->id;
        $params['order']->save();
    }

    public function hookActionValidateStepComplete($params)
	{
		if ($params['step_name'] != 'delivery') {
			return;
		}

        if ($params['cart']->id_carrier != Configuration::get(self::PREFIX . 'sps_parcelshop')) {
			return;
        }
        
		if (!isset($params['request_params']['neoship_parcelshop_id']) || !$params['request_params']['neoship_parcelshop_id']) {
            $controller           = $this->context->controller;
			$controller->errors[] = $this->l('Please select a parcelshop!');
			$params['completed']  = false;
		} else {
            $parcelshops = \Neoship\Neoshipapi::getParcelShops();
            if(!array_key_exists($params['request_params']['neoship_parcelshop_id'], $parcelshops)) {
                $controller           = $this->context->controller;
                $controller->errors[] = $this->l('Please select a parcelshop!');
                $params['completed']  = false;
                return;
            }
            Context::getContext()->cookie->neoship_parcelshop_id = $params['request_params']['neoship_parcelshop_id'];
            
			/* Db::getInstance()->insert('lpexpress_terminal_for_cart', array(
				'id_cart'       => $params['cart']->id,
				'id_terminal'   => (int)$params['request_params']['lpexpress_terminal_id'],
			)); */
		}
	}

    public function uninstall()
    {
        /* if (!$this->deleteCarriers()) {
			return false;
		} */

        if (!parent::uninstall() ||
            !Configuration::deleteByName('CLIENT_ID') ||
            !Configuration::deleteByName('CLIENT_SECRET')
        ){
            return false;
        }

        return true;
    }

    public function getContent()
    {
        $echo = '';

        if (Tools::isSubmit('submitSetting'))
        {
            if (Configuration::get('CLIENT_ID') != Tools::getValue('CLIENT_ID') && Configuration::updateValue('CLIENT_ID', Tools::getValue('CLIENT_ID')))
                $echo .= $this->displayConfirmation($this->l('Your Client ID has been updated.'));

            if (Configuration::get('CLIENT_SECRET') != Tools::getValue('CLIENT_SECRET') && Configuration::updateValue('CLIENT_SECRET', Tools::getValue('CLIENT_SECRET')))
                $echo .= $this->displayConfirmation($this->l('Your Client Secret has been updated.'));

            if (Configuration::get('CLIENT_USERNAME') != Tools::getValue('CLIENT_USERNAME') && Configuration::updateValue('CLIENT_USERNAME', Tools::getValue('CLIENT_USERNAME')))
                $echo .= $this->displayConfirmation($this->l('Your Client Username has been updated.'));
        }
        $echo .= $this->renderForm();

        return $echo;
    }
    
    public function renderForm()
    {
        $fields_form = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Settings'),
                    'icon'  => 'icon-cogs',
                ),
                'input'  => array(
                    array(
                        'type'     => 'text',
                        'label'    => $this->l('Client ID'),
                        'name'     => 'CLIENT_ID',
                        'required' => true,
                        'desc'     => $this->l('Fill in the Client ID that you received from your API provider.'),
                    ),
                    array(
                        'type'     => 'text',
                        'label'    => $this->l('Client Secret'),
                        'name'     => 'CLIENT_SECRET',
                        'required' => true,
                        'desc'     => $this->l('Fill in the Client Secret that you received from your API provider.'),
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                )
            ),
        );

        $helper                           = new HelperForm();
        $helper->module                   = $this;
        $helper->show_toolbar             = false;
        $helper->table                    = $this->table;
        $lang                             = new Language((int) Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language    = $lang->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
        $this->fields_form                = array();

        $helper->identifier    = $this->identifier;
        $helper->submit_action = 'submitSetting';
        $helper->currentIndex  = $this->context->link->getAdminLink('AdminModules', false) . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token         = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars      = array(
            'fields_value' => $this->getConfigFieldsValues(),
            'languages'    => $this->context->controller->getLanguages(),
            'id_language'  => $this->context->language->id
        );

        return $helper->generateForm(array($fields_form));
    }

    public function getConfigFieldsValues()
    {
        return array(
            'CLIENT_ID'       => Tools::getValue('CLIENT_ID', Configuration::get('CLIENT_ID')),
            'CLIENT_SECRET'   => Tools::getValue('CLIENT_SECRET', Configuration::get('CLIENT_SECRET')),
            'CLIENT_USERNAME' => Tools::getValue('CLIENT_USERNAME', Configuration::get('CLIENT_USERNAME')),
            'API_URL'         => $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['SCRIPT_NAME'],
        );
    }
}
