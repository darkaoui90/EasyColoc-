<?php

namespace App\Services;

use App\Models\Colocation;

class ColocationBalanceService
{
    /**
     * @return array{
     *     balances: array<int, float>,
     *     settlements: array<int, array{from_user_id:int,to_user_id:int,amount:float}>
     * }
     */
    public function compute(Colocation $colocation): array
    {
        $activeMemberIds = $colocation->members()
            ->wherePivotNull('left_at')
            ->pluck('users.id')
            ->all();

        if (count($activeMemberIds) === 0) {
            return [
                'balances' => [],
                'settlements' => [],
            ];
        }

        $memberCount = count($activeMemberIds);
        $balances = [];

        foreach ($activeMemberIds as $memberId) {
            $balances[(int) $memberId] = 0.0;
        }

        $expenses = $colocation->expenses()
            ->whereIn('payer_id', $activeMemberIds)
            ->get(['payer_id', 'amount']);

        $totalExpenses = (float) $expenses->sum('amount');
        $individualShare = $memberCount > 0 ? $totalExpenses / $memberCount : 0.0;

        foreach ($activeMemberIds as $memberId) {
            $paid = (float) $expenses
                ->where('payer_id', $memberId)
                ->sum('amount');

            $balances[(int) $memberId] = $this->roundMoney($paid - $individualShare);
        }

        // Paid settlements reduce outstanding balances.
        $paidSettlements = $colocation->settlements()
            ->whereNotNull('paid_at')
            ->whereIn('from_user_id', $activeMemberIds)
            ->whereIn('to_user_id', $activeMemberIds)
            ->get(['from_user_id', 'to_user_id', 'amount']);

        foreach ($paidSettlements as $settlement) {
            $fromId = (int) $settlement->from_user_id;
            $toId = (int) $settlement->to_user_id;
            $amount = (float) $settlement->amount;

            $balances[$fromId] = $this->roundMoney($balances[$fromId] + $amount);
            $balances[$toId] = $this->roundMoney($balances[$toId] - $amount);
        }

        return [
            'balances' => $balances,
            'settlements' => $this->buildSimplifiedSettlements($balances),
        ];
    }

    /**
     * @param  array<int, float>  $balances
     * @return array<int, array{from_user_id:int,to_user_id:int,amount:float}>
     */
    private function buildSimplifiedSettlements(array $balances): array
    {
        $creditors = [];
        $debtors = [];

        foreach ($balances as $userId => $balance) {
            if ($balance > 0.009) {
                $creditors[] = [
                    'user_id' => $userId,
                    'amount' => $balance,
                ];
            } elseif ($balance < -0.009) {
                $debtors[] = [
                    'user_id' => $userId,
                    'amount' => abs($balance),
                ];
            }
        }

        $settlements = [];
        $i = 0;
        $j = 0;

        while (isset($debtors[$i]) && isset($creditors[$j])) {
            $amount = min($debtors[$i]['amount'], $creditors[$j]['amount']);
            $amount = $this->roundMoney($amount);

            if ($amount <= 0.009) {
                break;
            }

            $settlements[] = [
                'from_user_id' => $debtors[$i]['user_id'],
                'to_user_id' => $creditors[$j]['user_id'],
                'amount' => $amount,
            ];

            $debtors[$i]['amount'] = $this->roundMoney($debtors[$i]['amount'] - $amount);
            $creditors[$j]['amount'] = $this->roundMoney($creditors[$j]['amount'] - $amount);

            if ($debtors[$i]['amount'] <= 0.009) {
                $i++;
            }

            if ($creditors[$j]['amount'] <= 0.009) {
                $j++;
            }
        }

        return $settlements;
    }

    private function roundMoney(float $value): float
    {
        return round($value, 2);
    }
}
