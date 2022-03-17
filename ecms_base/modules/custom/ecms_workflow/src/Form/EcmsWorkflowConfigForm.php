<?php

declare(strict_types =1);

namespace Drupal\ecms_workflow\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\ecms_workflow\EcmsWorkflowBundleCreate;

/**
 * Setup the configuration form for the ecms_workflow module.
 *
 * @package Drupal\ecms_workflow\Form
 */
class EcmsWorkflowConfigForm extends ConfigFormBase {

  /**
   * The entity_type.manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * The entity_type.bundle.info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  private $entityTypeBundleInfo;

  /**
   * The ecms_workflow.bundle_create service.
   *
   * @var \Drupal\ecms_workflow\EcmsWorkflowBundleCreate
   */
  private $ecmsWorkflowBundleCreate;

  /**
   * EcmsWorkflowConfigForm constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity_type.manager service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config.factory service.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entityTypeBundleInfo
   *   The entity_type.bundle.info service.
   * @param \Drupal\ecms_workflow\EcmsWorkflowBundleCreate $ecmsWorkflowBundleCreateService
   *   The ecms_workflow.bundle_create service.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, ConfigFactoryInterface $config_factory, EntityTypeBundleInfoInterface $entityTypeBundleInfo, EcmsWorkflowBundleCreate $ecmsWorkflowBundleCreateService) {
    parent::__construct($config_factory);

    $this->entityTypeManager = $entityTypeManager;
    $this->entityTypeBundleInfo = $entityTypeBundleInfo;
    $this->ecmsWorkflowBundleCreate = $ecmsWorkflowBundleCreateService;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('config.factory'),
      $container->get('entity_type.bundle.info'),
      $container->get('ecms_workflow.bundle_create')
    );
  }

  /**
   * {@inheritDoc}
   */
  public function getEditableConfigNames(): array {
    return [
      'ecms_workflow.settings',
    ];
  }

  /**
   * {@inheritDoc}
   */
  public function getFormId(): string {
    return 'ecms_workflow_settings_form';
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildForm($form, $form_state);

    // Load the available nodes and build list of types.
    $nodeTypes = node_type_get_names();

    // List of all available content types as checkboxes.
    $form['excluded_content_types'] = [
      '#title' => $this->t('Excluded Content types'),
      '#description' => $this->t('Select the content types to exclude from the default workflow.'),
      '#type' => 'checkboxes',
      '#options' => $nodeTypes,
      '#default_value' => $this->config('ecms_workflow.settings')->get('excluded_content_types'),
    ];

    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    // Get the excluded content types from the user.
    $contentTypes = $form_state->getValue('excluded_content_types');

    // Filter the array.
    $contentTypes = array_filter($contentTypes);

    // Save the allowed content types to the configuration object.
    $this->config('ecms_workflow.settings')
      ->set('excluded_content_types', $contentTypes)
      ->save();

    parent::submitForm($form, $form_state);

    // Remove the selected content types from the workflow.
    $this->removeTypesFromWorkflow($contentTypes);
  }

  /**
   * Removes the selected types from the default workflow.
   *
   * @param array $contentTypes
   *   Array of content types selected on the configuration form.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  private function removeTypesFromWorkflow(array $contentTypes): void {

    foreach ($contentTypes as $key => $type) {
      $this->ecmsWorkflowBundleCreate->removeContentTypeFromWorkflow($key);
    }

    // Make sure all unchecked types are in the workflow.
    foreach (node_type_get_names() as $key => $type) {
      if (!array_key_exists($key, $contentTypes)) {
        $this->ecmsWorkflowBundleCreate->addContentTypeToWorkflow($key, TRUE);
      }
    }

  }

}
