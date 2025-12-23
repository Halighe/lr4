<?php
namespace App\Controller\Admin;

use App\Entity\Student;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud; // Добавьте этот импорт
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;

class StudentCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Student::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Студент')
            ->setEntityLabelInPlural('Студенты')
            ->setPageTitle('index', 'Студенты')
            ->setPageTitle('new', 'Добавить студента')
            ->setPageTitle('edit', 'Редактировать студента')
            ->setSearchFields(['full_name']);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('full_name')
            ->add(EntityFilter::new('institutes', 'Институты'));
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_EDIT, Action::INDEX)
            ->add(Crud::PAGE_NEW, Action::INDEX);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('full_name', 'ФИО'),

            AssociationField::new('institutes', 'Институты/Группы')
                ->setFormTypeOption('multiple', true)
                ->setFormTypeOption('by_reference', false)
                ->formatValue(function ($value, $entity) {
                    if (method_exists($entity, 'getInstitutes') && !$entity->getInstitutes()->isEmpty()) {
                        $groupNames = [];
                        foreach ($entity->getInstitutes() as $institute) {
                            $groupNames[] = $institute->getGroupName();
                        }
                        return implode(', ', $groupNames);
                    }

                    return 'Нет института';
                })
                ->setCustomOption('renderAsBadges', true), // Опционально: отображать как бейджи
        ];
    }
}
