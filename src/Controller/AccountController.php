<?php
namespace App\Controller;

use App\Service\AccountService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;

class AccountController
{
    private AccountService $accountService;

    /**
     * Initializes a new instance of the class.
     *
     * @param AccountService $accountService The account service instance.
     */
    public function __construct(AccountService $accountService)
    {
        $this->accountService = $accountService;
    }

    /**
     * Resets the account service.
     *
     * @return Response An HTTP response indicating success.
     */
    #[Route('/reset', methods: ['POST'])]
    public function reset(): Response
    {
        $this->accountService->reset();
        return new Response('OK', Response::HTTP_OK);
    }

    /**
     * Retrieves the balance of an account based on the provided account ID.
     *
     * @param Request $request The HTTP request containing the account ID query parameter.
     * @return JsonResponse The account balance if found, otherwise an error response.
     */
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

    /**
     * Handles an event based on the provided event type.
     *
     * @param Request $request The HTTP request containing the event data.
     * @return JsonResponse The response based on the event type, or an error response if the event type is invalid or missing.
     */
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

    /**
     * Handles a deposit event by validating the provided data, creating the destination account if necessary, 
     * and performing the deposit operation.
     *
     * @param array $data An array containing the deposit event data, including the destination account ID and the amount to deposit.
     * @return JsonResponse A JSON response containing the updated balance of the destination account, or an error response if the deposit operation fails.
     */
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

    /**
     * Handles a withdraw event by validating the provided data, checking the origin account balance, 
     * and performing the withdraw operation.
     *
     * @param array $data An array containing the withdraw event data, including the origin account ID and the amount to withdraw.
     * @throws None
     * @return JsonResponse A JSON response containing the updated balance of the origin account, or an error response if the withdraw operation fails.
     */
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

    /**
     * Handles a transfer event by validating the provided data, checking the origin account balance, 
     * and performing the transfer operation.
     *
     * @param array $data An array containing the transfer event data, including the origin account ID, 
     *                     the destination account ID, and the amount to transfer.
     * @throws None
     * @return JsonResponse A JSON response containing the updated balances of the origin and destination accounts, 
     *                      or an error response if the transfer operation fails.
     */
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

    /**
     * Creates a new account with a balance of 0 if the account ID does not already exist.
     *
     * @param string $accountId The ID of the account to create if it does not exist.
     * @return void
     */
    private function createAccountIfNotExists(string $accountId): void
    {
        if ($this->accountService->getBalance($accountId) === null) {
            $this->accountService->createAccount($accountId, 0);
        }
    }
}