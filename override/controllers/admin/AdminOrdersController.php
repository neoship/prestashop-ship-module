<?php

class AdminOrdersController extends AdminOrdersControllerCore {

    public function __construct() {
        parent::__construct();
        $this->addRowAction('NeoshipTrack');

        global $kernel;
        $api = $kernel->getContainer()->get('neoship.neoshipapi');

        $credit = 0;
        
        try {
            $api->login();
            $credit = $api->getUserCredit();
        } catch (Exception $e) {
            unset($e);
        }

        $this->bulk_actions['uploadOrderToNeoship'] = array('text' => 'Exportovať do Neoshipu ('.$credit.'€)', 'icon' => 'icon-upload');
        $this->bulk_actions['printStickers'] = array('text' => 'Tlač štítkov (PDF)', 'icon' => 'icon-print');
        $this->bulk_actions['stickersZebra102x152'] = array('text' => 'Tlač štítkov (PDF) 102x152', 'icon' => 'icon-print');
        $this->bulk_actions['stickersZebra80x214'] = array('text' => 'Tlač štítkov (PDF) 80x214', 'icon' => 'icon-print');
        $this->bulk_actions['acceptanceProtocol'] = array('text' => 'Tlač preberacieho protokolu (PDF)', 'icon' => 'icon-print');
    }

    public function displayNeoshipTrackLink($token = null, $id, $name = null)
    {
        $href = self::$currentIndex.'&'.$this->identifier.'='.$id.'&NeoshipTrack'.$this->table.'&token='.($token != null ? $token : $this->token);
        
        return '
            <a class="btn btn-default _blank" href="'.$href.'" target="_blank">
                Sledovanie <i class="icon-truck"></i>
            </a>
        ';
    }

    public function initProcess()
    {
        if (Tools::getIsset('NeoshipTrack'.$this->table))
        {
            $this->action = 'NeoshipTrack';
        }
        parent::initProcess();
    }

    public function processNeoshipTrack()
    {
        $order = new Order(Tools::getValue('id_order'));
        $api = new \Neoship\Neoshipapi();

        try {
            $api->login();
            $userID = $api->getUserId();
            $url = NEOSHIP_TRACKING_URL . '/tracking/packageReference/' . $userID . '/' . $order->reference;
            Tools::redirect($url);
        } catch (Exception $e) {
            unset($e);
        }
    }

    public function processBulkAcceptanceProtocol()
    {
        $str = '';
        $last = '';
        parse_str($_SERVER['QUERY_STRING'], $str);
        foreach ($str as $key => $value) {
            if(strpos($key, 'submit') !== false) {
                $last = $key;
            }
        }
        if(strpos($last, 'printStickers') !== false) {
            $this->processBulkPrintStickers(0);
            return;
        }
        else if(strpos($last, 'stickersZebra102x152') !== false) {
            $this->processBulkPrintStickers(1);
            return;
        }
        else if(strpos($last, 'stickersZebra80x214') !== false) {
            $this->processBulkPrintStickers(2);
            return;
        }

        if (Tools::getValue('orderBox') !== false) {
            $addOrders = '';
            foreach (Tools::getValue('orderBox') as $orderID) {
                $addOrders .= '&orders[]=' . $orderID;
            }
            Tools::redirectAdmin($this->context->link->getAdminLink('print_acceptance_neoship') . $addOrders);
        } else {
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminOrders'));
        }
    }

    public function processBulkUploadOrderToNeoship()
    {

        if (Tools::getValue('orderBox') !== false) {
            $addOrders = '';
            foreach (Tools::getValue('orderBox') as $orderID) {
                $addOrders .= '&orders[]=' . $orderID;
            }
            Tools::redirectAdmin($this->context->link->getAdminLink('export_to_neoship') . $addOrders);
        } else {
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminOrders'));
        }
    }

    public function processBulkPrintStickers($printtype = 0)
    {
        $str = '';
        $last = '';
        parse_str($_SERVER['QUERY_STRING'], $str);
        foreach ($str as $key => $value) {
            if(strpos($key, 'submit') !== false) {
                $last = $key;
            }
        }
        if(strpos($last, 'printStickers') !== false) {
            $printtype = 0;
        }
        else if(strpos($last, 'stickersZebra102x152') !== false) {
            $printtype = 1;
        }
        else if(strpos($last, 'stickersZebra80x214') !== false) {
            $printtype = 2;
        }
        else if(strpos($last, 'acceptanceProtocol') !== false) {
            $this->processBulkAcceptanceProtocol();
            return;
        }

        if (Tools::getValue('orderBox') !== false) {
            $addOrders = '';
            foreach (Tools::getValue('orderBox') as $orderID) {
                $addOrders .= '&orders[]=' . $orderID;
            }
            Tools::redirectAdmin($this->context->link->getAdminLink('print_sticker_neoship') . $addOrders . '&printtype=' . $printtype );
        } else {
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminOrders'));
        }
    }

    public function processBulkStickersZebra102x152()
    {
        $this->processBulkPrintStickers(1);
    }

    public function processBulkStickersZebra80x214()
    {
        $this->processBulkPrintStickers(2);
    }
}
