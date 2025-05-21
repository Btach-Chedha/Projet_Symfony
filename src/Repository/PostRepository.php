<?php

namespace App\Repository;

use App\Entity\Post;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Post>
 */
class PostRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Post::class);
    }

    /**
     * Trouver les publications récentes avec tous les éléments associés
     * 
     * @param int $limit Nombre de publications à récupérer
     * @return Post[] Un tableau d'objets Post
     */
    public function findRecentWithRelations(int $limit = 10): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.comments', 'c')
            ->leftJoin('p.reactions', 'r')
            ->leftJoin('p.author', 'a')
            ->leftJoin('c.author', 'ca')
            ->leftJoin('c.reactions', 'cr')
            ->addSelect('c', 'r', 'a', 'ca', 'cr')
            ->orderBy('p.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère le nombre de réactions par type pour un post
     * 
     * @param Post $post Le post pour lequel compter les réactions
     * @param string $type Le type de réaction (like, support, me_too)
     * @return int Le nombre de réactions
     */
    public function countReactionsByType(Post $post, string $type): int
    {
        return $this->createQueryBuilder('p')
            ->select('COUNT(r.id)')
            ->join('p.reactions', 'r')
            ->where('p.id = :postId')
            ->andWhere('r.type = :type')
            ->setParameter('postId', $post->getId())
            ->setParameter('type', $type)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Vérifie si un utilisateur a réagi à un post avec un certain type
     * 
     * @param Post $post Le post à vérifier
     * @param int $userId L'ID de l'utilisateur
     * @param string $type Le type de réaction (like, support, me_too)
     * @return bool True si l'utilisateur a réagi, sinon false
     */
    public function hasUserReacted(Post $post, int $userId, string $type): bool
    {
        $count = $this->createQueryBuilder('p')
            ->select('COUNT(r.id)')
            ->join('p.reactions', 'r')
            ->where('p.id = :postId')
            ->andWhere('r.type = :type')
            ->andWhere('r.user = :userId')
            ->setParameter('postId', $post->getId())
            ->setParameter('type', $type)
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getSingleScalarResult();
        
        return $count > 0;
    }
}