<?php


namespace BookshareRestApiBundle\Service\Books;


use BookshareRestApiBundle\Entity\Book;
use BookshareRestApiBundle\Repository\BookRepository;
use BookshareRestApiBundle\Service\Users\UserService;
use Doctrine\ORM\Query\Expr\Math;

class BookService implements BookServiceInterface
{
    private $bookRepository;
    private $userService;

    public function __construct(BookRepository $bookRepository,
                                UserService $userService)
    {
        $this->bookRepository = $bookRepository;
        $this->userService = $userService;
    }

    /**
     * @param int $id
     * @return Book|null|object
     */
    public function bookById(int $id): ?Book
    {
        return $this->bookRepository->find($id);
    }

    public function update(Book $book): bool
    {
        return $this->bookRepository->merge($book);
    }

    public function getBooksBySearch(string $search): array
    {
        $books = $this->getAllBooks();
        $books = array_filter($books, function ($book) use ($search) {
            /** @var Book $book */
            if (mb_stripos($book->getTitle(), $search) !== false) {
                return true;
            } return false;
        });
        usort($books, function ($a, $b) use ($search) {
            $this->sortBooksBySearch($a, $b, $search);
        });
        $books = array_slice($books, 0, 5);
        return $books;
    }

    public function sortBooksBySearch(Book $firstBook, Book $secondBook, string $search): bool
    {
        $firstBookPosition = mb_stripos($firstBook->getTitle(), $search);
        $secondBookPosition = mb_stripos($secondBook->getTitle(), $search);

        if ( $firstBookPosition === $secondBookPosition ) {
            if ( $firstBook->getRating() === $secondBook->getRating()) {
                if ( $firstBook->getDatePublished() === $secondBook->getDatePublished()) {
                    return 0;
                }
                return (strtotime($firstBook->getDatePublished()) > strtotime($secondBook->getDatePublished())) ? +1 : -1;
            }
            return ($firstBook->getRating() > $secondBook->getRating()) ? -1 : +1;
        }

        return ($firstBookPosition > $secondBookPosition) ? +1 : -1;
    }

    public function getAllBooks(): array
    {
        return $this->bookRepository->findAll();
    }

    public function getBooksByCurrentUser(): array
    {
        $currentUser = $this->userService->getCurrentUser();
        return $this->bookRepository->findBooksByCurrentUser($currentUser);
    }

    public function getMostExchangedBooks(): array
    {
        return $this->bookRepository->findMostExchangedBooks();
    }

    public function getNewestBooks(): array
    {
        return $this->bookRepository->findNewestBooks();
    }

    public function getSuggestedBooksForUser(): array
    {
        $userSubcategories = $this->userService->getUserFavouriteSubcategories();
        $suggestedBook = [];
        if (count($userSubcategories) > 12) {
            $userSubcategories = array_rand(array_flip($userSubcategories), 12);
        }

        $booksPerSubcategories = ceil(12 / count($userSubcategories));
        var_dump($userSubcategories);
        for ($i = 0; $i < $booksPerSubcategories; $i++) {
            foreach ($userSubcategories as $subcategory) {
                var_dump($this->bookRepository->findBookBySubcategory($subcategory)[0]->getTitle());
                break;
            }
            break;
        }
        var_dump($booksPerSubcategories);
        var_dump(count($userSubcategories));
    }
}