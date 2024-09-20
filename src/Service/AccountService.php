<?php
namespace App\Service;

class AccountService
{
    private static string $filePath = __DIR__ . '/../../var/data/accounts.json';

    private function writeAccounts(array $accounts): void
    {
        file_put_contents(self::$filePath, json_encode($accounts, JSON_PRETTY_PRINT));
    }

    private function readAccounts(): array
    {
        if (!file_exists(self::$filePath)) {
            return [];
        }
        return json_decode(file_get_contents(self::$filePath), true) ?? [];
    }

    public function getBalance(string $accountId): ?int
    {
        $accounts = $this->readAccounts();
        return $accounts[$accountId] ?? null;
    }

    public function createAccount(string $accountId, int $initialBalance = 0): void
    {
        $accounts = $this->readAccounts();
        if (!isset($accounts[$accountId])) {
            $accounts[$accountId] = $initialBalance;
            $this->writeAccounts($accounts);
        }
    }

    public function deposit(string $accountId, int $amount): void
    {
        $accounts = $this->readAccounts();
        if (isset($accounts[$accountId])) {
            $accounts[$accountId] += $amount;
            $this->writeAccounts($accounts);
        }
    }

    public function withdraw(string $accountId, int $amount): void
    {
        $accounts = $this->readAccounts();
        if (isset($accounts[$accountId])) {
            $accounts[$accountId] -= $amount;
            $this->writeAccounts($accounts);
        }
    }

    public function reset(): void
    {
        $this->writeAccounts([]);
    }
}