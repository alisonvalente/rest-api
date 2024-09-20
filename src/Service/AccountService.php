<?php
namespace App\Service;

class AccountService
{
    private static string $filePath = __DIR__ . '/../../var/data/accounts.json';
    private array $accounts;

    public function __construct()
    {
        $this->accounts = $this->readAccounts();
    }

    private function writeAccounts(): void
    {
        if (false === file_put_contents(self::$filePath, json_encode($this->accounts, JSON_PRETTY_PRINT))) {
            throw new \RuntimeException('Failed to write to accounts file.');
        }
    }

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

    public function getBalance(string $accountId): ?int
    {
        return $this->accounts[$accountId] ?? null;
    }

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

    public function reset(): void
    {
        $this->accounts = [];
        $this->writeAccounts();
    }
}