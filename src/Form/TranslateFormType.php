<?php

declare(strict_types=1);

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

final class TranslateFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('originalText', TextareaType::class, [
                'label' => 'Verwaltungstext',
                'attr' => [
                    'rows' => 12,
                    'placeholder' => 'Hier den Beamtendeutsch-Text einfügen…',
                    'spellcheck' => 'true',
                ],
                'constraints' => [
                    new Assert\NotBlank(message: 'Bitte einen Text zum Übersetzen eingeben.'),
                    new Assert\Length(min: 5, max: 8000),
                ],
            ])
            ->add('temperature', NumberType::class, [
                'label' => 'Temperatur',
                'scale' => 2,
                'html5' => true,
                'attr' => ['min' => 0.0, 'max' => 1.5, 'step' => 0.05],
                'constraints' => [
                    new Assert\Range(min: 0.0, max: 1.5),
                ],
            ])
            ->add('maxTokens', IntegerType::class, [
                'label' => 'Maximale Tokens',
                'attr' => ['min' => 64, 'max' => 8192, 'step' => 64],
                'constraints' => [
                    new Assert\Range(min: 64, max: 8192),
                ],
            ])
            ->add('qualityCheck', CheckboxType::class, [
                'label' => 'Qualitätscheck durchführen',
                'required' => false,
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'In Leichte Sprache übersetzen',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
        ]);
    }
}
