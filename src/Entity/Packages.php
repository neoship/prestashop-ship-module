<?php
namespace Neoship\Entity;

use Doctrine\Common\Collections\ArrayCollection;

class Packages
{
    protected $packages;

    public function __construct()
    {
        $this->packages = new ArrayCollection();
    }

    public function getPackages()
    {
        return $this->packages;
    }
}