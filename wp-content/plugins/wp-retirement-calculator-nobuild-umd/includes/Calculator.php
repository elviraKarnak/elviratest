<?php
namespace WPRC_UMD;
if ( ! defined( 'ABSPATH' ) ) { exit; }

class Calculator {
    public function run(array $input): array {
        $age                = $this->num($input['age'] ?? 35, 18, 74);
        $retire_age         = $this->num($input['retireAge'] ?? 67, max($age+1, 50), 75);
        $current_balance    = $this->money($input['currentBalance'] ?? 80000);
        $salary             = $this->money($input['salary'] ?? 90000);
        $sg_rate            = $this->pct($input['sgRate'] ?? 0.12);
        $vol_pre_tax_pct    = $this->pct($input['volPreTaxPct'] ?? 0.00);
        $vol_after_tax_annual = $this->money($input['volAfterTaxAnnual'] ?? 0);
        $salary_growth      = $this->pct($input['salaryGrowth'] ?? 0.035);
        $return_nominal     = $this->pct($input['returnNominal'] ?? 0.065);
        $earnings_tax       = $this->pct($input['earningsTax'] ?? 0.07);
        $contrib_tax        = $this->pct($input['contribTax'] ?? 0.15);
        $fee_pct            = $this->pct($input['feePct'] ?? 0.007);
        $fee_fixed          = $this->money($input['feeFixed'] ?? 100);
        $inflation          = $this->pct($input['inflation'] ?? 0.025);
        $longevity_age      = $this->num($input['longevityAge'] ?? 92, $retire_age+1, 110);
        $age_pension_pa     = $this->money($input['agePensionPa'] ?? 0);

        $years_to_retire = max(0, $retire_age - $age);
        $proj = [];
        $balance = $current_balance;
        $sal = $salary;

        for ($y = 0; $y < $years_to_retire; $y++) {
            $employer = $sal * $sg_rate;
            $vol_pre  = $sal * $vol_pre_tax_pct;
            $gross_contrib = $employer + $vol_pre + $vol_after_tax_annual;
            $net_contrib = $gross_contrib - (($employer + $vol_pre) * $contrib_tax);

            $fees = $balance * $fee_pct + $fee_fixed;
            $earnings = $balance * $return_nominal;
            $earnings_after_tax = $earnings * (1.0 - $earnings_tax);

            $balance = max(0, $balance + $net_contrib + $earnings_after_tax - $fees);

            $proj[] = [
                'age' => $age + $y + 1,
                'yearIndex' => $y + 1,
                'salary' => round($sal, 2),
                'balance' => round($balance, 2),
                'contribNet' => round($net_contrib, 2),
                'fees' => round($fees, 2),
                'earningsAfterTax' => round($earnings_after_tax, 2)
            ];

            $sal *= (1.0 + $salary_growth);
        }

        $balance_at_retire = $balance;
        $n_years = max(1, $longevity_age - $retire_age);
        $r_real = ((1.0 + $return_nominal) / (1.0 + $inflation)) - 1.0;

        if (abs($r_real) < 1e-9) {
            $income_real = $balance_at_retire / $n_years;
        } else {
            $income_real = $balance_at_retire * ($r_real / (1.0 - pow(1.0 + $r_real, -$n_years)));
        }

        $income_today_dollars = $income_real;
        $total_income_today = $income_today_dollars + $age_pension_pa;

        $chart_balance = array_map(function($row) {
            return [ $row['age'], $row['balance'] ];
        }, $proj);

        return [
            'projection' => $proj,
            'atRetirement' => [
                'balance' => round($balance_at_retire, 2),
                'age' => $retire_age,
            ],
            'income' => [
                'annualSuperIncomeToday' => round($income_today_dollars, 2),
                'annualAgePensionToday' => round($age_pension_pa, 2),
                'annualTotalIncomeToday' => round($total_income_today, 2),
                'assumedLongevityAge' => $longevity_age
            ],
            'chart' => [ 'balanceSeries' => $chart_balance ]
        ];
    }

    private function num($v, $min, $max) { $n = floatval($v); return max($min, min($max, $n)); }
    private function pct($v) { $n = floatval($v); if ($n > 1.0) $n = $n / 100.0; return max(0.0, min(1.0, $n)); }
    private function money($v) { return max(0.0, floatval($v)); }
}
