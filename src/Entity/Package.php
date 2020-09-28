<?php
namespace Neoship\Entity;

class Package
{
    private $variableNumber;

    private $index;

    private $sms = true;

    private $phone = false;
    
    private $email = true;
    
    private $cod;

    private $codprice;

    private $delivery;

    private $saturday;

    private $parts = 1;

    private $attachment;

    private $holddelivery;

    private $insurance = 0;

    private $isGls = false;

    /**
     * Get the value of variableNumber
     */ 
    public function getVariableNumber()
    {
        return $this->variableNumber;
    }

    /**
     * Set the value of variableNumber
     *
     * @return  self
     */ 
    public function setVariableNumber($variableNumber)
    {
        $this->variableNumber = $variableNumber;

        return $this;
    }

    /**
     * Get the value of index
     */ 
    public function getIndex()
    {
        return $this->index;
    }

    /**
     * Set the value of index
     *
     * @return  self
     */ 
    public function setIndex($index)
    {
        $this->index = $index;

        return $this;
    }

    /**
     * Get the value of sms
     */ 
    public function getSms()
    {
        return $this->sms;
    }

    /**
     * Set the value of sms
     *
     * @return  self
     */ 
    public function setSms($sms)
    {
        $this->sms = $sms;

        return $this;
    }

    /**
     * Get the value of phone
     */ 
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * Set the value of phone
     *
     * @return  self
     */ 
    public function setPhone($phone)
    {
        $this->phone = $phone;

        return $this;
    }

    /**
     * Get the value of email
     */ 
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set the value of email
     *
     * @return  self
     */ 
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get the value of cod
     */ 
    public function getCod()
    {
        return $this->cod;
    }

    /**
     * Set the value of cod
     *
     * @return  self
     */ 
    public function setCod($cod)
    {
        $this->cod = $cod;

        return $this;
    }

    /**
     * Get the value of codprice
     */ 
    public function getCodprice()
    {
        return $this->codprice;
    }

    /**
     * Set the value of codprice
     *
     * @return  self
     */ 
    public function setCodprice($codprice)
    {
        $this->codprice = $codprice;

        return $this;
    }

    /**
     * Get the value of delivery
     */ 
    public function getDelivery()
    {
        return $this->delivery;
    }

    /**
     * Set the value of delivery
     *
     * @return  self
     */ 
    public function setDelivery($delivery)
    {
        $this->delivery = $delivery;

        return $this;
    }

    /**
     * Get the value of saturday
     */ 
    public function getSaturday()
    {
        return $this->saturday;
    }

    /**
     * Set the value of saturday
     *
     * @return  self
     */ 
    public function setSaturday($saturday)
    {
        $this->saturday = $saturday;

        return $this;
    }

    /**
     * Get the value of parts
     */ 
    public function getParts()
    {
        return $this->parts;
    }

    /**
     * Set the value of parts
     *
     * @return  self
     */ 
    public function setParts($parts)
    {
        $this->parts = $parts;

        return $this;
    }

    /**
     * Get the value of attachment
     */ 
    public function getAttachment()
    {
        return $this->attachment;
    }

    /**
     * Set the value of attachment
     *
     * @return  self
     */ 
    public function setAttachment($attachment)
    {
        $this->attachment = $attachment;

        return $this;
    }

    /**
     * Get the value of holddelivery
     */ 
    public function getHolddelivery()
    {
        return $this->holddelivery;
    }

    /**
     * Set the value of holddelivery
     *
     * @return  self
     */ 
    public function setHolddelivery($holddelivery)
    {
        $this->holddelivery = $holddelivery;

        return $this;
    }

    /**
     * Get the value of insurance
     */ 
    public function getInsurance()
    {
        return $this->insurance;
    }

    /**
     * Set the value of insurance
     *
     * @return  self
     */ 
    public function setInsurance($insurance)
    {
        $this->insurance = $insurance;

        return $this;
    }

    /**
     * Get the value of isGls
     */ 
    public function getIsGls()
    {
        return $this->isGls;
    }

    /**
     * Set the value of isGls
     *
     * @return  self
     */ 
    public function setIsGls($isGls)
    {
        $this->isGls = $isGls;

        return $this;
    }
}