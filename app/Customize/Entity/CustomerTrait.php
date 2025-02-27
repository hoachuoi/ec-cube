<?php

namespace Customize\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Annotation\EntityExtension;

/**
 * Trait CustomerTrait
 * @EntityExtension("Eccube\Entity\Customer")
 */
trait CustomerTrait
{
    /**
     * @var string|null
     * @ORM\Column(name="account_type", type="string", length=50, nullable=true)
     */
    private $account_type;

     /**
     * @var string|null
     * @ORM\Column(name="avatar_filename", type="string", length=50, nullable=true)
     */
    private $avatar_filename;

    public function __construct()
    {
        $this->account_type = 'personal';
    }

    public function getAccountType(): ?string
    {
        return $this->account_type;
    }

    public function setAccountType(?string $accountType): self
    {
        $this->account_type = $accountType;
        return $this;
    }
    public function getAvatarFilename(): ?string
    {
        return $this->avatar_filename;
    }
    public function setAvatarFilename(?string $avatarFilename): self
    {
        $this->avatar_filename = $avatarFilename;
        return $this;
    }
}
