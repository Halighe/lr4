<?php

namespace App\Entity;

use App\Repository\PaymentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PaymentRepository::class)]
class Payment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Student::class, inversedBy: 'payments')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Student $student = null;

    #[ORM\ManyToOne(targetEntity: Service::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Service $service = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $summ = null;

    #[ORM\Column]
    private ?\DateTime $date = null;

    #[ORM\Column(length: 255)]
    private ?string $status = null;

    #[ORM\OneToOne(mappedBy: 'payment', targetEntity: PaymentCheck::class, cascade: ['persist', 'remove'])]
    private ?PaymentCheck $paymentCheck = null;
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSumm(): ?string
    {
        return $this->summ;
    }

    public function setSumm(string $summ): static
    {
        $this->summ = $summ;

        return $this;
    }

    public function getDate(): ?\DateTime
    {
        return $this->date;
    }

    public function setDate(\DateTime $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }
    public function getService(): ?Service
    {
        return $this->service;
    }

    public function setService(?Service $service): static
    {
        $this->service = $service;
        return $this;
    }

    public function getStudent(): ?Student
    {
        return $this->student;
    }

    public function setStudent(?Student $student): static
    {
        $this->student = $student;
        return $this;
    }

    public function getPaymentCheck(): ?PaymentCheck
    {
        return $this->paymentCheck;
    }

    public function setPaymentCheck(?PaymentCheck $paymentCheck): static
    {
        // unset the owning side of the relation if necessary
        if ($paymentCheck === null && $this->paymentCheck !== null) {
            $this->paymentCheck->setPayment(null);
        }

        // set the owning side of the relation if necessary
        if ($paymentCheck !== null && $paymentCheck->getPayment() !== $this) {
            $paymentCheck->setPayment($this);
        }

        $this->paymentCheck = $paymentCheck;

        return $this;
    }

    public function getAmount(): ?string  // Добавляем этот метод для совместимости
    {
        return $this->summ;
    }

    public function setAmount(string $amount): static
    {
        $this->summ = $amount;
        return $this;
    }
}
