<?php
namespace App\Service;

class AccountService
{
    private static string $filePath = __DIR__ . '/../../var/data/accounts.json';

    private function writeAccounts(array $accounts): void
    {
        file_put_contents(self::$filePath, json_encode($accounts, JSON_PRETTY_PRINT));
    }

    public function reset(): void
    {
        $this->writeAccounts([
            '100' => 0,
            '300' => 0,
        ]);
    }
}