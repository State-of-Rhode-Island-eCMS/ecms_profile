<?php

declare(strict_types = 1);

namespace Drupal\ecms_blocks\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormBuilderInterface;

/**
 * Provides a listing of promos referenced by the node.
 *
 * @Block(
 *   id = "ecms_search_block",
 *   admin_label = @Translation("Search Block"),
 * )
 */
class SearchBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Form builder service.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('form_builder')
    );
  }

  /**
   * PromotionsNodeSpecificBlock constructor.
   *
   * @param array $configuration
   *   Configuration array for the block.
   * @param string $plugin_id
   *   The plugin id.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The Form builder service.
   */
  public function __construct(array $configuration, string $plugin_id, $plugin_definition, FormBuilderInterface $form_builder) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->formBuilder = $form_builder;
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {

    $output['form'] = $this->formBuilder->getForm('Drupal\ecms_blocks\Form\SearchBlockForm');
    return $output;

  }

}
