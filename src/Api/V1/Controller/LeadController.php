<?php

declare(strict_types=1);

namespace App\Api\V1\Controller;

use App\Api\V1\Dto\Request\Lead\CreateLeadCollectionDto;
use App\Api\V1\Dto\Request\Lead\CreateLeadWithContactCollectionDto;
use App\Api\V1\Dto\Request\Lead\GetLeadItemQueryDto;
use App\Api\V1\Dto\Request\Lead\GetLeadQueryDto;
use App\Api\V1\Handler\Lead\CreateLeadHandler;
use App\Api\V1\Handler\Lead\GetLeadHandler;
use App\Api\V1\Response\ApiResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
readonly class LeadController
{
    #[Route('/leads', methods: ['POST'])]
    public function createLeads(
        #[MapRequestPayload] CreateLeadCollectionDto $dtos,
        CreateLeadHandler $handler,
    ): ApiResponse {
        $res = $handler->createBulk($dtos);

        return ApiResponse::success($res);
    }

    #[Route('/leads/complex', methods: ['POST'])]
    public function createLeadsWithContacts(
        #[MapRequestPayload] CreateLeadWithContactCollectionDto $dtos,
        CreateLeadHandler $handler,
    ): ApiResponse {
        $res = $handler->createBulkWithContacts($dtos);

        return ApiResponse::success($res);
    }

    #[Route('/leads', methods: ['GET'])]
    public function getLeads(
        #[MapQueryString] GetLeadQueryDto $query,
        GetLeadHandler $handler,
    ): ApiResponse {
        $res = $handler->getList($query);

        return ApiResponse::success($res);
    }

    #[Route('/leads/{id}', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function getLead(
        #[MapQueryString] GetLeadItemQueryDto $query,
        int $id,
        GetLeadHandler $handler,
    ): ApiResponse {
        $res = $handler->getOneById($id, $query);

        return ApiResponse::success($res);
    }
}
