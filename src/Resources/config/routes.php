<?php

use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $routes): void {
    $routes
        ->add('kikwik_user_password_change', '/change')
        ->controller('kikwik_user.controller.password_controller::changePassword')
        ->methods(['GET', 'POST']);

    $routes
        ->add('kikwik_user_password_request', '/request')
        ->controller('kikwik_user.controller.password_controller::requestPassword')
        ->methods(['GET', 'POST']);

    $routes
        ->add('kikwik_user_password_reset', '/reset/{userIdentifier}/{secretCode}')
        ->controller('kikwik_user.controller.password_controller::resetPassword')
        ->methods(['GET', 'POST']);
};