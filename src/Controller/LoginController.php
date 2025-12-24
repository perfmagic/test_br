<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;

class LoginController extends AbstractController
{
    #[Route('/v1/api/login', name: 'api_login', methods: ['POST'])]
    public function login(): void
    {
        // This code is never executed.
        // The request is intercepted by the json_login authenticator firewall.
        // See config/packages/security.yaml
        throw new \LogicException('This code should not be reached!');
    }
}
