<?php

namespace Ekyna\Bundle\CharacteristicsBundle\Controller;

use Ekyna\Component\Characteristics\Entity\ChoiceCharacteristicValue;
use Ekyna\Component\Characteristics\Form\Type\ChoiceCharacteristicValueType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class ChoicesController
 * @package Ekyna\Bundle\CharacteristicsBundle\Controller
 */
class ChoicesController extends Controller
{
    public function homeAction(Request $request)
    {
        $schemas = array();

        foreach ($this->getRegistry()->getSchemas() as $schema) {
            $definitions = array();
            foreach ($schema->getGroups() as $group) {
                foreach ($group->getDefinitions() as $definition) {
                    if ($definition->getType() == 'choice' && !array_key_exists($definition->getIdentifier(), $definitions)) {
                        $definitions[$definition->getIdentifier()] = $definition;
                    }
                }
            }
            if (count($definitions) > 0) {
                $schemas[] = array(
                    'title' => $schema->getTitle(),
                    'definitions' => $definitions,
                );
            }
        }

        return $this->render(
            'EkynaCharacteristicsBundle:Choices:home.html.twig',
            array(
                'schemas' => $schemas
            )
        );
    }

    public function listAction(Request $request)
    {
        $definition = $this->getRegistry()->getDefinitionByIdentifier($request->attributes->get('name'));

        $choices = $this->getRepository()->findByDefinition($definition);

        return $this->render(
            'EkynaCharacteristicsBundle:Choices:list.html.twig',
            array(
                'definition' => $definition,
                'choices' => $choices,
            )
        );
    }

    public function newAction(Request $request)
    {
        $definition = $this->getRegistry()->getDefinitionByIdentifier($request->attributes->get('name'));

        $choiceValue = new ChoiceCharacteristicValue();
        $choiceValue->setIdentifier($definition->getIdentifier());

        $form = $this->createForm(new ChoiceCharacteristicValueType(), $choiceValue, array(
            'admin_mode' => true,
            '_redirect_enabled' => true,
            '_footer' => array(
                'cancel_path' => $this->generateUrl('ekyna_characteristics_admin_list', array('name' => $definition->getIdentifier())),
            ),
        ));

        $form->handleRequest($request);
        if ($form->isValid()) {

            $em = $this->getDoctrine()->getManager();
            $em->persist($choiceValue);
            $em->flush();

            if ($request->isXmlHttpRequest()) {
                return new JsonResponse(array(
                    'id' => $choiceValue->getId(),
                    'name' => $choiceValue->getValue(),
                ));
            } else {
                $this->get('session')->getFlashBag()->add('success', 'La resource a été créée avec succès.');
            }

            if (null !== $redirectPath = $form->get('_redirect')->getData()) {
                return $this->redirect($redirectPath);
            }

            return $this->redirect(
                $this->generateUrl(
                    'ekyna_characteristics_admin_show',
                    array(
                        'name' => $definition->getIdentifier(),
                        'choiceId' => $choiceValue->getId(),
                    )
                )
            );
        } elseif ($request->getMethod() === 'POST' && $request->isXmlHttpRequest()) {
            return new JsonResponse(array('error' => $form->getErrors()));
        }

        $format = 'html';
        if ($request->isXmlHttpRequest()) {
            $format = 'xml';
        }

        return $this->render(
            'EkynaCharacteristicsBundle:Choices:new.'.$format.'.twig',
            array(
                'definition' => $definition,
                'form' => $form->createView(),
            )
        );
    }

    public function showAction(Request $request)
    {
        $definition = $this->getRegistry()->getDefinitionByIdentifier($request->attributes->get('name'));

        $choiceValue = $this->getRepository()->find($request->attributes->get('choiceId'));
        if(null === $choiceValue) {
            throw new NotFoundHttpException('Characteristic choice not found.');
        }

        return $this->render(
            'EkynaCharacteristicsBundle:Choices:show.html.twig',
            array(
                'definition' => $definition,
                'choice' => $choiceValue,
            )
        );
    }

    public function editAction(Request $request)
    {
        $definition = $this->getRegistry()->getDefinitionByIdentifier($request->attributes->get('name'));

        $choiceValue = $this->getRepository()->find($request->attributes->get('choiceId'));
        if(null === $choiceValue) {
            throw new NotFoundHttpException('Characteristic choice not found.');
        }

        $form = $this->createForm(new ChoiceCharacteristicValueType(), $choiceValue, array(
            'admin_mode' => true,
            '_redirect_enabled' => true,
            '_footer' => array(
                'cancel_path' => $this->generateUrl('ekyna_characteristics_admin_list', array('name' => $definition->getIdentifier())),
            ),
        ));

        $form->handleRequest($request);
        if ($form->isValid()) {

            $em = $this->getDoctrine()->getManager();
            $em->persist($choiceValue);
            $em->flush();

            $this->get('session')->getFlashBag()->add('success', 'La resource a été modifiée avec succès.');

            if (null !== $redirectPath = $form->get('_redirect')->getData()) {
                return $this->redirect($redirectPath);
            }

            return $this->redirect(
                $this->generateUrl(
                    'ekyna_characteristics_admin_show',
                    array(
                        'name' => $definition->getIdentifier(),
                        'choiceId' => $choiceValue->getId(),
                    )
                )
            );
        }

        return $this->render(
            'EkynaCharacteristicsBundle:Choices:edit.html.twig',
            array(
                'definition' => $definition,
                'form' => $form->createView(),
                'choice' => $choiceValue,
            )
        );
    }

    public function removeAction(Request $request)
    {
        $definition = $this->getRegistry()->getDefinitionByIdentifier($request->attributes->get('name'));

        $choiceValue = $this->getRepository()->find($request->attributes->get('choiceId'));
        if(null === $choiceValue) {
            throw new NotFoundHttpException('Characteristic choice not found.');
        }

        // TODO Warn user about ChoiceCharacteristics associations ?

        $builder = $this->createFormBuilder(null, array(
            'admin_mode' => true,
            '_redirect_enabled' => true,
            '_footer' => array(
                'cancel_path' => $this->generateUrl(
                    'ekyna_characteristics_admin_show',
                    array(
                        'name' => $definition->getIdentifier(),
                        'choiceId' => $choiceValue->getId(),
                    )
                ),
                'buttons' => array(
                    'submit' => array(
                        'theme' => 'danger',
                        'icon'  => 'trash',
                        'label' => 'ekyna_core.button.remove',
                    )
                )
            ),
        ));

        $form = $builder
            ->add('confirm', 'checkbox', array(
                'label' => 'Confirmer la suppression ?',
                'attr' => array('align_with_widget' => true),
                'required' => true
            ))
            ->getForm()
        ;

        $form->handleRequest($request);
        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($choiceValue);
            $em->flush();

            $this->get('session')->getFlashBag()->add('success', 'La resource a été supprimée avec succès.');

            return $this->redirect(
                $this->generateUrl(
                    'ekyna_characteristics_admin_list',
                    array(
                        'name' => $definition->getIdentifier(),
                    )
                )
            );
        }

        return $this->render(
            'EkynaCharacteristicsBundle:Choices:remove.html.twig',
            array(
                'definition' => $definition,
                'choice' => $choiceValue,
                'form' => $form->createView(),
            )
        );
    }

    /**
     * @return \Ekyna\Component\Characteristics\Schema\SchemaRegistry
     */
    private function getRegistry()
    {
        return $this->get('ekyna_characteristics.schema_registry');
    }

    private function getRepository()
    {
        return $this->getDoctrine()
            ->getRepository('Ekyna\Component\Characteristics\Entity\ChoiceCharacteristicValue');
    }
}