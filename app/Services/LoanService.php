<?php

namespace App\Services;

use App\Models\Loan;
use App\Models\ReceivedRepayment;
use App\Models\ScheduledRepayment;
use App\Models\User;
use Carbon\Carbon;

class LoanService
{
    /**
     * Create a Loan
     *
     * @param  User  $user
     * @param  int  $amount
     * @param  string  $currencyCode
     * @param  int  $terms
     * @param  string  $processedAt
     *
     * @return Loan
     */
    public function createLoan(User $user, int $amount, string $currencyCode, int $terms, string $processedAt): Loan
    {
        $data = [
            'user_id' => $user->id,
            'amount' => (int) $amount,
            'terms' => $terms,
            'currency_code' => $currencyCode,
            'processed_at' => $processedAt,
        ];
        $dataRepayment = [];
        $loan = Loan::create($data);
        for ($i = 0; $i < $terms; $i++) {
            array_push($dataRepayment, [
                'loan_id' => $loan->id,
                'currency_code' => $currencyCode,
                'due_date' => Carbon::parse($processedAt)->addMonths($i + 1)->toDateString()
            ]);
        }
        ScheduledRepayment::insert($dataRepayment);
        return $loan;
    }

    /**
     * Repay Scheduled Repayments for a Loan
     *
     * @param  Loan  $loan
     * @param  int  $amount
     * @param  string  $currencyCode
     * @param  string  $receivedAt
     *
     * @return ReceivedRepayment
     */
    public function repayLoan(Loan $loan, int $amount, string $currencyCode, string $receivedAt): ReceivedRepayment
    {
        $data = [
            'id' => $loan->id,
            'currency_code' => $currencyCode,
            'outstanding_amount' => $loan->amount - $amount,
            'status' => Loan::STATUS_DUE,
        ];
        $loan->update($data);
        $receivedRepaymentData = [
            'loan_id' => $loan->id,
            'amount' => $amount,
            'currency_code' => $currencyCode,
            'received_at' => $receivedAt,
        ];
        return ReceivedRepayment::create($receivedRepaymentData);
    }
}
