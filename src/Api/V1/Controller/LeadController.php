<?php

declare(strict_types=1);

namespace App\Api\V1\Controller;

use App\Api\V1\Response\ApiResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
class LeadController
{
    public function __construct()
    {
    }

    #[Route('/leads')]
    public function getLeads(UserInterface $user): ApiResponse
    {
        return ApiResponse::success($user->getPermissions());

        //        $response = new JsonResponse($user->getPermissions());
        //        return $response;
    }
}
