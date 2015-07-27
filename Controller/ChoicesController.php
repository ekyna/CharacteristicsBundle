<?php

namespace Ekyna\Bundle\CharacteristicsBundle\Controller;

use Ekyna\Bundle\CoreBundle\Controller\Controller;
use Ekyna\Component\Characteristics\Entity\ChoiceCharacteristicValue;
use Ekyna\Component\Characteristics\Form\Type\ChoiceCharacteristicValueType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class ChoicesController
 * @package Ekyna\Bundle\CharacteristicsBundle\Controller
 * @author Étienne Dauvergne <contact@ekyna.com>
 */
class ChoicesController extends Controller
{
    /**
     * Home action.
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function homeAction(Request $request)
    {
        $this->isGranted('VIEW');

        // TODO admin breadcrumb

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

        return $this->render('EkynaCharacteristicsBundle:Choices:home.html.twig', array(
            'schemas' => $schemas
        ));
    }

    /**
     * List action.
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function listAction(Request $request)
    {
        $this->isGranted('VIEW');

        $definition = $this->getRegistry()->getDefinitionByIdentifier($request->attributes->get('name'));

        $choices = $this->getRepository()->findByDefinition($definition);

        return $this->render('EkynaCharacteristicsBundle:Choices:list.html.twig', array(
            'definition' => $definition,
            'choices' => $choices,
        ));
    }

    /**
     * New action.
     *
     * @param Request $request
     * @return JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function newAction(Request $request)
    {
        $this->isGranted('CREATE');

        $definition = $this->getRegistry()->getDefinitionByIdentifier($request->attributes->get('name'));

        $choiceValue = new ChoiceCharacteristicValue();
        $choiceValue->setIdentifier($definition->getIdentifier());

        $form = $this
            ->createForm(new ChoiceCharacteristicValueType(), $choiceValue, array(
                'admin_mode' => true,
                '_redirect_enabled' => true,
                /*'_footer' => array(
                    'cancel_path' => $this->generateUrl('ekyna_characteristics_choice_admin_list', array('name' => $definition->getIdentifier())),
                ),*/
            ))
            // TODO form_actions
        ;

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
                $this->addFlash('La resource a été créée avec succès.', 'success');
            }

            if (null !== $redirectPath = $form->get('_redirect')->getData()) {
                return $this->redirect($redirectPath);
            }

            return $this->redirect($this->generateUrl('ekyna_characteristics_choice_admin_show', array(
                'name' => $definition->getIdentifier(),
                'choiceId' => $choiceValue->getId(),
            )));
        } elseif ($request->getMethod() === 'POST' && $request->isXmlHttpRequest()) {
            return new JsonResponse(array('error' => $form->getErrors()));
        }

        $format = 'html';
        if ($request->isXmlHttpRequest()) {
            $format = 'xml';
        }

        return $this->render('EkynaCharacteristicsBundle:Choices:new.'.$format.'.twig', array(
            'definition' => $definition,
            'form' => $form->createView(),
        ));
    }

    /**
     * Show action.
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function showAction(Request $request)
    {
        $this->isGranted('VIEW');

        $definition = $this->getRegistry()->getDefinitionByIdentifier($request->attributes->get('name'));

        $choiceValue = $this->getRepository()->find($request->attributes->get('choiceId'));
        if(null === $choiceValue) {
            throw new NotFoundHttpException('Characteristic choice not found.');
        }

        return $this->render('EkynaCharacteristicsBundle:Choices:show.html.twig', array(
            'definition' => $definition,
            'choice' => $choiceValue,
        ));
    }

    /**
     * Edit action.
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function editAction(Request $request)
    {
        $this->isGranted('EDIT');

        $definition = $this->getRegistry()->getDefinitionByIdentifier($request->attributes->get('name'));

        $choiceValue = $this->getRepository()->find($request->attributes->get('choiceId'));
        if(null === $choiceValue) {
            throw new NotFoundHttpException('Characteristic choice not found.');
        }

        $form = $this
            ->createForm(new ChoiceCharacteristicValueType(), $choiceValue, array(
                'admin_mode' => true,
                '_redirect_enabled' => true,
                /*'_footer' => array(
                    'cancel_path' => $this->generateUrl('ekyna_characteristics_choice_admin_list', array('name' => $definition->getIdentifier())),
                ),*/
            ))
            // TODO form_actions
        ;

        $form->handleRequest($request);
        if ($form->isValid()) {

            $em = $this->getDoctrine()->getManager();
            $em->persist($choiceValue);
            $em->flush();

            $this->addFlash('La resource a été modifiée avec succès.', 'success');

            if (null !== $redirectPath = $form->get('_redirect')->getData()) {
                return $this->redirect($redirectPath);
            }

            return $this->redirect($this->generateUrl('ekyna_characteristics_choice_admin_show', array(
                'name' => $definition->getIdentifier(),
                'choiceId' => $choiceValue->getId(),
            )));
        }

        return $this->render('EkynaCharacteristicsBundle:Choices:edit.html.twig', array(
            'definition' => $definition,
            'form' => $form->createView(),
            'choice' => $choiceValue,
        ));
    }

    /**
     * Remove action.
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function removeAction(Request $request)
    {
        $this->isGranted('DELETE');

        $definition = $this->getRegistry()->getDefinitionByIdentifier($request->attributes->get('name'));

        $choiceValue = $this->getRepository()->find($request->attributes->get('choiceId'));
        if(null === $choiceValue) {
            throw new NotFoundHttpException('Characteristic choice not found.');
        }

        // TODO Warn user about ChoiceCharacteristics associations ?

        $builder = $this->createFormBuilder(null, array(
            'admin_mode' => true,
            '_redirect_enabled' => true,
            /*'_footer' => array(
                'cancel_path' => $this->generateUrl(
                    'ekyna_characteristics_choice_admin_show',
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
            ),*/
        ));

        $form = $builder
            ->add('confirm', 'checkbox', array(
                'label' => 'Confirmer la suppression ?',
                'attr' => array('align_with_widget' => true),
                'required' => true
            ))
            // TODO form_actions
            ->getForm()
        ;

        $form->handleRequest($request);
        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($choiceValue);
            $em->flush();

            $this->addFlash('La resource a été supprimée avec succès.', 'success');

            return $this->redirect($this->generateUrl('ekyna_characteristics_choice_admin_list', array(
                'name' => $definition->getIdentifier(),
            )));
        }

        return $this->render('EkynaCharacteristicsBundle:Choices:remove.html.twig', array(
            'definition' => $definition,
            'choice' => $choiceValue,
            'form' => $form->createView(),
        ));
    }

    /**
     * Returns the schema registry.
     *
     * @return \Ekyna\Component\Characteristics\Schema\SchemaRegistry
     */
    private function getRegistry()
    {
        return $this->get('ekyna_characteristics.schema_registry');
    }

    /**
     * Returns the choice characteristic value repository.
     *
     * @return \Ekyna\Component\Characteristics\Entity\ChoiceCharacteristicValueRepository
     */
    private function getRepository()
    {
        return $this->getDoctrine()->getRepository('Ekyna\Component\Characteristics\Entity\ChoiceCharacteristicValue');
    }

    /**
     * Checks if the attributes are granted against the current token.
     *
     * @param mixed $attributes
     * @param mixed|null $object
     * @param bool $throwException
     *
     * @throws AccessDeniedHttpException when the security context has no authentication token.
     *
     * @return bool
     */
    protected function isGranted($attributes, $object = null, $throwException = true)
    {
        if (is_null($object)) {
            $object = $this->getConfiguration()->getObjectIdentity();
        } else {
            $object = $this->get('ekyna_admin.pool_registry')->getObjectIdentity($object);
        }
        if (!$this->get('security.context')->isGranted($attributes, $object)) {
            if ($throwException) {
                throw new AccessDeniedHttpException('You are not allowed to view this resource.');
            }
            return false;
        }
        return true;
    }

    /**
     * Returns the configuration.
     *
     * @return \Ekyna\Bundle\AdminBundle\Pool\ConfigurationInterface
     */
    private function getConfiguration()
    {
        return $this->get('ekyna_characteristics.choice.configuration');
    }
}
