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
        if (!$accountId) {
            return new JsonResponse(['error' => 'Account ID is required'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $balance = $this->accountService->getBalance($accountId);
        return $balance !== null ? new JsonResponse($balance, JsonResponse::HTTP_OK) : new JsonResponse(0, JsonResponse::HTTP_NOT_FOUND);
    }

    #[Route('/event', methods: ['POST'])]
    public function handleEvent(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $type = $data['type'] ?? null;

        if (!$type) {
            return new JsonResponse(['error' => 'Event type is required'], JsonResponse::HTTP_BAD_REQUEST);
        }

        return match ($type) {
            'deposit' => $this->handleDeposit($data),
            'withdraw' => $this->handleWithdraw($data),
            'transfer' => $this->handleTransfer($data),
            default => new JsonResponse(['error' => 'Invalid event type'], JsonResponse::HTTP_BAD_REQUEST),
        };
    }

    private function handleDeposit(array $data): JsonResponse
    {
        if (!isset($data['destination'], $data['amount']) || !is_numeric($data['amount']) || $data['amount'] <= 0) {
            return new JsonResponse(['error' => 'Invalid destination or amount'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $this->createAccountIfNotExists($data['destination']);
        $this->accountService->deposit($data['destination'], (int) $data['amount']);

        return new JsonResponse([
            'destination' => [
                'id' => $data['destination'], 
                'balance' => $this->accountService->getBalance($data['destination'])
            ]
        ], JsonResponse::HTTP_CREATED);
    }

    private function handleWithdraw(array $data): JsonResponse
    {
        if (!isset($data['origin'], $data['amount']) || !is_numeric($data['amount']) || $data['amount'] <= 0) {
            return new JsonResponse(['error' => 'Invalid origin or amount'], JsonResponse::HTTP_BAD_REQUEST);
        }

        if ($this->accountService->getBalance($data['origin']) === null) {
            return new JsonResponse(0, JsonResponse::HTTP_NOT_FOUND);
        }

        $this->accountService->withdraw($data['origin'], (int) $data['amount']);

        return new JsonResponse([
            'origin' => [
                'id' => $data['origin'], 
                'balance' => $this->accountService->getBalance($data['origin'])
            ]
        ], JsonResponse::HTTP_CREATED);
    }

    private function handleTransfer(array $data): JsonResponse
    {
        if (!isset($data['origin'], $data['destination'], $data['amount']) || !is_numeric($data['amount']) || $data['amount'] <= 0) {
            return new JsonResponse(['error' => 'Invalid transfer data'], JsonResponse::HTTP_BAD_REQUEST);
        }

        if ($this->accountService->getBalance($data['origin']) === null) {
            return new JsonResponse(0, JsonResponse::HTTP_NOT_FOUND);
        }

        $this->createAccountIfNotExists($data['destination']);
        
        $this->accountService->withdraw($data['origin'], (int) $data['amount']);
        $this->accountService->deposit($data['destination'], (int) $data['amount']);

        return new JsonResponse([
            'origin' => [
                'id' => $data['origin'], 
                'balance' => $this->accountService->getBalance($data['origin'])
            ],
            'destination' => [
                'id' => $data['destination'], 
                'balance' => $this->accountService->getBalance($data['destination'])
            ]
        ], JsonResponse::HTTP_CREATED);
    }

    private function createAccountIfNotExists(string $accountId): void
    {
        if ($this->accountService->getBalance($accountId) === null) {
            $this->accountService->createAccount($accountId, 0);
        }
    }
}