<?php

declare(strict_types=1);

namespace App\Retailing\Form;

use App\Retailing\Entity\Retail\RetailEntity;
use App\Retailing\Enum\RetailKind;
use App\Retailing\Service\RetailCategoryVocabularyService;
use App\Retailing\Service\RetailKindVocabularyService;
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
    public function __construct(
        private readonly RetailKindVocabularyService $kindVocabulary,
        private readonly RetailCategoryVocabularyService $categoryVocabulary,
    ) {}

    /** @param array<string, mixed> $options */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('kind', ChoiceType::class, [
                'label' => 'Listing type',
                'choices' => $this->kindVocabulary->choices(),
                'choice_value' => static fn(?RetailKind $kind): ?string => $kind?->value,
            ])
            ->add('categoryId', ChoiceType::class, [
                'label' => 'Category',
                'required' => true,
                'placeholder' => 'Choose a category',
                'choices' => $this->categoryVocabulary->choices(),
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

            $categoryId = $retail->getCategoryId();
            if (null === $categoryId) {
                return;
            }

            $retail->setCatalogCode($retail->getKind()->catalogCode());
            if (!$this->categoryVocabulary->contains($retail->getKind(), $categoryId)) {
                $event->getForm()->get('categoryId')->addError(new FormError('Choose a category for the selected listing type.'));
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

}
