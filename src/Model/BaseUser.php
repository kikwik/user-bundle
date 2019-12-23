<?php

namespace Kikwik\UserBundle\Model;

use Gedmo\Blameable\Traits\BlameableEntity;
use Gedmo\IpTraceable\Traits\IpTraceableEntity;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Security\Core\User\UserInterface;

abstract class BaseUser implements UserInterface
{
    use TimestampableEntity;

    use BlameableEntity;

    use IpTraceableEntity;

    /**
     * Plain password. Used for model validation. Must not be persisted.
     *
     * @var string
     */
    protected $plainPassword;

    /**
     * @var \DateTime|null
     */
    protected $lastLogin;

    /**
     * @var \DateTime|null
     */
    protected $previousLogin;

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
    public function getLastLogin(): ?\DateTime {
        return $this->lastLogin;
    }

    /**
     * @param \DateTime|null $lastLogin
     * @return BaseUser
     */
    public function setLastLogin(?\DateTime $lastLogin): BaseUser {
        $this->lastLogin = $lastLogin;
        return $this;
    }

    /**
     * @return \DateTime|null
     */
    public function getPreviousLogin(): ?\DateTime {
        return $this->previousLogin;
    }

    /**
     * @param \DateTime|null $previousLogin
     * @return BaseUser
     */
    public function setPreviousLogin(?\DateTime $previousLogin): BaseUser {
        $this->previousLogin = $previousLogin;
        return $this;
    }


}