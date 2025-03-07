<?php

namespace Customize\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Annotation\EntityExtension;

/**
 * Trait MemberTrait
 * @EntityExtension("Eccube\Entity\Member")
 */

trait MemberTrait
{
    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    public $is_warehouse;

    public function __construct()
    {
        $this->is_warehouse = false;
    }
    /**
     * @return bool|null
     */
    public function getIsWarehouse(): ?bool
    {
        return $this->is_warehouse;
    }

    /**
     * @param bool|null $isWarehouse
     * @return $this
     */
    public function setIsWarehouse(?bool $isWarehouse): self
    {
        $this->is_warehouse = $isWarehouse;
        return $this;
    }
    public function getRoles(): array
    {
        if ($this->is_warehouse) {
            return ['ROLE_WAREHOUSE'];
        } else{
            return ['ROLE_ADMIN'];
        }
    }
}