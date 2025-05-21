<?php

namespace App\Controller;

use App\Entity\Post;
use App\Entity\Comment;
use App\Entity\Reaction;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ReactionController extends AbstractController
{
    #[Route('/post/{id}/react/{type}', name: 'post_react', methods: ['POST'])]
    public function reactToPost(Post $post, string $type, Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $user = $this->getUser();
        
        // Vérifier si le type de réaction est valide
        $validTypes = ['like', 'support', 'me_too'];
        if (!in_array($type, $validTypes)) {
            return $this->json(['error' => 'Type de réaction non valide'], Response::HTTP_BAD_REQUEST);
        }
        
        // Vérifier si l'utilisateur a déjà réagi avec ce type
        $existingReaction = $entityManager->getRepository(Reaction::class)->findOneBy([
            'post' => $post,
            'user' => $user,
            'type' => $type
        ]);
        
        if ($existingReaction) {
            // Supprimer la réaction existante (toggle)
            $entityManager->remove($existingReaction);
            $entityManager->flush();
            
            return $this->redirectToReferer($request);
        }
        
        // Créer une nouvelle réaction
        $reaction = new Reaction();
        $reaction->setPost($post);
        $reaction->setUser($user);
        $reaction->setType($type);
        
        $entityManager->persist($reaction);
        $entityManager->flush();
        
        return $this->redirectToReferer($request);
    }
    
    #[Route('/comment/{id}/react/{type}', name: 'comment_react', methods: ['POST'])]
    public function reactToComment(Comment $comment, string $type, Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $user = $this->getUser();
        
        // Vérifier si le type de réaction est valide
        $validTypes = ['like', 'support', 'me_too'];
        if (!in_array($type, $validTypes)) {
            return $this->json(['error' => 'Type de réaction non valide'], Response::HTTP_BAD_REQUEST);
        }
        
        // Vérifier si l'utilisateur a déjà réagi avec ce type
        $existingReaction = $entityManager->getRepository(Reaction::class)->findOneBy([
            'comment' => $comment,
            'user' => $user,
            'type' => $type
        ]);
        
        if ($existingReaction) {
            // Supprimer la réaction existante (toggle)
            $entityManager->remove($existingReaction);
            $entityManager->flush();
            
            return $this->redirectToReferer($request);
        }
        
        // Créer une nouvelle réaction
        $reaction = new Reaction();
        $reaction->setComment($comment);
        $reaction->setUser($user);
        $reaction->setType($type);
        
        $entityManager->persist($reaction);
        $entityManager->flush();
        
        return $this->redirectToReferer($request);
    }
    
    private function redirectToReferer(Request $request): Response
    {
        $referer = $request->headers->get('referer');
        return $this->redirect($referer ?: $this->generateUrl('app_home'));
    }
}