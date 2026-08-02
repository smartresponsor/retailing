<?php

declare(strict_types=1);

namespace App\Retailing\Controller\Admin;

use App\Retailing\Entity\Retail\RetailEntity;
use App\Retailing\Enum\Retail\RetailKind;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

final class RetailCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return RetailEntity::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield ChoiceField::new('kind')->setChoices([
            'Task' => RetailKind::Task,
            'Service' => RetailKind::Service,
            'Product' => RetailKind::Goods,
            'Project' => RetailKind::Project,
        ]);
        yield TextField::new('owner', 'Owner vendor');
        yield TextField::new('categoryId', 'Category');
        yield TextField::new('title');
        yield TextareaField::new('description')->hideOnIndex();
        yield IntegerField::new('amountMinor', 'Amount in minor units');
        yield TextField::new('currency');
        yield TextField::new('location');
        yield TextField::new('objectStatus', 'Status')->hideOnForm();
    }
}
