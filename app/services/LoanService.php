<?php

namespace app\services;

use app\models\LoanRequest;
use Yii;
use yii\base\Component;
use yii\db\Exception;
use yii\db\Transaction;

class LoanService extends Component
{
    private float $approvalProbability;
    private int $maxApprovedLoansPerUser;

    public function __construct($config = [])
    {
        $this->approvalProbability = Yii::$app->params['loan']['approvalProbability'] ?? 0.1;
        $this->maxApprovedLoansPerUser = Yii::$app->params['loan']['maxApprovedLoansPerUser'] ?? 1;
        parent::__construct($config);
    }

    public function createLoanRequest(array $data): array
    {
        $loanRequest = new LoanRequest();
        $loanRequest->load($data, '');

        if (!$loanRequest->validate()) {
            return [
                'success' => false,
                'errors' => $loanRequest->errors,
            ];
        }

        if (LoanRequest::hasApprovedLoan($loanRequest->user_id)) {
            return [
                'success' => false,
                'message' => 'У пользователя уже есть одобренный займ',
            ];
        }

        if ($loanRequest->save()) {
            return [
                'success' => true,
                'id' => $loanRequest->id,
            ];
        }

        return [
            'success' => false,
            'errors' => $loanRequest->errors,
        ];
    }

    public function processLoanRequests(int $delay = 0): array
    {
        $pendingRequests = LoanRequest::findPendingRequests();
        $processedCount = 0;
        $errors = [];

        foreach ($pendingRequests as $request) {
            try {
                $this->processLoanRequest($request, $delay);
                $processedCount++;
            } catch (Exception $e) {
                $errors[] = [
                    'request_id' => $request->id,
                    'error' => $e->getMessage(),
                ];
                Yii::error("Не удалось обработать заявку {$request->id}: " . $e->getMessage());
            }
        }

        return [
            'success' => true,
            'processed_count' => $processedCount,
            'errors' => $errors,
        ];
    }

    private function processLoanRequest(LoanRequest $request, int $delay): void
    {
        if ($delay > 0) {
            sleep($delay);
        }

        $transaction = Yii::$app->db->beginTransaction();
        
        try {
            $shouldApprove = $this->shouldApproveLoan($request);
            
            if ($shouldApprove && !LoanRequest::hasApprovedLoan($request->user_id)) {
                $request->approve();
                Yii::info("Заявка {$request->id} одобрена для пользователя {$request->user_id}");
            } else {
                $request->decline();
                Yii::info("Заявка {$request->id} отклонена для пользователя {$request->user_id}");
            }
            
            $transaction->commit();
        } catch (Exception $e) {
            $transaction->rollBack();
            throw $e;
        }
    }

    private function shouldApproveLoan(LoanRequest $request): bool
    {
        if (LoanRequest::hasApprovedLoan($request->user_id)) {
            return false;
        }

        $randomValue = mt_rand() / mt_getrandmax();
        return $randomValue <= $this->approvalProbability;
    }

    public function validateLoanRequestData(array $data): array
    {
        $errors = [];

        if (!isset($data['user_id']) || !is_int($data['user_id']) || $data['user_id'] <= 0) {
            $errors['user_id'] = 'ID пользователя должен быть положительным числом';
        }

        if (!isset($data['amount']) || !is_int($data['amount']) || $data['amount'] <= 0) {
            $errors['amount'] = 'Сумма должна быть положительным числом';
        }

        if (!isset($data['term']) || !is_int($data['term']) || $data['term'] <= 0) {
            $errors['term'] = 'Срок должен быть положительным числом';
        }

        return $errors;
    }

    public function getApprovalProbability(): float
    {
        return $this->approvalProbability;
    }

    public function setApprovalProbability(float $probability): void
    {
        $this->approvalProbability = max(0.0, min(1.0, $probability));
    }
}