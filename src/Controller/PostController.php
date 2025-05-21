<?php

namespace App\Controller;

use App\Entity\Post;
use App\Entity\Comment;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PostController extends AbstractController
{
    #[Route('/post/create', name: 'post_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        
        $content = $request->request->get('content');
        $isAnonymous = $request->request->getBoolean('is_anonymous', false);
        
        if (empty($content)) {
            $this->addFlash('danger', 'Le contenu de la publication ne peut pas être vide.');
            return $this->redirectToRoute('app_home');
        }
        
        $post = new Post();
        $post->setContent($content);
        $post->setIsAnonymous($isAnonymous);
        $post->setCreatedAt(new \DateTimeImmutable());
        $post->setAuthor($this->getUser());
        
        $entityManager->persist($post);
        $entityManager->flush();
        
        $this->addFlash('success', 'Votre publication a été créée avec succès.');
        return $this->redirectToRoute('app_home');
    }
    
    #[Route('/post/{postId}/comment', name: 'comment_create', methods: ['POST'])]
    public function comment(int $postId, Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        
        $post = $entityManager->getRepository(Post::class)->find($postId);
        
        if (!$post) {
            $this->addFlash('danger', 'Publication non trouvée.');
            return $this->redirectToRoute('app_home');
        }
        
        $content = $request->request->get('content');
        
        if (empty($content)) {
            $this->addFlash('danger', 'Le contenu du commentaire ne peut pas être vide.');
            return $this->redirectToRoute('app_home');
        }
        
        $comment = new Comment();
        $comment->setContent($content);
        $comment->setCreatedAt(new \DateTimeImmutable());
        $comment->setAuthor($this->getUser());
        $comment->setPost($post);
        
        $entityManager->persist($comment);
        $entityManager->flush();
        
        $this->addFlash('success', 'Votre commentaire a été ajouté avec succès.');
        return $this->redirectToRoute('app_home');
    }
}