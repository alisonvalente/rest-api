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

    public function reset(): void
    {
        $this->writeAccounts([
            '100' => 0,
            '300' => 0,
        ]);
    }
}