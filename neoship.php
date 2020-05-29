<?php

//session_start();

if (!defined('_PS_VERSION_')) {
    exit;
}

class neoship extends Module {

    public function __construct() {
        $this->name      = 'neoship';
        $this->bootstrap = true;
        $this->tab       = 'others';
        $this->version   = '1.1'; //updated for prestashop version 1.7
        $this->author    = 'Neoship s.r.o.';

        parent::__construct();

        $this->displayName = $this->l('Neoship Order Ship');
    }

    public function install()
    {
        // Prepare tab
        $tab = new Tab();
        $tab->active = 1;
        $tab->class_name = 'AdminNeoship';
        $tab->name = array();
        foreach (Language::getLanguages(true) as $lang)
            $tab->name[$lang['id_lang']] = 'Neoship';
        $tab->id_parent = -1;
        $tab->module = $this->name;

        if (!$tab->add() ||
            !parent::install() )
            return false;

        return true;
    }

    public function uninstall()
    {
        $id_tab = (int)Tab::getIdFromClassName('AdminNeoship');

        if ($id_tab)
        {
            $tab = new Tab($id_tab);
            $tab->delete();
        }

        if (!parent::uninstall())
            return false;
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
                    array(
                        'type'     => 'text',
                        'label'    => $this->l('Client Username'),
                        'name'     => 'CLIENT_USERNAME',
                        'required' => true,
                        'desc'     => $this->l('Fill in the Client Username that you received from your API provider.'),
                    ),
                    array(
                        'type'     => 'text',
                        'label'    => $this->l('API URL'),
                        'name'     => 'API_URL',
                        'readonly' => true,
                        'desc'     => $this->l('Copy this URL a send to your API provider.'),
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
