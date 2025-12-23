<?php
namespace App\Dto;

class MasterStudentPaymentInfo
{
    private string $studentName;
    private string $direction;
    private string $serviceName;
    private string $status;
    private ?string $contractNumber;

    public function __construct(
        string $studentName,
        string $direction,
        string $serviceName,
        string $status,
        ?string $contractNumber = null
    ) {
        $this->studentName = $studentName;
        $this->direction = $direction;
        $this->serviceName = $serviceName;
        $this->status = $status;
        $this->contractNumber = $contractNumber;
    }

    public function getStudentName(): string
    {
        return $this->studentName;
    }

    public function getDirection(): string
    {
        return $this->direction;
    }

    public function getServiceName(): string
    {
        return $this->serviceName;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getContractNumber(): ?string
    {
        return $this->contractNumber;
    }

    public function getStatusBadgeClass(): string
    {
        return match ($this->status) {
            'завершена' => 'success',
            'ожидает' => 'warning',
            default => 'secondary'
        };
    }

    public function getStatusText(): string
    {
        return match ($this->status) {
            'завершена' => 'Оплачено',
            'ожидает' => 'Ожидает оплаты',
            default => $this->status
        };
    }
}
