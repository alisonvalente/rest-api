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

    #[Route('/event', methods: ['POST'])]
    public function handleEvent(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $type = $data['type'] ?? null;

        switch ($type) {
            case 'deposit':
                return $this->handleDeposit($data);
            case 'withdraw':
                return $this->handleWithdraw($data);
            case 'transfer':
                return $this->handleTransfer($data);
            default:
                return new JsonResponse(['error' => 'Invalid event type'], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    private function handleDeposit(array $data): JsonResponse
    {
        $this->accountService->createAccount($data['destination'], 0);
        $this->accountService->deposit($data['destination'], $data['amount']);
        return new JsonResponse(['destination' => ['id' => $data['destination'], 'balance' => $this->accountService->getBalance($data['destination'])]], JsonResponse::HTTP_CREATED);
    }

    private function handleWithdraw(array $data): JsonResponse
    {
        if ($this->accountService->getBalance($data['origin']) === null) {
            return new JsonResponse(0, JsonResponse::HTTP_NOT_FOUND);
        }
        $this->accountService->withdraw($data['origin'], $data['amount']);
        return new JsonResponse(['origin' => ['id' => $data['origin'], 'balance' => $this->accountService->getBalance($data['origin'])]], JsonResponse::HTTP_CREATED);
    }

    private function handleTransfer(array $data): JsonResponse
    {
        if ($this->accountService->getBalance($data['origin']) === null || $this->accountService->getBalance($data['destination']) === null) {
            return new JsonResponse(0, JsonResponse::HTTP_NOT_FOUND);
        }
        $this->accountService->withdraw($data['origin'], $data['amount']);
        $this->accountService->createAccount($data['destination'], 0);
        $this->accountService->deposit($data['destination'], $data['amount']);
        return new JsonResponse([
            'origin' => ['id' => $data['origin'], 'balance' => $this->accountService->getBalance($data['origin'])],
            'destination' => ['id' => $data['destination'], 'balance' => $this->accountService->getBalance($data['destination'])]
        ], JsonResponse::HTTP_CREATED);
    }
}