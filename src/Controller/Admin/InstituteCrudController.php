<?php
namespace App\Controller\Admin;

use App\Entity\Institute;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud; // Добавьте этот импорт
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;

class InstituteCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Institute::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Институт')
            ->setEntityLabelInPlural('Институты')
            ->setPageTitle('index', 'Институты')
            ->setPageTitle('new', 'Добавить институт')
            ->setPageTitle('edit', 'Редактировать институт');
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('direction', 'Направление'),
            TextField::new('groupName', 'Название группы'),

            // Опционально: показать студентов в этом институте
            AssociationField::new('students', 'Студенты')
                ->onlyOnDetail() // показывать только на странице деталей
                ->formatValue(function ($value, $entity) {
                    if (method_exists($entity, 'getStudents') && !$entity->getStudents()->isEmpty()) {
                        $studentNames = [];
                        foreach ($entity->getStudents() as $student) {
                            $studentNames[] = $student->getFullName();
                        }
                        return implode(', ', $studentNames);
                    }

                    return 'Нет студентов';
                }),
        ];
    }
}
