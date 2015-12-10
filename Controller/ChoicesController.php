<?php

namespace Ekyna\Bundle\CharacteristicsBundle\Controller;

use Doctrine\DBAL\DBALException;
use Ekyna\Bundle\CoreBundle\Controller\Controller;
use Ekyna\Bundle\CoreBundle\Modal\Modal;
use Ekyna\Component\Characteristics\Entity\ChoiceCharacteristicValue;
use Ekyna\Component\Characteristics\Form\Type\ChoiceCharacteristicValueType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Validator\Constraints;

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
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function homeAction()
    {
        $this->isGranted('VIEW');

        $this->appendBreadcrumb(
            'characteristics',
            'ekyna_characteristics.menu',
            'ekyna_characteristics_choice_admin_home'
        );

        $schemas = [];

        foreach ($this->getRegistry()->getSchemas() as $schema) {
            $definitions = [];
            foreach ($schema->getGroups() as $group) {
                foreach ($group->getDefinitions() as $definition) {
                    if ($definition->getType() == 'choice' && !array_key_exists($definition->getIdentifier(), $definitions)) {
                        $definitions[$definition->getIdentifier()] = $definition;
                    }
                }
            }
            if (count($definitions) > 0) {
                $schemas[] = [
                    'title' => $schema->getTitle(),
                    'definitions' => $definitions,
                ];
            }
        }

        return $this->render('EkynaCharacteristicsBundle:Choices:home.html.twig', [
            'schemas' => $schemas
        ]);
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

        $this->appendBreadcrumb(
            'characteristics',
            'ekyna_characteristics.menu',
            'ekyna_characteristics_choice_admin_home'
        );

        $choices = $this->getRepository()->findByDefinition($definition);

        return $this->render('EkynaCharacteristicsBundle:Choices:list.html.twig', [
            'definition' => $definition,
            'choices' => $choices,
        ]);
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

        $this->appendBreadcrumb(
            'characteristics',
            'ekyna_characteristics.menu',
            'ekyna_characteristics_choice_admin_home'
        );

        $isXhr = $request->isXmlHttpRequest();
        $modal = $this->createModal('new');

        $definition = $this->getRegistry()->getDefinitionByIdentifier($request->attributes->get('name'));

        $choiceValue = new ChoiceCharacteristicValue();
        $choiceValue->setIdentifier($definition->getIdentifier());

        $form = $this->createChoiceForm(
            $choiceValue,
            $this->generateUrl('ekyna_characteristics_choice_admin_new', array(
                'name' => $definition->getIdentifier(),
            )),
            $this->generateUrl('ekyna_characteristics_choice_admin_list', array(
                'name' => $definition->getIdentifier(),
            )),
            $isXhr
        );

        $form->handleRequest($request);
        if ($form->isValid()) {

            $em = $this->getDoctrine()->getManager();
            $em->persist($choiceValue);
            $em->flush();

            if ($isXhr) {
                $modal->setContent([
                    'id' => $choiceValue->getId(),
                    'name' => $choiceValue->getValue(),
                ]);
                return $this->get('ekyna_core.modal')->render($modal);
            }

            $this->addFlash('La resource a été créée avec succès.', 'success');

            if (null !== $redirectPath = $form->get('_redirect')->getData()) {
                return $this->redirect($redirectPath);
            }

            return $this->redirect($this->generateUrl('ekyna_characteristics_choice_admin_show', [
                'name' => $definition->getIdentifier(),
                'choiceId' => $choiceValue->getId(),
            ]));
        }

        if ($isXhr) {
            $modal
                ->setContent($form->createView())
                ->setVars(array(
                    'form_template' => 'EkynaCharacteristicsBundle:Choices:_form.html.twig',
                ))
            ;
            return $this->get('ekyna_core.modal')->render($modal);
        }

        return $this->render('EkynaCharacteristicsBundle:Choices:new.html.twig', [
            'definition' => $definition,
            'form' => $form->createView(),
        ]);
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

        $this->appendBreadcrumb(
            'characteristics',
            'ekyna_characteristics.menu',
            'ekyna_characteristics_choice_admin_home'
        );

        $definition = $this->getRegistry()->getDefinitionByIdentifier($request->attributes->get('name'));

        $choiceValue = $this->getRepository()->find($request->attributes->get('choiceId'));
        if(null === $choiceValue) {
            throw new NotFoundHttpException('Characteristic choice not found.');
        }

        return $this->render('EkynaCharacteristicsBundle:Choices:show.html.twig', [
            'definition' => $definition,
            'choice' => $choiceValue,
        ]);
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

        $this->appendBreadcrumb(
            'characteristics',
            'ekyna_characteristics.menu',
            'ekyna_characteristics_choice_admin_home'
        );

        $isXhr = $request->isXmlHttpRequest();
        $modal = $this->createModal('edit');

        $definition = $this->getRegistry()->getDefinitionByIdentifier($request->attributes->get('name'));

        /** @var ChoiceCharacteristicValue $choiceValue */
        $choiceValue = $this->getRepository()->find($request->attributes->get('choiceId'));
        if(null === $choiceValue) {
            throw new NotFoundHttpException('Characteristic choice not found.');
        }

        $form = $this->createChoiceForm(
            $choiceValue,
            $this->generateUrl('ekyna_characteristics_choice_admin_edit', array(
                'name' => $definition->getIdentifier(),
                'choiceId' => $choiceValue->getId(),
            )),
            $this->generateUrl('ekyna_characteristics_choice_admin_list', array(
                'name' => $definition->getIdentifier(),
            )),
            $isXhr
        );

        $form->handleRequest($request);
        if ($form->isValid()) {

            $em = $this->getDoctrine()->getManager();
            $em->persist($choiceValue);
            $em->flush();

            if ($isXhr) {
                $modal->setContent([
                    'id' => $choiceValue->getId(),
                    'name' => $choiceValue->getValue(),
                ]);
                return $this->get('ekyna_core.modal')->render($modal);
            }

            $this->addFlash('La resource a été modifiée avec succès.', 'success');

            if (null !== $redirectPath = $form->get('_redirect')->getData()) {
                return $this->redirect($redirectPath);
            }

            return $this->redirect($this->generateUrl('ekyna_characteristics_choice_admin_show', [
                'name' => $definition->getIdentifier(),
                'choiceId' => $choiceValue->getId(),
            ]));
        }

        if ($isXhr) {
            $modal
                ->setContent($form->createView())
                ->setVars(array(
                    'form_template' => 'EkynaCharacteristicsBundle:Choices:_form.html.twig',
                ))
            ;
            return $this->get('ekyna_core.modal')->render($modal);
        }

        return $this->render('EkynaCharacteristicsBundle:Choices:edit.html.twig', [
            'definition' => $definition,
            'form' => $form->createView(),
            'choice' => $choiceValue,
        ]);
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

        $this->appendBreadcrumb(
            'characteristics',
            'ekyna_characteristics.menu',
            'ekyna_characteristics_choice_admin_home'
        );

        $isXhr = $request->isXmlHttpRequest();
        $modal = $this->createModal('remove')->setSize(Modal::SIZE_NORMAL);

        $definition = $this->getRegistry()->getDefinitionByIdentifier($request->attributes->get('name'));

        $choiceValue = $this->getRepository()->find($request->attributes->get('choiceId'));
        if(null === $choiceValue) {
            throw new NotFoundHttpException('Characteristic choice not found.');
        }

        // TODO Warn user about ChoiceCharacteristics associations ?

        $form = $this
            ->createFormBuilder(null, [
                'action' => $this->generateUrl('ekyna_characteristics_choice_admin_remove', array(
                    'name' => $definition->getIdentifier(),
                    'choiceId' => $choiceValue->getId(),
                )),
                'attr' => ['class' => 'form-horizontal'],
                'method' => 'POST',
                'admin_mode' => true,
                '_redirect_enabled' => true,
            ])
            ->add('confirm', 'checkbox', [
                'label' => 'ekyna_core.message.remove_confirm',
                'attr' => ['align_with_widget' => true],
                'required' => true,
                'constraints' => [
                    new Constraints\IsTrue(),
                ]
            ])
            ->getForm()
        ;

        if (!$isXhr) {
            $cancelPath = $this->generateUrl('ekyna_characteristics_choice_admin_list', array(
                'name' => $definition->getIdentifier(),
            ));
            $form->add('actions', 'form_actions', [
                'buttons' => [
                    'remove' => [
                        'type' => 'submit',
                        'options' => [
                            'button_class' => 'danger',
                            'label' => 'ekyna_core.button.remove',
                            'attr' => ['icon' => 'trash'],
                        ],
                    ],
                    'cancel' => [
                        'type' => 'button',
                        'options' => [
                            'label' => 'ekyna_core.button.cancel',
                            'button_class' => 'default',
                            'as_link' => true,
                            'attr' => [
                                'class' => 'form-cancel-btn',
                                'icon' => 'remove',
                                'href' => $cancelPath,
                            ],
                        ],
                    ],
                ],
            ]);
        }

        $form->handleRequest($request);
        if ($form->isValid()) {
            $success = false;
            $em = $this->getDoctrine()->getManager();
            $em->remove($choiceValue);
            try {
                $em->flush();
                $success = true;
            } catch(DBALException $e) {
                $this->addFlash('ekyna_admin.resource.message.remove.integrity', 'danger');
            }

            if ($success) {
                if ($isXhr) {
                    $modal->setContent(['success' => true]);
                    return $this->get('ekyna_core.modal')->render($modal);
                }

                $this->addFlash('La resource a été supprimée avec succès.', 'success');

                return $this->redirect($this->generateUrl('ekyna_characteristics_choice_admin_list', [
                    'name' => $definition->getIdentifier(),
                ]));
            }
        }

        if ($isXhr) {
            $modal->setContent($form->createView());
            return $this->get('ekyna_core.modal')->render($modal);
        }

        return $this->render('EkynaCharacteristicsBundle:Choices:remove.html.twig', [
            'definition' => $definition,
            'choice' => $choiceValue,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Creates the choice value form.
     *
     * @param ChoiceCharacteristicValue $choiceValue
     * @param string $actionPath
     * @param string $cancelPath
     * @param bool|false                $isXhr
     *
     * @return \Symfony\Component\Form\Form
     */
    private function createChoiceForm(ChoiceCharacteristicValue $choiceValue, $actionPath, $cancelPath, $isXhr = false)
    {
        $form = $this
            ->createForm(new ChoiceCharacteristicValueType(), $choiceValue, [
                'action' => $actionPath,
                'method' => 'POST',
                'attr' => ['class' => 'form-horizontal'],
                'admin_mode' => true,
                '_redirect_enabled' => true,
            ])
            ->remove('value')
            ->add('value', 'text', [
                'label' => 'ekyna_characteristics.choice.field.value',
                'required' => true,
            ]);;

        if (!$isXhr) {
            $form->add('actions', 'form_actions', [
                'buttons' => [
                    'save' => [
                        'type' => 'submit',
                        'options' => [
                            'button_class' => 'primary',
                            'label' => 'ekyna_core.button.save',
                            'attr' => ['icon' => 'ok'],
                        ],
                    ],
                    'cancel' => [
                        'type' => 'button',
                        'options' => [
                            'label' => 'ekyna_core.button.cancel',
                            'button_class' => 'default',
                            'as_link' => true,
                            'attr' => [
                                'class' => 'form-cancel-btn',
                                'icon' => 'remove',
                                'href' => $cancelPath,
                            ],
                        ],
                    ],
                ],
            ]);
        }

        return $form;
    }

    /**
     * Creates a modal object.
     *
     * @param string $action
     * @return Modal
     */
    private function createModal($action)
    {
        $modal = new Modal(sprintf('ekyna_characteristics.choice.header.%s', $action, $action));

        $buttons = [];

        if (in_array($action, ['new', 'edit', 'remove'])) {
            $submitButton = [
                'id'       => 'submit',
                'label'    => 'ekyna_core.button.save',
                'icon'     => 'glyphicon glyphicon-ok',
                'cssClass' => 'btn-success',
                'autospin' => true,
            ];
            if ($action === 'edit') {
                $submitButton['icon'] = 'glyphicon glyphicon-ok';
                $submitButton['cssClass'] = 'btn-warning';
            } elseif ($action === 'remove') {
                $submitButton['label'] = 'ekyna_core.button.remove';
                $submitButton['icon'] = 'glyphicon glyphicon-trash';
                $submitButton['cssClass'] = 'btn-danger';
            }
            $buttons[] = $submitButton;
        }

        $buttons[] = [
            'id' => 'close',
            'label' => 'ekyna_core.button.cancel',
            'icon' => 'glyphicon glyphicon-remove',
            'cssClass' => 'btn-default',
        ];

        $modal->setButtons($buttons);

        return $modal;
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
        if (!$this->get('security.authorization_checker')->isGranted($attributes, $object)) {
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

    /**
     * Appends a link or span to the admin breadcrumb
     *
     * @param string $name
     * @param string $label
     * @param string $route
     *
     * @param array $parameters
     */
    protected function appendBreadcrumb($name, $label, $route = null, array $parameters = [])
    {
        $this->container->get('ekyna_admin.menu.builder')->breadcrumbAppend($name, $label, $route, $parameters);
    }
}
