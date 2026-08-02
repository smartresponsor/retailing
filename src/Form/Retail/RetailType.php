<?php

declare(strict_types=1);

namespace App\Retailing\Form\Retail;

use App\Retailing\Entity\Retail\RetailEntity;
use App\Retailing\Enum\Retail\RetailKind;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class RetailType extends AbstractType
{
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
            ->add('categoryId', TextType::class, [
                'label' => 'Category',
                'required' => false,
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
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => RetailEntity::class,
            'csrf_protection' => true,
        ]);
    }
}
