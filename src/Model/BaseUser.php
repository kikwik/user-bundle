<?php

namespace Kikwik\UserBundle\Model;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Blameable\Traits\BlameableEntity;
use Gedmo\IpTraceable\Traits\IpTraceableEntity;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Class BaseUser
 * @package Kikwik\UserBundle\Model
 */
abstract class BaseUser implements UserInterface
{
    use TimestampableEntity;

    use BlameableEntity;

    use IpTraceableEntity;

    /**************************************/
    /* Helpers                            */
    /**************************************/

    public function __toString()
    {
        return (string) $this->getUsername();
    }

    public function getRolesAsLabel()
    {
        return $this->buildRoleString('label');
    }

    public function getRolesAsBadges()
    {
        return $this->buildRoleString('badge');
    }

    protected function getRoleClass($role)
    {
        switch($role)
        {
            case 'ROLE_SUPER_ADMIN':
                return 'label-danger badge-danger';
                break;
            case 'ROLE_ADMIN':
                return 'label-warning badge-warning';
                break;
            default:
                return 'label-default badge-dark';
                break;
        }
    }

    private function buildRoleString($class = 'label')
    {
        $roles = array();
        foreach ($this->getRoles() as $role) {
            $tmp = explode('_', $role);
            array_shift($tmp);
            $roles[] = '<span class="'.$class.' '.$this->getRoleClass($role).'">'.ucfirst(strtolower(implode(' ', $tmp))).'</span>';
        }
        return implode(' ', $roles);
    }

    /**************************************/
    /* Password                           */
    /**************************************/

    /**
     * Plain password. Used for model validation. Must not be persisted.
     *
     * @var string
     */
    protected $plainPassword;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @Gedmo\Timestampable(on="change", field={"password"})
     */
    protected $passwordChangedAt;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $changePasswordSecret;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @Gedmo\Timestampable(on="change", field={"changePasswordSecret"})
     */
    protected $changePasswordRequestedAt;

    /**
     * @return string
     */
    public function getPlainPassword(): ?string {
        return $this->plainPassword;
    }

    /**
     * @param string $plainPassword
     * @return BaseUser
     */
    public function setPlainPassword(string $plainPassword): BaseUser {
        $this->plainPassword = $plainPassword;
        return $this;
    }

    /**
     * @return \DateTime|null
     */
    public function getPasswordChangedAt(): ?\DateTime {
        return $this->passwordChangedAt;
    }

    /**
     * @return string|null
     */
    public function getChangePasswordSecret(): ?string {
        return $this->changePasswordSecret;
    }

    /**
     * @return string
     */
    public function generateChangePasswordSecret(): string {
        if(is_null($this->changePasswordRequestedAt) || $this->changePasswordRequestedAt < new \DateTime('-1 day'))
        {
            $this->changePasswordSecret = md5(uniqid(rand(), true));
        }
        return $this->changePasswordSecret;
    }

    public function removeChangePasswordSecret()
    {
        $this->changePasswordSecret = null;
        $this->changePasswordRequestedAt = null;
    }


    /**************************************/
    /* login                              */
    /**************************************/

    /**
     * @var \DateTime|null
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $lastLogin;

    /**
     * @var \DateTime|null
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $previousLogin;

    /**
     * @var integer
     * @ORM\Column(type="integer", options={"default" : 0})
     */
    protected $loginCount = 0;

    /**
     * @return \DateTime|null
     */
    public function getLastLogin(): ?\DateTime {
        return $this->lastLogin;
    }

    /**
     * @return \DateTime|null
     */
    public function getPreviousLogin(): ?\DateTime {
        return $this->previousLogin;
    }

    /**
     * @return int
     */
    public function getLoginCount(): int {
        return $this->loginCount;
    }

    public function newLogin()
    {
        $this->previousLogin = $this->lastLogin;
        $this->lastLogin = new \DateTime();
        $this->loginCount++;
    }

    /**************************************/
    /* isEnabled                          */
    /**************************************/

    /**
     * @return bool
     * @ORM\Column(type="boolean", options={"default":"1"})
     */
    protected $isEnabled = true;

    /**
     * @return bool
     */
    public function isEnabled(): bool {
        return $this->isEnabled;
    }

    /**
     * @param bool $isEnabled
     * @return BaseUser
     */
    public function setIsEnabled(bool $isEnabled): BaseUser {
        $this->isEnabled = $isEnabled;
        return $this;
    }
}