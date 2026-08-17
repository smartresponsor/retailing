<?php

declare(strict_types=1);

namespace App\Retailing\Form\Retail;

use App\Cataloging\ServiceInterface\CatalogCatalogTreeReadServiceInterface;
use App\Retailing\Entity\Retail\RetailEntity;
use App\Retailing\Enum\Retail\RetailKind;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class RetailType extends AbstractType
{
    public function __construct(private readonly CatalogCatalogTreeReadServiceInterface $catalogTreeReadService)
    {
    }

    /** @param array<string, mixed> $options */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('kind', ChoiceType::class, [
                'label' => 'Listing type',
                'choices' => [
                    'Task' => RetailKind::Task,
                    'Service' => RetailKind::Service,
                    'Product' => RetailKind::Goods,
                    'Project' => RetailKind::Project,
                ],
                'choice_value' => static fn (?RetailKind $kind): ?string => $kind?->value,
            ])
            ->add('catalogCode', ChoiceType::class, [
                'label' => 'Catalog',
                'required' => true,
                'choices' => [
                    'Services' => 'services',
                    'Products' => 'products',
                    'Projects' => 'projects',
                ],
            ])
            ->add('categoryId', ChoiceType::class, [
                'label' => 'Category',
                'required' => true,
                'placeholder' => 'Choose a category',
                'choices' => $this->categoryChoices(),
            ])
            ->add('title', TextType::class)
            ->add('description', TextareaType::class, [
                'required' => false,
            ])
            ->add('amountMinor', IntegerType::class, [
                'label' => 'Amount in minor units',
                'required' => false,
            ])
            ->add('currency', TextType::class)
            ->add('location', TextType::class, [
                'required' => false,
            ]);

        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event): void {
            $retail = $event->getData();
            if (!$retail instanceof RetailEntity) {
                return;
            }

            $catalogCode = $retail->getCatalogCode();
            $categoryId = $retail->getCategoryId();
            if (null === $catalogCode || null === $categoryId) {
                return;
            }

            if (!$this->catalogContainsCategory($catalogCode, $categoryId)) {
                $event->getForm()->get('categoryId')->addError(new FormError('Choose a category from the selected catalog.'));
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => RetailEntity::class,
            'csrf_protection' => true,
        ]);
    }

    /** @return array<string, array<string, string>> */
    private function categoryChoices(): array
    {
        $choices = [];
        foreach ([
            'services' => 'Services',
            'products' => 'Products',
            'projects' => 'Projects',
        ] as $code => $label) {
            $tree = $this->catalogTreeReadService->byCode($code);
            $nodes = $tree['nodes'] ?? null;
            if (!is_array($nodes)) {
                continue;
            }

            $choices[$label] = $this->flattenCategoryChoices($nodes);
        }

        return $choices;
    }

    /**
     * @param array<int, mixed> $nodes
     *
     * @return array<string, string>
     */
    private function flattenCategoryChoices(array $nodes, string $prefix = ''): array
    {
        $choices = [];
        foreach ($nodes as $node) {
            if (!is_array($node)) {
                continue;
            }

            $id = isset($node['nodeId']) && is_scalar($node['nodeId']) ? trim((string) $node['nodeId']) : '';
            $title = isset($node['title']) && is_scalar($node['title']) ? trim((string) $node['title']) : '';
            if ('' === $id || '' === $title) {
                continue;
            }

            $choiceLabel = '' === $prefix ? $title : $prefix.' › '.$title;
            $choices[$choiceLabel] = $id;
            $children = $node['children'] ?? null;
            if (is_array($children)) {
                $choices += $this->flattenCategoryChoices($children, $choiceLabel);
            }
        }

        return $choices;
    }

    private function catalogContainsCategory(string $catalogCode, string $categoryId): bool
    {
        $tree = $this->catalogTreeReadService->byCode($catalogCode);
        $nodes = $tree['nodes'] ?? null;

        return is_array($nodes) && in_array($categoryId, array_values($this->flattenCategoryChoices($nodes)), true);
    }
}
