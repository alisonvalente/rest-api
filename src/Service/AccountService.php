<?php
namespace App\Service;

/**
 * Service class responsible for managing account operations, 
 */
class AccountService
{
    private static string $filePath = __DIR__ . '/../../var/data/accounts.json';
    private array $accounts;

    /**
     * Initializes the account service by loading existing accounts from storage.
     *
     * @throws Exception If an error occurs while reading accounts from storage.
     */
    public function __construct()
    {
        $this->accounts = $this->readAccounts();
    }

    /**
     * Writes the current account data to the storage file.
     *
     * @throws \RuntimeException If an error occurs while writing to the accounts file.
     * @return void
     */
    private function writeAccounts(): void
    {
        if (false === file_put_contents(self::$filePath, json_encode($this->accounts, JSON_PRETTY_PRINT))) {
            throw new \RuntimeException('Failed to write to accounts file.');
        }
    }

    /**
     * Reads the account data from the storage file.
     *
     * @throws \RuntimeException If an error occurs while reading or decoding the account data.
     * @return array The account data read from the storage file.
     */
    private function readAccounts(): array
    {
        if (!file_exists(self::$filePath)) {
            return [];
        }

        $data = file_get_contents(self::$filePath);
        $accounts = json_decode($data, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Failed to decode JSON: ' . json_last_error_msg());
        }

        return $accounts ?? [];
    }

    /**
     * Retrieves the current balance of a specific account.
     *
     * @param string $accountId The unique identifier of the account.
     * @return int|null The balance of the account, or null if the account does not exist.
     */
    public function getBalance(string $accountId): ?int
    {
        return $this->accounts[$accountId] ?? null;
    }

    /**
     * Creates a new account with the specified account ID and initial balance.
     *
     * @param string $accountId The unique identifier of the account.
     * @param int $initialBalance The initial balance of the account (default is 0).
     * @throws \InvalidArgumentException If the initial balance is negative.
     * @return void
     */
    public function createAccount(string $accountId, int $initialBalance = 0): void
    {
        if (!isset($this->accounts[$accountId])) {
            if ($initialBalance < 0) {
                throw new \InvalidArgumentException('Initial balance cannot be negative.');
            }

            $this->accounts[$accountId] = $initialBalance;
            $this->writeAccounts();
        }
    }

    /**
     * Deposits a specified amount into a specific account.
     *
     * @param string $accountId The unique identifier of the account.
     * @param int $amount The amount to be deposited.
     * @throws \InvalidArgumentException If the deposit amount is not positive.
     * @throws \RuntimeException If the account does not exist.
     * @return void
     */
    public function deposit(string $accountId, int $amount): void
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Deposit amount must be positive.');
        }

        if (isset($this->accounts[$accountId])) {
            $this->accounts[$accountId] += $amount;
            $this->writeAccounts();
        } else {
            throw new \RuntimeException('Account does not exist.');
        }
    }

    /**
     * Withdraws a specified amount from a specific account.
     *
     * @param string $accountId The unique identifier of the account.
     * @param int $amount The amount to be withdrawn.
     * @throws \InvalidArgumentException If the withdrawal amount is not positive.
     * @throws \RuntimeException If the account does not exist or if there are insufficient funds.
     * @return void
     */
    public function withdraw(string $accountId, int $amount): void
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Withdrawal amount must be positive.');
        }

        if (isset($this->accounts[$accountId])) {
            if ($this->accounts[$accountId] < $amount) {
                throw new \RuntimeException('Insufficient funds.');
            }

            $this->accounts[$accountId] -= $amount;
            $this->writeAccounts();
        } else {
            throw new \RuntimeException('Account does not exist.');
        }
    }

    /**
     * Resets all accounts, clearing the stored data.
     *
     * @return void
     */
    public function reset(): void
    {
        $this->accounts = [];
        $this->writeAccounts();
    }
}