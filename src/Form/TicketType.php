<?php

namespace App\Form;

use App\Entity\Ticket;
use App\Entity\Categorie;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class TicketType extends AbstractType
{

    private $authorizationChecker = null;

    public function __construct(AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->authorizationChecker = $authorizationChecker;
    }


    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('titre')
            ->add('message')
            ->add('categorie', EntityType::class, ['class' => Categorie::class, 'choice_label' => 'nom']);

        if ($this->authorizationChecker->isGranted('ROLE_SUPPORT') || $this->authorizationChecker->isGranted('ROLE_ADMIN')) {
            $builder->add('status', ChoiceType::class, [
                'required' => true,
                'multiple' => false,
                'expanded' => false,
                'choices'  => [
                    'Ouvert' => 'Ouvert',
                    'Fermé' => 'Fermé',
                ],
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Ticket::class,
        ]);
    }
}
