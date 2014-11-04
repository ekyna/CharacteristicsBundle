<?php

namespace Ekyna\Bundle\CharacteristicsBundle\Form\Type;

use Doctrine\ORM\EntityRepository;
use Ekyna\Component\Characteristics\Form\Type\ChoiceCharacteristicType as BaseType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class ChoiceCharacteristicType
 * @package Ekyna\Bundle\CharacteristicsBundle\Form\Type
 * @author Étienne Dauvergne <contact@ekyna.com>
 */
class ChoiceCharacteristicType extends BaseType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('choice', 'entity', array(
            'label' => false,
            'required' => false,
            'class' => 'Ekyna\Component\Characteristics\Entity\ChoiceCharacteristicValue',
            'property' => 'value',
            'add_route' => 'ekyna_characteristics_choice_admin_new',
            'add_route_params' => array('name' => $options['identifier']),
            'query_builder' => function(EntityRepository $er) use ($options) {
                return $er
                    ->createQueryBuilder('c')
                    ->where('c.identifier = :identifier')
                    ->setParameter('identifier', $options['identifier'])
                    ->orderBy('c.value', 'ASC');
            },
        ));
    }
} 