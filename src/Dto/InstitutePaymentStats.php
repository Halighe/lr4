<?php
namespace App\Dto;

class InstitutePaymentStats
{
    private string $direction;
    private int $paidCount;
    private int $unpaidCount;
    private int $totalCount;

    public function __construct(string $direction, int $paidCount, int $unpaidCount)
    {
        $this->direction = $direction;
        $this->paidCount = $paidCount;
        $this->unpaidCount = $unpaidCount;
        $this->totalCount = $paidCount + $unpaidCount;
    }

    public function getDirection(): string
    {
        return $this->direction;
    }

    public function getPaidCount(): int
    {
        return $this->paidCount;
    }

    public function getUnpaidCount(): int
    {
        return $this->unpaidCount;
    }

    public function getTotalCount(): int
    {
        return $this->totalCount;
    }

    public function getPaidPercentage(): float
    {
        if ($this->totalCount === 0) {
            return 0;
        }

        return round(($this->paidCount / $this->totalCount) * 100, 2);
    }

    public function getUnpaidPercentage(): float
    {
        if ($this->totalCount === 0) {
            return 0;
        }

        return round(($this->unpaidCount / $this->totalCount) * 100, 2);
    }
}
