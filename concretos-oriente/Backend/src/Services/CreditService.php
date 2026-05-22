<?php
namespace App\Services;

use App\Repositories\CreditRepository;
use Exception;

class CreditService
{
    private CreditRepository $creditRepository;

    public function __construct()
    {
        $this->creditRepository = new CreditRepository();
    }

    public function getAllCredits(): array
    {
        return $this->creditRepository->findAllWithPayments();
    }

    public function createCredit(array $data): void
    {
        if (empty($data['supplier_id']) || empty($data['amount'])) {
            throw new Exception('Proveedor y monto son obligatorios', 400);
        }

        $this->creditRepository->createCredit($data);
    }

    public function createPayment(array $data, ?array $fileData): void
    {
        if (empty($data['credit_id']) || empty($data['amount']) || empty($data['bank_account_id'])) {
            throw new Exception('Crédito, monto y cuenta bancaria son obligatorios', 400);
        }

        if ($fileData && $fileData['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../../Uploads/Payments/' . $data['credit_id'] . '/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $filename = time() . '_' . basename($fileData['name']);
            $targetFile = $uploadDir . $filename;
            
            if (move_uploaded_file($fileData['tmp_name'], $targetFile)) {
                $data['receipt_path'] = 'Uploads/Payments/' . $data['credit_id'] . '/' . $filename;
            }
        }

        $pdo = $this->creditRepository->getPDO();
        $pdo->beginTransaction();

        try {
            $this->creditRepository->createPayment($data);

            $creditInfo = $this->creditRepository->getCreditStatusInfo((int)$data['credit_id']);

            if ($creditInfo) {
                $newStatus = 'Pendiente';
                if ($creditInfo['total_paid'] >= $creditInfo['amount']) {
                    $newStatus = 'Pagado';
                } elseif ($creditInfo['total_paid'] > 0) {
                    $newStatus = 'Parcial';
                }

                $this->creditRepository->updateCreditStatus((int)$data['credit_id'], $newStatus);
            }

            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
