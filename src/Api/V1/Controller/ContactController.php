<?php

declare(strict_types=1);

namespace App\Api\V1\Controller;

use App\Api\V1\Dto\Request\Contact\CreateContactCollection;
use App\Api\V1\Dto\Request\Contact\GetContactItemQueryDto;
use App\Api\V1\Dto\Request\Contact\GetContactQueryDto;
use App\Api\V1\Handler\Contact\CreateContactHandler;
use App\Api\V1\Handler\Contact\GetContactHandler;
use App\Api\V1\Response\ApiResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
readonly class ContactController
{
    #[Route('/contacts', methods: ['POST'])]
    public function createContacts(
        #[MapRequestPayload] CreateContactCollection $dtos,
        CreateContactHandler $handler,
    ): ApiResponse {
        $res = $handler->createBulk($dtos);

        return ApiResponse::success($res);
    }

    #[Route('/contacts', methods: ['GET'])]
    public function getContacts(
        #[MapQueryString] GetContactQueryDto $query,
        GetContactHandler $handler,
    ): ApiResponse {
        $res = $handler->getList($query);

        return ApiResponse::success($res);
    }

    #[Route('/contacts/{id}', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function getContact(
        #[MapQueryString] GetContactItemQueryDto $query,
        int $id,
        GetContactHandler $handler,
    ): ApiResponse {
        $res = $handler->getOneById($id, $query);

        return ApiResponse::success($res);
    }
}
