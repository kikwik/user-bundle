# Changelog

## v2.0.0 (2023-12-08)
- min requirement php: >=8.1.0, symfony: ^6.4
- removed kikwik/iptraceable-listener-bundle dependecy
- added BaseUser::passwordChangedFromIp field 
- last/previous login refactoring in BaseUser, new field names: lastLoginAt, lastLoginFromIp, previousLoginAt, previousLoginFromIp



## v1.0.2 (2023-09-24)
- symfony 6 in composer
- add ORM attributes to BaseUser fields
- auto-configuration of stof/doctrine-extensions-bundle
- services parameter definition refactoring
- set createdBy and updatedBy in kikwik:user:xxx commands
- removed SonataUserAdminTrait
- Behat context and example feature for testing password request and reset function
- BaseUser::addRole() and BaseUser::removeRole()
- PasswordController refactoring for symfony 6
- removed AbstractController dependency from PasswordController
- 

## v0.2.2 (2022-05-29)
- auto admin configuration

## v0.2.1 (2022-04-03)
- sender email and name as config parameters

## v0.2.0 (2022-02-15)
- drop support for symfony 4

## v0.1.4 (2021-07-17)
- added support for symfony 5

## v0.1.2 (2020-05-19)
- Password validator (check for not blank and min length password)

## v0.1.1 (2020-03-20)
- en, fr and de translations
- request and reset password controller
- redirect to referer after password change
- user_block template for sonata admin


## v0.0.11 (2020-03-17)
- helpers in SonataUserAdminTrait
- User::isEnabled property and UserChecker class

## v0.0.10 (2020-03-04)
- kikwik:user:change-roles command
- kikwik:user:change-password command
- kikwik:user:create command

## v0.0.8 (2020-02-07)
- getRolesAsLabel and getRolesAsBadges methonds for bootstrap 3 and 4

## v0.0.6 (2020-01-28)
- translations

## v0.0.4 (2020-01-22)
- definitions for configuration file (config/packages/kikwik_user.yaml)
- kikwik:create:user command

## v0.0.3 (2020-01-21)
- getter for loginCount

## v0.0.2 (2020-01-07)
- LoginSubscriber for lastLogin, previousLogin and loginCount fields

## v0.0.1 (2019-12-23)
- definition of BaseUser with some properties and Timestampable, Blameable and IpTraceable doctrine extension
