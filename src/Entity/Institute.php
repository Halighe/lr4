<?php

namespace App\Entity;

use App\Repository\InstituteRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: InstituteRepository::class)]
class Institute
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $direction = null;

    #[ORM\Column(length: 255)]
    private ?string $group_name = null;

    #[ORM\ManyToMany(targetEntity: Student::class, mappedBy: 'institutes')]
    private Collection $students;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDirection(): ?string
    {
        return $this->direction;
    }

    public function setDirection(string $direction): static
    {
        $this->direction = $direction;

        return $this;
    }

    public function getGroupName(): ?string
    {
        return $this->group_name;
    }

    public function setGroupName(string $group_name): static
    {
        $this->group_name = $group_name;

        return $this;
    }

    public function __construct()
    {
        $this->students = new ArrayCollection();
    }

    public function addStudent(Student $student): static
    {
        if (!$this->students->contains($student)) {
            $this->students->add($student);
            $student->addInstitute($this);
        }

        return $this;
    }

    public function removeStudent(Student $student): static
    {
        if ($this->students->removeElement($student)) {
            $student->removeInstitute($this);
        }

        return $this;
    }

    // Для отображения в выпадающих списках
    public function __toString(): string
    {
        return $this->getGroupName() . ' (' . $this->getDirection() . ')';
    }
}
