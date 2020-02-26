<?php

namespace BookshareRestApiBundle\Repository;

use BookshareRestApiBundle\Entity\Book;
use BookshareRestApiBundle\Entity\Subcategory;
use BookshareRestApiBundle\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;

/**
 * BookRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class BookRepository extends \Doctrine\ORM\EntityRepository
{
    public function __construct(EntityManagerInterface $em,
                                Mapping\ClassMetadata $metadata = null)
    {
        parent::__construct($em,
            $metadata == null ?
                new Mapping\ClassMetadata(Book::class) :
                $metadata
        );
    }

    public function merge(Book $book): bool
    {
        try {
            $this->_em->merge($book);
            $this->_em->flush();
            return true;
        } catch ( OptimisticLockException $e ) {
            return false;
        } catch ( ORMException $e ) {
            return false;
        }
    }

    public function findBooksByCurrentUser(User $user) {
        return
            $this
                ->createQueryBuilder('users_books')
                ->leftJoin('users_books.users', 'user')
                ->where('user.id = :id')
                ->setParameter('id', $user->getId())
                ->getQuery()
                ->getResult();
    }

    public function findMostExchangedBooks() {
        return
            $this
                ->createQueryBuilder('books')
                ->leftJoin('books.requests', 'requests')
                ->leftJoin('books.chooses', 'chooses')
                ->addGroupBy('requests.requestedBook')
                ->addGroupBy('chooses.chosenBook')
                ->addOrderBy('count(requests)', 'DESC')
                ->addOrderBy('count(chooses)', 'DESC')
                ->setMaxResults(12)
                ->getQuery()
                ->getResult();
    }

    public function findNewestBooks() {
        return
            $this
                ->createQueryBuilder('books')
                ->addOrderBy('books.datePublished', 'DESC')
                ->setMaxResults(12)
                ->getQuery()
                ->getResult();
    }
}
