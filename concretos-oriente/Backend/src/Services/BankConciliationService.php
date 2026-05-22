<?php
namespace App\Services;

use App\Repositories\BankAccountRepository;
use App\Repositories\BankReconciliationRepository;
use Exception;

class BankConciliationService
{
    private BankAccountRepository $accountRepository;
    private BankReconciliationRepository $reconciliationRepository;

    public function __construct()
    {
        $this->accountRepository = new BankAccountRepository();
        $this->reconciliationRepository = new BankReconciliationRepository();
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

    public function getPendingTransactions(?string $cuenta): array
    {
        $transactions = $this->accountRepository->findTransactions($cuenta);
        
        usort($transactions, fn($a, $b) => strtotime($b['date']) - strtotime($a['date']));

        return array_map(function($tx) {
            return [
                'id' => $tx['type'] . '-' . $tx['id'],
                'date' => $tx['date'],
                'bankDesc' => $tx['bankDesc'] . ' - ' . $tx['account'],
                'detail' => $tx['detail'] ?: 'Sin detalle',
                'refSystem' => 'Ref DB: ' . $tx['id'],
                'isLinked' => true,
                'amount' => (float)$tx['amount'],
                'type' => $tx['type'],
                'status' => 'pending'
            ];
        }, $transactions);
    }

    public function createReconciliation(array $data): void
    {
        if (empty($data['bank_account_id']) || empty($data['periodo'])) {
            throw new Exception('Cuenta bancaria y período son obligatorios.', 400);
        }

        if (isset($data['partidas_conciliatorias']) && is_array($data['partidas_conciliatorias'])) {
            $data['partidas_conciliatorias'] = json_encode($data['partidas_conciliatorias']);
        } elseif (empty($data['partidas_conciliatorias'])) {
            $data['partidas_conciliatorias'] = '[]';
        }

        $this->reconciliationRepository->create($data);
    }
}
