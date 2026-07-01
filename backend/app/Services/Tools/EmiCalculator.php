<?php

namespace App\Services\Tools;

/**
 * Equated Monthly Installment calculator. Pure functions, no DB.
 *
 *   EMI = P · r · (1+r)^n / ((1+r)^n − 1)
 *   where r = monthly rate, n = months.
 */
class EmiCalculator
{
    /**
     * @param  float  $principal      Loan amount.
     * @param  float  $annualRate     Annual interest rate in percent (e.g. 12.5).
     * @param  int    $tenureMonths   Number of monthly installments.
     * @param  bool   $withSchedule   Include the full amortization schedule.
     */
    public static function calculate(
        float $principal,
        float $annualRate,
        int $tenureMonths,
        bool $withSchedule = true
    ): array {
        if ($principal <= 0 || $tenureMonths <= 0) {
            throw new \InvalidArgumentException('Principal and tenure must be positive.');
        }

        $monthlyRate = $annualRate / 12 / 100;

        if ($monthlyRate == 0.0) {
            // Interest-free: equal principal split.
            $emi = $principal / $tenureMonths;
        } else {
            $factor = pow(1 + $monthlyRate, $tenureMonths);
            $emi = $principal * $monthlyRate * $factor / ($factor - 1);
        }

        $emi = round($emi, 2);
        $totalPayable = round($emi * $tenureMonths, 2);
        $totalInterest = round($totalPayable - $principal, 2);

        $result = [
            'principal'      => round($principal, 2),
            'annual_rate'    => $annualRate,
            'tenure_months'  => $tenureMonths,
            'emi'            => $emi,
            'total_interest' => $totalInterest,
            'total_payable'  => $totalPayable,
        ];

        if ($withSchedule) {
            $result['schedule'] = self::schedule($principal, $monthlyRate, $emi, $tenureMonths);
        }

        return $result;
    }

    /** Month-by-month amortization (principal/interest split + balance). */
    protected static function schedule(float $principal, float $rate, float $emi, int $months): array
    {
        $balance = $principal;
        $rows = [];

        for ($m = 1; $m <= $months; $m++) {
            $interest = round($balance * $rate, 2);
            $principalPaid = round($emi - $interest, 2);

            // Final installment absorbs rounding drift so balance lands at 0.
            if ($m === $months) {
                $principalPaid = round($balance, 2);
                $emiRow = round($principalPaid + $interest, 2);
            } else {
                $emiRow = $emi;
            }

            $balance = round($balance - $principalPaid, 2);

            $rows[] = [
                'month'     => $m,
                'emi'       => $emiRow,
                'principal' => $principalPaid,
                'interest'  => $interest,
                'balance'   => max($balance, 0),
            ];
        }

        return $rows;
    }
}
