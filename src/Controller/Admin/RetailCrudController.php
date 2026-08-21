<?php

declare(strict_types=1);

namespace App\Retailing\Controller\Admin;

use App\Retailing\Entity\Retail\RetailEntity;
use App\Retailing\Service\Retail\RetailCategoryVocabularyService;
use App\Retailing\Service\Retail\RetailKindVocabularyService;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

final class RetailCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly RetailKindVocabularyService $kindVocabulary,
        private readonly RetailCategoryVocabularyService $categoryVocabulary,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return RetailEntity::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield ChoiceField::new('kind')->setChoices($this->kindVocabulary->choices());
        yield TextField::new('ownerType', 'Owner type');
        yield TextField::new('owner', 'Owner ID');
        yield TextField::new('catalogCode', 'Catalog')->hideOnForm();
        yield ChoiceField::new('typePath', 'Type')->setChoices($this->categoryVocabulary->choices());
        yield TextField::new('title');
        yield TextareaField::new('description')->hideOnIndex();
        yield IntegerField::new('amountMinor', 'Amount in minor units');
        yield TextField::new('currency');
        yield TextField::new('location');
        yield TextField::new('objectStatus', 'Status')->hideOnForm();
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof RetailEntity) {
            $this->normalizeCatalogSelection($entityInstance);
        }

        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof RetailEntity) {
            $this->normalizeCatalogSelection($entityInstance);
        }

        parent::updateEntity($entityManager, $entityInstance);
    }

    private function normalizeCatalogSelection(RetailEntity $retail): void
    {
        $typePath = $retail->getTypePath();
        if (null === $typePath || !$this->categoryVocabulary->contains($retail->getKind(), $typePath)) {
            throw new \DomainException('Choose a type for the selected listing type.');
        }

        $retail->setCatalogCode($retail->getKind()->catalogCode());
    }
}
