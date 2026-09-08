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

    public function createAccount(array $data): int
    {
        if (empty($data['nombre_banco']) || empty($data['numero_cuenta']) || empty($data['tipo_cuenta'])) {
            throw new Exception('Faltan campos obligatorios para registrar la cuenta.', 400);
        }

        return $this->accountRepository->create($data);
    }

    public function updateAccount(int $id, array $data): void
    {
        if (empty($data['nombre_banco']) || empty($data['numero_cuenta']) || empty($data['tipo_cuenta'])) {
            throw new Exception('Faltan campos obligatorios para actualizar la cuenta.', 400);
        }

        $this->accountRepository->update($id, $data);
    }

    public function deleteAccount(int $id): void
    {
        $this->accountRepository->delete($id);
    }

    public function getAccountHistory(int $accountId): array
    {
        return $this->accountRepository->findTransactions($accountId);
    }

    public function getAllTransactions(int $limit = 50): array
    {
        return $this->accountRepository->getAllTransactions($limit);
    }

    public function transfer(int $sourceId, int $destinationId, float $amount, string $date, string $description, ?string $reference = null): void
    {
        $this->accountRepository->transfer($sourceId, $destinationId, $amount, $date, $description, $reference);
    }

    public function reconcile(int $id, float $nuevoSaldo, string $notas = ''): void
    {
        $this->accountRepository->reconcile($id, $nuevoSaldo, $notas);
    }
}

