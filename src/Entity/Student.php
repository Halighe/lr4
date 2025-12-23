<?php

namespace App\Entity;

use App\Repository\StudentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StudentRepository::class)]
class Student
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $full_name = null;

    /**
     * @var Collection<int, Institute>
     */
    #[ORM\ManyToMany(targetEntity: Institute::class)]
    #[ORM\JoinTable(name: 'student_institute')]
    private Collection $institutes;

    // #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    // #[ORM\JoinColumn(nullable: false)]
    // private ?User $user = null;

     #[ORM\OneToOne(targetEntity: User::class, inversedBy: 'student')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id')]
    private ?User $user = null;

    #[ORM\OneToMany(targetEntity: Payment::class, mappedBy: 'student')]
    private Collection $payments;

     public function __construct()
    {
        $this->payments = new ArrayCollection();
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    /**
     * @return Collection<int, Payment>
     */
    public function getPayments(): Collection
    {
        return $this->payments;
    }

    // Получить только ожидающие оплаты
    public function getPendingPayments(): Collection
    {
        return $this->payments->filter(
            fn(Payment $payment) => $payment->getStatus() === 'ожидает'
        );
    }

    /**
     * @return Collection<int, Institute>
     */
    public function getInstitutes(): Collection
    {
        return $this->institutes;
    }

    public function addInstitute(Institute $institute): static
    {
        if (!$this->institutes->contains($institute)) {
            $this->institutes->add($institute);
            $institute->addStudent($this);
        }

        return $this;
    }

    public function removeInstitute(Institute $institute): static
    {
        if ($this->institutes->removeElement($institute)) {
            $institute->removeStudent($this);
        }

        return $this;
    }

    // Метод для удобного отображения
    public function getInstituteNames(): string
    {
        if ($this->institutes->isEmpty()) {
            return 'Нет института';
        }

        $names = [];
        foreach ($this->institutes as $institute) {
            $names[] = $institute->getGroupName();
        }

        return implode(', ', $names);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFullName(): ?string
    {
        return $this->full_name;
    }

    public function setFullName(string $full_name): static
    {
        $this->full_name = $full_name;

        return $this;
    }

    /**
     * @return Collection<int, Institute>
     */
    public function getInstitute(): Collection
    {
        return $this->institute;
    }


}
