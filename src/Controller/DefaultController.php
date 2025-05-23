<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;


class DefaultController extends AbstractController
{
    #[Route('/', name: 'root_redirect')]
    public function redirectToUserHome(AuthorizationCheckerInterface $authChecker): RedirectResponse
    {
        if ($authChecker->isGranted('IS_AUTHENTICATED_FULLY')) {
            return $this->redirectToRoute('web_homepage');
        }

        return $this->redirectToRoute('app_login');
    }

}
