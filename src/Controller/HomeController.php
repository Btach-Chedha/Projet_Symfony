<?php

namespace App\Controller;

use App\Repository\PostRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/home', name: 'app_home')]
   
    public function index(PostRepository $postRepository): Response
    {
        // Récupérer les publications récentes avec leurs relations
        $recentPosts = $postRepository->findRecentWithRelations(10);
        
        // Placer un tableau vide pour les catégories 
        // Vous pourrez implémenter cette fonctionnalité plus tard
        $categories = [];
        
        return $this->render('home/home.html.twig', [
            'recentPosts' => $recentPosts,
            'categories' => $categories,
        ]);
    }
}