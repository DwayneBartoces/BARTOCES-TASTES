<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    #[Route('/home', name: 'app_home_alt')]
    #[Route('/dashboard', name: 'app_dashboard')]
    #[Route('/index.php', name: 'app_home_php')]
    public function index(Request $request): RedirectResponse
    {
        $baseUrl = $request->getBaseUrl();
        
        $rootUrl = preg_replace('#/public(/index\.php)?$#i', '', $baseUrl);
        $target  = ($rootUrl ?: '') . '/index.php';

        return $this->redirect($target ?: '/index.php');
    }
}
