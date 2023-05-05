<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard')]
    public function index(UserRepository $users): Response
    {
        try{
            $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        }
        catch(Exception $e){
            return $this->redirectToRoute('app_login');
        }
        try{
            $this->denyAccessUnlessGranted('ROLE_ADMIN');
        }
        catch(Exception $e){
            return $this->redirectToRoute('app_home');
        }
        

        return $this->render('dashboard/index.html.twig', [
            'users' => $users->findAll(),
        ]);
    }
}