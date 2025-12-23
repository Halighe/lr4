<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[UniqueEntity(fields: ['email'], message: 'There is already an account with this email')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    #[ORM\OneToOne(targetEntity: Student::class, mappedBy: 'user', cascade: ['persist', 'remove'])]
    private ?Student $student = null;

    #[ORM\OneToOne(mappedBy: 'user', targetEntity: Employee::class)]
    private ?Employee $employee = null;

    public function getStudent(): ?Student
    {
        return $this->student;
    }

    public function setStudent(?Student $student): static
    {
        // unset the owning side of the relation if necessary
        if ($student === null && $this->student !== null) {
            $this->student->setUser(null);
        }

        // set the owning side of the relation if necessary
        if ($student !== null && $student->getUser() !== $this) {
            $student->setUser($this);
        }

        $this->student = $student;

        return $this;
    }

    // Проверка роли студента
    public function isStudent(): bool
    {
        return in_array('ROLE_STUDENT', $this->getRoles(), true);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function __construct()
    {
        $this->roles = ['ROLE_STUDENT']; // Устанавливаем роль по умолчанию
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Ensure the session doesn't contain actual password hashes by CRC32C-hashing them, as supported since Symfony 7.3.
     */
    public function __serialize(): array
    {
        $data = (array) $this;
        $data["\0" . self::class . "\0password"] = hash('crc32c', $this->password);

        return $data;
    }

    public function getEmployee(): ?Employee
    {
        return $this->employee;
    }

    public function setEmployee(?Employee $employee): static
    {
        // Убираем связь с предыдущим Employee
        if ($this->employee !== null && $this->employee->getUser() === $this) {
            $this->employee->setUser(null);
        }

        // Устанавливаем новую связь
        $this->employee = $employee;

        if ($employee !== null && $employee->getUser() !== $this) {
            $employee->setUser($this);
        }

        return $this;
    }

    public function getFullName(): ?string
    {
        if ($this->employee && $this->employee->getFullName()) {
            return $this->employee->getFullName();
        }

        if ($this->getStudent() && $this->getStudent()->getFullName()) {
            return $this->getStudent()->getFullName();
        }

        return $this->getEmail();
    }

    #[\Deprecated]
    public function eraseCredentials(): void
    {
        // @deprecated, to be removed when upgrading to Symfony 8
    }
}
