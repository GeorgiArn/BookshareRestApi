<?php


namespace BookshareRestApiBundle\Service\Messages;


interface MessageServiceInterface
{
    public function createMessage(string $description): bool;
}