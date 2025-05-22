<?php

namespace App\Controller;

use App\Entity\Post;
use App\Entity\Comment;
use App\Entity\Reaction;
use App\Form\PostForm;
use App\Repository\PostRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/home', name: 'app_home')]
    public function index(PostRepository $postRepository): Response
    {
        $recentPosts = $postRepository->findRecentWithRelations(10);
        $categories = [];
        
        return $this->render('home/home.html.twig', [
            'recentPosts' => $recentPosts,
            'categories' => $categories,
        ]);
    }

    #[Route('/post', name: 'app_post_index')]
    public function postIndex(PostRepository $postRepository): Response
    {
        return $this->render('post/index.html.twig', [
            'posts' => $postRepository->findAll(),
        ]);
    }

    #[Route('/post/{id}', name: 'app_post_show', methods: ['GET'])]
    public function show(Post $post): Response
    {
        return $this->render('post/show.html.twig', [
            'post' => $post,
        ]);
    }

    #[Route('/post/{id}/edit', name: 'app_post_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Post $post, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        if ($this->getUser() !== $post->getAuthor() && !$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('error', 'Vous n\'êtes pas autorisé à modifier cette publication.');
            return $this->redirectToRoute('app_post_index');
        }

        $form = $this->createForm(PostForm::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Publication modifiée avec succès.');
            return $this->redirectToRoute('app_post_index');
        }

        return $this->render('post/edit.html.twig', [
            'post' => $post,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/comment/new/{post_id}', name: 'app_comment_new', methods: ['POST'])]
    public function newComment(Request $request, int $post_id, PostRepository $postRepository, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $post = $postRepository->find($post_id);
        if (!$post) {
            $this->addFlash('error', 'Publication non trouvée.');
            return $this->redirectToRoute('app_home');
        }

        if ($this->isCsrfTokenValid('comment_new', $request->request->get('_token'))) {
            $comment = new Comment();
            $comment->setContent($request->request->get('content'));
            $comment->setAuthor($this->getUser());
            $comment->setPost($post);
            $comment->setCreatedAt(new \DateTimeImmutable());

            $entityManager->persist($comment);
            $entityManager->flush();

            $this->addFlash('success', 'Commentaire ajouté avec succès.');
        } else {
            $this->addFlash('error', 'Jeton CSRF invalide.');
        }

        return $this->redirectToRoute('app_home');
    }

    #[Route('/post/{id}/react/{type}', name: 'app_post_react', methods: ['GET'])]
    public function reactToPost(Post $post, string $type, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $validTypes = ['like', 'support', 'me_too'];
        if (!in_array($type, $validTypes)) {
            $this->addFlash('error', 'Type de réaction invalide.');
            return $this->redirectToRoute('app_home');
        }

        $existingReaction = $entityManager->getRepository(Reaction::class)->findOneBy([
            'post' => $post,
            'user' => $this->getUser(),
            'type' => $type,
        ]);

        if (!$existingReaction) {
            $reaction = new Reaction();
            $reaction->setPost($post);
            $reaction->setUser($this->getUser());
            $reaction->setType($type);

            $entityManager->persist($reaction);
            $entityManager->flush();

            $this->addFlash('success', 'Réaction ajoutée.');
        } else {
            $this->addFlash('info', 'Vous avez déjà réagi avec ce type.');
        }

        return $this->redirectToRoute('app_home');
    }

    #[Route('/comment/{id}/react/{type}', name: 'app_comment_react', methods: ['GET'])]
    public function reactToComment(Comment $comment, string $type, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $validTypes = ['like'];
        if (!in_array($type, $validTypes)) {
            $this->addFlash('error', 'Type de réaction invalide.');
            return $this->redirectToRoute('app_home');
        }

        $existingReaction = $entityManager->getRepository(Reaction::class)->findOneBy([
            'comment' => $comment,
            'user' => $this->getUser(),
            'type' => $type,
        ]);

        if (!$existingReaction) {
            $reaction = new Reaction();
            $reaction->setComment($comment);
            $reaction->setUser($this->getUser());
            $reaction->setType($type);

            $entityManager->persist($reaction);
            $entityManager->flush();

            $this->addFlash('success', 'Réaction ajoutée.');
        } else {
            $this->addFlash('info', 'Vous avez déjà réagi avec ce type.');
        }

        return $this->redirectToRoute('app_home');
    }

    #[Route('/post/{id}/delete', name: 'app_post_delete', methods: ['POST'])]
    public function deletePost(Request $request, Post $post, EntityManagerInterface $entityManager): Response
    {
        if ($this->isGranted('ROLE_ADMIN') || ($this->getUser() && $post->getAuthor() === $this->getUser())) {
            if ($this->isCsrfTokenValid('delete'.$post->getId(), $request->request->get('_token'))) {
                $entityManager->remove($post);
                $entityManager->flush();

                $this->addFlash('success', 'La publication a été supprimée avec succès.');
            } else {
                $this->addFlash('error', 'Jeton CSRF invalide.');
            }
        } else {
            $this->addFlash('error', 'Vous n\'êtes pas autorisé à supprimer cette publication.');
        }

        return $this->redirectToRoute('app_home');
    }

    #[Route('/comment/{id}/delete', name: 'app_comment_delete', methods: ['POST'])]
    public function deleteComment(Request $request, Comment $comment, EntityManagerInterface $entityManager): Response
    {
        if ($this->isGranted('ROLE_ADMIN') || ($this->getUser() && $comment->getAuthor() === $this->getUser())) {
            if ($this->isCsrfTokenValid('delete'.$comment->getId(), $request->request->get('_token'))) {
                $entityManager->remove($comment);
                $entityManager->flush();

                $this->addFlash('success', 'Le commentaire a été supprimé avec succès.');
            } else {
                $this->addFlash('error', 'Jeton CSRF invalide.');
            }
        } else {
            $this->addFlash('error', 'Vous n\'êtes pas autorisé à supprimer ce commentaire.');
        }

        return $this->redirectToRoute('app_home');
    }
}