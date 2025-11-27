<?php

declare(strict_types=1);

namespace App\Api\V1\Controller;

use App\Api\V1\Dto\Request\Contact\CreateContactDto;
use App\Api\V1\Handler\Contact\CreateContactHandler;
use App\Api\V1\Response\ApiResponse;
use App\Service\DtoResolver;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
readonly class ContactController
{
    public function __construct(
        private DtoResolver $dtoResolver,
    ) {
    }

    #[Route('/contacts', methods: ['POST'])]
    public function createContacts(Request $request, CreateContactHandler $handler): ApiResponse
    {
        $dtos = $this->dtoResolver->resolve(CreateContactDto::class, $request->toArray());
        $res = $handler->createBulk($dtos);

        return ApiResponse::success($res);
    }
}
