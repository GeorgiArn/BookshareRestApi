<?php

namespace BookshareRestApiBundle\Controller;

use BookshareRestApiBundle\Service\Addresses\AddressServiceInterface;
use BookshareRestApiBundle\Service\Books\BookServiceInterface;
use BookshareRestApiBundle\Service\Requests\RequestServiceInterface;
use BookshareRestApiBundle\Service\Users\UsersServiceInterface;
use FOS\RestBundle\Controller\Annotations\Route;
use JMS\Serializer\Serializer;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class BookRequestController extends Controller
{
    private $bookRequestService;
    private $userService;
    private $bookService;
    private $addressService;
    private $encoder;
    private $normalizer;

    public function __construct(RequestServiceInterface $bookRequestService,
                                UsersServiceInterface $userService,
                                BookServiceInterface $bookService,
                                AddressServiceInterface $addressService)
    {
        $this->bookRequestService = $bookRequestService;
        $this->userService = $userService;
        $this->bookService = $bookService;
        $this->addressService = $addressService;
        $this->encoder = new JsonEncoder();
        $this->normalizer = new ObjectNormalizer();
        $this->normalizer->setCircularReferenceHandler(function ($object) {
            return $object->getId();
        });
    }

    /**
     * @Route("/private/book-request", methods={"POST"})
     * @param Request $request
     *
     * @return Response
     */
    public function requestBook(Request $request) {
        $book = $this->bookService->bookById(json_decode($request->getContent(), true)['bookId']);
        $address = $this->addressService->addressById(intval(json_decode($request->getContent(), true)['addressId']));
        $this->bookRequestService->createRequest($book, $address);

        return new Response(null, Response::HTTP_CREATED);
    }

    /**
     * @Route("/private/accept-book", methods={"POST"})
     * @param Request $request
     *
     * @return Response
     */
    public function acceptBook(Request $request) {
        $this->bookRequestService->acceptRequest(json_decode($request->getContent(), true)['requestId'],
            json_decode($request->getContent(), true)['bookId'],
            json_decode($request->getContent(), true)['addressId']);

        return new Response(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @Route("/private/unread-requests-count", methods={"GET"})
     *
     * @return Response
     */
    public function getUnreadRequests() {
        $unreadRequests = $this->bookRequestService->getAllUnreadRequestsForCurrentUserCount();

        $serializer = $this->container->get('jms_serializer');
        $requestsJson = $serializer->serialize($unreadRequests, 'json');

        return new Response($requestsJson,
            Response::HTTP_OK,
            ['content-type' => 'application/json']);
    }

    /**
     * @Route("/private/all-requests", methods={"GET"})
     *
     * @return Response
     */
    public function getAllRequests() {
        $requests = $this->bookRequestService->getAllRequestsForCurrentUser();

        $serializer = new \Symfony\Component\Serializer\Serializer(array($this->normalizer), array($this->encoder));

        $requestsJson = $serializer->serialize($requests, 'json', ['attributes' => ['id', 'isAccepted', 'isReadByRequester', 'isReadByReceiver',
            'requester' => ['id', 'email', 'firstName', 'lastName', 'address', 'phoneNumber'],
            'receiver' => ['id', 'email', 'firstName', 'lastName', 'address', 'phoneNumber'],
            'requestedBook' => ['id', 'title', 'author', 'description', 'publisher', 'datePublished', 'imageURL', 'rating'],
            'chosenBook' => ['id', 'title', 'author', 'description', 'publisher', 'datePublished', 'pages', 'imageURL', 'rating']
        ]]);


        return new Response($requestsJson,
            Response::HTTP_OK,
            ['content-type' => 'application/json']);
    }

    /**
     * @Route("/private/read-unread-requests", methods={"GET"})
     *
     * @return Response
     */
    public function readAllUnreadRequests() {
        $this->bookRequestService->readAllUnreadRequests();

        return new Response(null, Response::HTTP_CREATED);
    }

    /**
     * @Route("/private/request/{id}", methods={"GET"})
     *
     * @param int $id
     * @return Response
     */
    public function getRequestById(int $id) {
        $request = $this->bookRequestService->requestById($id);

        $this->normalizer->setIgnoredAttributes(["email", "username","lastName", "phoneNumber", "roles", "address","password","chooses","users","requests","receipts","requests","requestedBook","chosenBook","dateRequested","bookRequests", "addresses","requesterAddress", "receiverAddress", "suggestions", "messages"]);

        $serializer = new \Symfony\Component\Serializer\Serializer(array($this->normalizer), array($this->encoder));
        $requestJson = $serializer->serialize($request, 'json');

        return new Response($requestJson,
            Response::HTTP_OK,
            array('content_type' => 'application/json'));
    }

    /**
     * @Route("/private/request-info/{id}", methods={"GET"})
     *
     * @param int $id
     * @return Response
     */
    public function getRequestInfoById(int $id) {
        $request = $this->bookRequestService->requestById($id);

        $serializer = new \Symfony\Component\Serializer\Serializer(array($this->normalizer), array($this->encoder));
        $requestJson = $serializer->serialize($request, 'json', ['attributes' => ['id', 'isAccepted', 'isReadByRequester', 'isReadByReceiver',
            'requesterAddress' => ['id', 'address', 'courierService' => ['id', 'courierServiceName'], 'city' => ['id', 'cityName']],
            'receiverAddress' => ['id', 'address', 'courierService' => ['id', 'courierServiceName'], 'city' => ['id', 'cityName']],
            'requester' => ['id', 'email', 'firstName', 'lastName', "phoneNumber"],
            'receiver' => ['id', 'email', 'firstName', 'lastName', "phoneNumber"],
            'requestedBook' => ['id', 'title', 'author', 'description', 'publisher', 'datePublished', 'imageURL', 'rating'],
            'chosenBook' => ['id', 'title', 'author', 'description', 'publisher', 'datePublished', 'imageURL', 'rating']
        ]]);

        return new Response($requestJson,
            Response::HTTP_OK,
            array('content_type' => 'application/json'));
    }

    /**
     * @Route("/private/cancel-request", methods={"POST"})
     *
     * @param Request $request
     * @return Response
     */
    public function cancelRequestIfNotAccepted(Request $request) {
        $requestId = intval(json_decode($request->getContent(), true)['requestId']);
        $userId = intval(json_decode($request->getContent(), true)['userId']);
        $request = $this->bookRequestService->requestById($requestId);

        $this->bookRequestService->cancelRequest($request, $userId);

        return new Response(null, Response::HTTP_CREATED);
    }
}
