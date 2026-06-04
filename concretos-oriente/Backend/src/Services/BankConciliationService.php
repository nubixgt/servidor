<?php
namespace App\Services;

use App\Repositories\BankAccountRepository;
use Exception;

class BankConciliationService
{
    private BankAccountRepository $accountRepository;

    public function __construct()
    {
        $this->accountRepository = new BankAccountRepository();
    }

    public function getAllAccounts(): array
    {
        return $this->accountRepository->findAll();
    }

    public function createAccount(array $data): void
    {
        if (empty($data['nombre_banco']) || empty($data['numero_cuenta']) || empty($data['tipo_cuenta'])) {
            throw new Exception('Faltan campos obligatorios para registrar la cuenta.', 400);
        }

        $this->accountRepository->create($data);
    }

    public function getAccountHistory(int $accountId): array
    {
        return $this->accountRepository->findTransactions($accountId);
    }
}
