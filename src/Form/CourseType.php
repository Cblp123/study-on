<?php

namespace App\Form;

use App\Entity\Course;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CourseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('code', TextType::class, [
                'label' => 'Код курса',
                'attr' => ['maxlength' => 255],
            ])
            ->add('name', TextType::class, [
                'label' => 'Название курса',
                'attr' => ['maxlength' => 255]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Описание курса',
                'attr' => ['maxlength' => 1000],
                'required' => false
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Тип курса',
                'mapped' => false,
                'choices' => [
                    'Бесплатный' => 'free',
                    'Аренда' => 'rent',
                    'Покупка' => 'buy',
                ],
                'data' => $options['billing_type'],
                'required' => true,
            ])
            ->add('price', MoneyType::class, [
                'label' => 'Стоимость',
                'mapped' => false,
                'currency' => 'RUB',
                'required' => false,
                'data' => $options['billing_price'] ? (float) $options['billing_price'] : null,
            ])
        ;
    }

public function configureOptions(OptionsResolver $resolver): void
{
    $resolver->setDefaults([
        'data_class'    => Course::class,
        'billing_type'  => null,
        'billing_price' => null,
    ]);
}
}
