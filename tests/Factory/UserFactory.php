<?php

namespace Kikwik\UserBundle\Tests\Factory;

use Kikwik\UserBundle\Tests\Fixture\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<User>
 */
final class UserFactory extends PersistentProxyObjectFactory
{
    const PASSWORD = 'Pa$$';

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#factories-as-services
     *
     * @todo inject services if required
     */
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher
    )
    {
        parent::__construct();
    }

    public static function class(): string
    {
        return User::class;
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     * @todo add your default values here
     */
    protected function defaults(): array|callable
    {
        return [
            'username' => self::faker()->userName(),
            'email' => self::faker()->safeEmail(),
            'isEnabled' => true,
            'password' => self::PASSWORD,
            'roles' => [],
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    protected function initialize(): static
    {
        return $this
             ->afterInstantiate(function(User $user): void {
                 if($user->getPassword()) {
                     $user->setPassword($this->passwordHasher->hashPassword($user, $user->getPassword()));
                 }
             })
        ;
    }
}
