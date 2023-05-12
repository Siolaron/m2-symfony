<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractController
{
    #[Route('/admin/dashboard', name: 'app_dashboard')]
    public function index(UserRepository $users): Response
    {
        try{
            $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        }
        catch(Exception $e){
            return $this->redirectToRoute('app_login');
        }
        
        return $this->render('dashboard/index.html.twig', [
            'users' => $users->findAll(),
        ]);
    }

    #[Route('/admin/dashboard/user/{id}', name: 'app_dashboard_update')]
    public function enableUser(UserRepository $users, User $user, EntityManagerInterface $entityManager): Response
    {
        if($user->isEnabled() == 1){
            $user->setIsEnabled(0);
        }else{
            $user->setIsEnabled(1);
        }
        
        $entityManager->flush();
        
        return $this->redirectToRoute('app_dashboard');
    }
}
