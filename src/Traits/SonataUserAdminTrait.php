<?php

namespace Kikwik\UserBundle\Traits;

trait SonataUserAdminTrait
{
    private function changePassword($object)
    {
        $plainPassword = $object->getPlainPassword();
        if($plainPassword)
        {
            $container = $this->getConfigurationPool()->getContainer();
            $encoder = $container->get('security.password_encoder');
            $encoded = $encoder->encodePassword($object, $plainPassword);

            $object->setPassword($encoded);
        }
    }

    public function prePersist($object)
    {
        $this->changePassword($object);
    }


    public function preUpdate($object)
    {
        $this->changePassword($object);
    }

    protected function getRoleHierarchy(): array
    {
        $container = $this->getConfigurationPool()->getContainer();
        $roles = $container->getParameter('security.role_hierarchy.roles');
        return $roles;
    }
}