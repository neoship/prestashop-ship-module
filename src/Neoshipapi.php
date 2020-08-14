<?php

namespace Neoship;

use PrestaShop\PrestaShop\Adapter\Configuration;
use Symfony\Component\Translation\TranslatorInterface;
		
define( 'NEOSHIP_API_URL', 'https://api.neoship.sk' );
define( 'NEOSHIP_TRACKING_URL', 'https://neoship.sk' );

class Neoshipapi
{
	private $accessData = false;
    private $curl;
    private $loginData;
    private $translator;

    function __construct(TranslatorInterface $translator = null) {

        $this->translator = $translator;
		
		$this->curl = curl_init();

	}
	
    public function login(){
        
        $url = NEOSHIP_API_URL . '/oauth/v2/token?client_id='.urlencode(Configuration::get('CLIENT_ID')).'&client_secret='.urlencode(Configuration::get('CLIENT_SECRET')).'&grant_type=client_credentials';
        curl_setopt($this->curl, CURLOPT_URL, $url);
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, 1); 
        $response = curl_exec($this->curl);

        if (curl_getinfo($this->curl, CURLINFO_HTTP_CODE) != 200){
            throw new \Exception( $this->translator->trans('Bad login credentials', [], 'Modules.Neoship.Api') );
        }
        
        $this->accessData = json_decode($response);
	}
	
    public function getUserAddress(){
        $url = NEOSHIP_API_URL . '/user/?' . http_build_query($this->accessData);
        curl_setopt($this->curl, CURLOPT_URL, $url);
        $response = curl_exec($this->curl);
        if (curl_getinfo($this->curl, CURLINFO_HTTP_CODE) != 200){
            throw new \Exception( $this->translator->trans('Something is wrong. Please refresh the page and try again', [], 'Modules.Neoship.Api') );
        }
        
        $user = json_decode($response, true);
        $this->loginData['userid'] = $user['id'];
        $user['address']['state'] = $user['address']['state']['id'];
        unset($user['address']['id']);
        $user['address']['zIP'] = $user['address']['zip'];
        unset($user['address']['zip']);
        return $user['address'];
	}
	
    public function getUserId(){
        $url = NEOSHIP_API_URL . '/user/?' . http_build_query($this->accessData);
        curl_setopt($this->curl, CURLOPT_URL, $url);
        $response = curl_exec($this->curl);      
        if (curl_getinfo($this->curl, CURLINFO_HTTP_CODE) != 200){
            throw new \Exception( $this->translator->trans('Something is wrong. Please refresh the page and try again', [], 'Modules.Neoship.Api') );
        }
        
        $user = json_decode($response, true);
        return $user['id'];
	}
	
    public function getUserCredit(){
        $url = NEOSHIP_API_URL . '/user/?' . http_build_query($this->accessData);
        curl_setopt($this->curl, CURLOPT_URL, $url);
        $response = curl_exec($this->curl);      
        if (curl_getinfo($this->curl, CURLINFO_HTTP_CODE) != 200){
            return 0;
        }
        
        $user = json_decode($response, true);
        return round($user['kredit'], 2);
	}
	
    public function getStatesIds(){
        $url = NEOSHIP_API_URL . '/state/?' . http_build_query($this->accessData);
        curl_setopt($this->curl, CURLOPT_URL, $url);
        $response = curl_exec($this->curl);      
        if (curl_getinfo($this->curl, CURLINFO_HTTP_CODE) != 200){
            throw new \Exception( $this->translator->trans('Something is wrong. Please refresh the page and try again', [], 'Modules.Neoship.Api') );
        }
        
        $states = json_decode($response, true);
        $stateIdsByCode = [];
        foreach ($states as $state) {
            $stateIdsByCode[$state['code']] = $state['id'];
        }
        return $stateIdsByCode;
	}
	
    public function createPackages($packages, $gls = false){
        $url = NEOSHIP_API_URL . '/package/bulk?' . ($gls ? 'gls=1&' : '') . http_build_query($this->accessData);
        curl_setopt($this->curl, CURLOPT_URL, $url);   
        curl_setopt($this->curl, CURLOPT_POST, 1);                                                              
        curl_setopt($this->curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, json_encode($packages));
        $response = curl_exec($this->curl);
       
        if (curl_getinfo($this->curl, CURLINFO_HTTP_CODE) != 200){
            throw new \Exception( $this->translator->trans('Something is wrong. Please refresh the page and try again', [], 'Modules.Neoship.Api') );
        }
        
        return json_decode($response, true);
	}
	
    public function getCurrenciesIds(){
        $url = NEOSHIP_API_URL . '/currency/?' . http_build_query($this->accessData);
        curl_setopt($this->curl, CURLOPT_URL, $url);
        $response = curl_exec($this->curl);      
        if (curl_getinfo($this->curl, CURLINFO_HTTP_CODE) != 200){
            throw new \Exception( $this->translator->trans('Something is wrong. Please refresh the page and try again', [], 'Modules.Neoship.Api') );
        }
        
        $currencies = json_decode($response, true);
        $currencyIdsByCode = [];
        foreach ($currencies as $currency) {
            $currencyIdsByCode[$currency['code']] = $currency['id'];
        }
        return $currencyIdsByCode;
	}

    public function printSticker($template,$referenceNumber){
        $data['ref'] = $referenceNumber;
        $data['template'] = $template;
        $data = (object) array_merge((array) $data, (array) $this->accessData);
        $url = NEOSHIP_API_URL . '/package/sticker?' . http_build_query($data);
        curl_setopt($this->curl, CURLOPT_URL, $url);
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, 0); 
        $this->handlePdf('sticker'.$template);
	}

    public function printGlsSticker($referenceNumber){
        $data['ref'] = $referenceNumber;
        $data = (object) array_merge((array) $data, (array) $this->accessData);
        $url = NEOSHIP_API_URL . '/package/stickerwitherrors?' . http_build_query($data);
        curl_setopt($this->curl, CURLOPT_URL, $url);
        $response = curl_exec($this->curl);    
        
        if (curl_getinfo($this->curl, CURLINFO_HTTP_CODE) != 200){
            throw new \Exception( $this->translator->trans('Something is wrong. Please refresh the page and try again', [], 'Modules.Neoship.Api') );
        }
        
        return json_decode($response, true);
	}
	
    public function printAcceptanceProtocol($referenceNumber){
        $data['ref'] = $referenceNumber;
        $data = (object) array_merge((array) $data, (array) $this->accessData);
        $url = NEOSHIP_API_URL . '/package/acceptance?' . http_build_query($data);
        curl_setopt($this->curl, CURLOPT_URL, $url);
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, 0); 
        $this->handlePdf();
	}
	
    private function handlePdf($filename = 'acceptance') {
        header('Cache-Control: public'); 
        header('Content-type: application/pdf');
        $response = curl_exec($this->curl);  
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, 1);   
        /* if (curl_getinfo($this->curl, CURLINFO_HTTP_CODE) != 200){
            throw new \Exception( $this->translator->trans('You are trying Neoship action on orders which are not imported to neoship', [], 'Modules.Neoship.Api') );
        }
        header('Cache-Control: public'); 
        header('Content-type: application/pdf');
        header('Content-Disposition: attachment; filename="'.$filename.'.pdf"');
        header('Content-Length: '.strlen($response));
        echo $response;
        curl_close($response); */
        exit();
	}
	
    static public function getParcelShops($all = false){
        $url = NEOSHIP_API_URL . '/public/parcelshop/';

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($curl);
        
        if (curl_getinfo($curl, CURLINFO_HTTP_CODE) != 200){
            throw new \Exception( 'Parcelshop load problem' );
        }
        
        $parcelshops = json_decode($response, true);

        $parcelShops = [];
        foreach ($parcelshops as $parcelshop) {
            if($all){
                $parcelShops[$parcelshop['id']] = $parcelshop;
            }
            else{
                $parcelShops[$parcelshop['id']] = $parcelshop['address']['city'].', '.$parcelshop['address']['company'];
            }
        }
        return $parcelShops;
    }
	
    static public function getGlsParcelShops($all = false){
        $url = NEOSHIP_API_URL . '/public/glsparcelshop/';

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($curl);
        
        if (curl_getinfo($curl, CURLINFO_HTTP_CODE) != 200){
            throw new \Exception( 'Parcelshop load problem' );
        }
        
        $parcelshops = json_decode($response, true);

        $parcelShops = [];
        foreach ($parcelshops as $parcelshop) {
            if($all){
                $parcelShops[$parcelshop['parcelShopId']] = $parcelshop;
            }
            else{
                $parcelShops[$parcelshop['parcelShopId']] = $parcelshop['cityName'] . ', ' . $parcelshop['name'];
            }
        }

        return $parcelShops;
    }
}