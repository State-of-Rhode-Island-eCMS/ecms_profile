<?php

declare(strict_types=1);

namespace Drupal\ecms_api_recipient\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\RoleInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Setup the configuration form for the ecms_api_recipient module.
 *
 * @package Drupal\ecms_api_recipient\Form
 */
class EcmsApiRecipientConfigForm extends ConfigFormBase {

  /**
   * The role id to assign to the oauth user account.
   */
  const RECIPIENT_ROLE = 'ecms_api_recipient';

  /**
   * The publish action for the editorial workflow.
   */
  const EDITORIAL_PUBLISH = 'use editorial transition publish';

  /**
   * EcmsApiRecipientConfigForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config.factory service.
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typedConfigManager
   *   The typed config manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity_type.manager service.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entityTypeBundleInfo
   *   The entity_type.bundle.info service.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    TypedConfigManagerInterface $typedConfigManager,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected EntityTypeBundleInfoInterface $entityTypeBundleInfo,
  ) {
    parent::__construct($config_factory, $typedConfigManager);
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('config.typed'),
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * {@inheritDoc}
   */
  public function getEditableConfigNames(): array {
    return [
      'ecms_api_recipient.settings',
    ];
  }

  /**
   * {@inheritDoc}
   */
  public function getFormId(): string {
    return 'ecms_api_recipient_settings_form';
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildForm($form, $form_state);

    // Load the available nodes.
    $nodes = $this->getEntityBundles();

    // List of all available content types as checkboxes.
    $form['allowed_content_types'] = [
      '#title' => $this->t('Content types'),
      '#description' => $this->t('Select the content types that are allowed to receive syndicated content with the eCMS API.'),
      '#type' => 'checkboxes',
      '#options' => $nodes,
      '#default_value' => $this->config('ecms_api_recipient.settings')->get('allowed_content_types'),
    ];

    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    // Get the allowed content types from the user.
    $contentTypes = $form_state->getValue('allowed_content_types');

    // Filter the array.
    $contentTypes = array_filter($contentTypes);

    // Save the allowed content types to the configuration object.
    $this->config('ecms_api_recipient.settings')
      ->set('allowed_content_types', $contentTypes)
      ->save();

    parent::submitForm($form, $form_state);

    // Save the permissions of the selected content types to the api role.
    $this->setRecipientRolePermissions($contentTypes);
  }

  /**
   * Set the correct API permissions to the role.
   *
   * @param array $contentTypes
   *   Array of content types selected on the configuration form.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  private function setRecipientRolePermissions(array $contentTypes): void {
    // Load the role entity.
    $role = $this->getRole();

    // Guard against a null entity.
    if (empty($role)) {
      return;
    }

    $allBundles = $this->getEntityBundles();
    $notEnabled = array_diff_key($allBundles, $contentTypes);

    // Revoke permissions for the non-enabled bundles.
    foreach ($notEnabled as $bundle => $label) {
      $role->revokePermission("create {$bundle} content");
      $role->revokePermission("edit own {$bundle} content");
      $role->revokePermission("translate {$bundle} node");
    }

    // Grant create and edit own permissions for the selected content types.
    foreach ($contentTypes as $key => $type) {
      $role->grantPermission("create {$key} content");
      $role->grantPermission("edit own {$key} content");
      $role->grantPermission("translate {$key} node");
    }

    // Grant the publishing permission.
    $role->grantPermission(self::EDITORIAL_PUBLISH);

    // Allow the api user translation permissions.
    $role->grantPermission('create content translations');

    try {
      $role->save();
    }
    catch (EntityStorageException $e) {
      return;
    }
  }

  /**
   * Load the user role entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The role entity or null if it doesn't exist.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function getRole(): ?RoleInterface {
    return $this->entityTypeManager
      ->getStorage('user_role')
      ->load(self::RECIPIENT_ROLE);
  }

  /**
   * Get the available entity bundles.
   *
   * @return array
   *   Array of entity bundles.
   */
  private function getEntityBundles(): array {
    $nodes = $this->entityTypeBundleInfo->getBundleInfo('node');
    return array_map(function ($bundle_info) {
      return $bundle_info['label'];
    }, $nodes);
  }

}
