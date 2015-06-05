<?php

class AdminOrdersController extends AdminOrdersControllerCore {

    public function __construct() {
        parent::__construct();

        $this->bulk_actions['uploadOrderToNeoship'] = array('text' => $this->l('Export Order to Neoship'), 'icon' => 'icon-upload');
    }

    public function processBulkUploadOrderToNeoship()
    {
        if (Tools::getValue('orderBox') !== false) {
            $addOrders = '';
            foreach (Tools::getValue('orderBox') as $orderID) {
                $addOrders .= '&orders[]=' . $orderID;
            }
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminNeoship') . $addOrders);
        } else {
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminOrders'));
        }
    }
}
