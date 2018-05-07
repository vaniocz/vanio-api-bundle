<?php
namespace Vanio\ApiBundle\Security;

use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\FormLoginFactory;

class ApiFormFactory extends FormLoginFactory
{
    public function __construct()
    {
        $this->addOption('username_parameter', 'username');
        $this->addOption('password_parameter', 'password');
        $this->addOption('post_only', true);
    }

    public function getListenerId(): string
    {
        return 'security.authentication.listener.api_form';
    }

    public function getKey(): string
    {
        return 'api-form-login';
    }
}
