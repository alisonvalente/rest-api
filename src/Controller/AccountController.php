<?php
namespace App\Controller;

use App\Service\AccountService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class AccountController
{
    private AccountService $accountService;

    public function __construct(AccountService $accountService)
    {
        $this->accountService = $accountService;
    }

    #[Route('/reset', methods: ['POST'])]
    public function reset(): JsonResponse
    {
        $this->accountService->reset();
        return new JsonResponse(null, JsonResponse::HTTP_OK);
    }

    #[Route('/balance', methods: ['GET'])]
    public function getBalance(Request $request): JsonResponse
    {
        $accountId = $request->query->get('account_id');
        $balance = $this->accountService->getBalance($accountId);
        return $balance !== null ? new JsonResponse($balance, JsonResponse::HTTP_OK) : new JsonResponse(0, JsonResponse::HTTP_NOT_FOUND);
    }
}