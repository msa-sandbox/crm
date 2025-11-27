<?php

declare(strict_types=1);

namespace App\Api\V1\Controller;

use App\Api\V1\Dto\Request\Lead\CreateLeadDto;
use App\Api\V1\Dto\Request\Lead\CreateLeadWithContactDto;
use App\Api\V1\Handler\Lead\CreateLeadHandler;
use App\Api\V1\Response\ApiResponse;
use App\Service\DtoResolver;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
readonly class LeadController
{
    public function __construct(
        private DtoResolver $dtoResolver,
    ) {
    }

    #[Route('/leads', methods: ['POST'])]
    public function createLeads(Request $request, CreateLeadHandler $handler): ApiResponse
    {
        $dtos = $this->dtoResolver->resolve(CreateLeadDto::class, $request->toArray());
        $res = $handler->createBulk($dtos);

        return ApiResponse::success($res);
    }

    public function createLeadsWithContacts(Request $request, CreateLeadHandler $handler): ApiResponse
    {
        $dtos = $this->dtoResolver->resolve(CreateLeadWithContactDto::class, $request->toArray());
        $res = $handler->createBulk($dtos);

        return ApiResponse::success($res);
    }
}
