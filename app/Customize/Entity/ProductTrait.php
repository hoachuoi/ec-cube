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
     * @ORM\Column(type="string", nullable=true)
     */
    // thêm một cột mới vào bảng product trong database có thể có giá trị null
   public $maker_name;
}