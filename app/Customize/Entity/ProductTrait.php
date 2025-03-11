<?php

namespace Customize\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Annotation\EntityExtension;

/**
 * Trait ProductTrait
 * @EntityExtension("Eccube\Entity\Product")
 */

trait ProductTrait
{
    /**
     *  @ORM\Column(type="integer", nullable=true)
     * @var 
     */
    private $id_warehouse;

    public function __construct()
    {
        $this->id_warehouse = null;
    }
    /**
     * @return int|null
     */
    public function getIdWarehouse(): ?int
    {
        return $this->id_warehouse;
    }

    /**
     * @param int|null $idWarehouse
     */
    public function setIdWarehouse(?int $idWarehouse): self
    {
        $this->id_warehouse = $idWarehouse;
        return $this;
    }
}
