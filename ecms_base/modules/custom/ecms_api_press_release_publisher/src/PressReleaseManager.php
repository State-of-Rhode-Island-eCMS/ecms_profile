<?php

declare(strict_types=1);

namespace Drupal\ecms_api_press_release_publisher;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\taxonomy\Entity\Term;

/**
 * Provide some logic for press releases.
 *
 * @package Drupal\ecms_api_press_release_publisher
 */
class PressReleaseManager {

  /**
   * The press release vocabulary.
   */
  const VID = 'press_release_topics';

  /**
   * The ecms_api_recipient.settings configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  private $config;

  /**
   * The entity_type.manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * PressReleasePublisher constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config.factory service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity_type.manager service.
   */
  public function __construct(ConfigFactoryInterface $configFactory, EntityTypeManagerInterface $entityTypeManager) {
    $this->config = $configFactory->get('ecms_api_recipient.settings');
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * Add a new press release term to identify source site.
   *
   * @return \Drupal\taxonomy\Entity\Term
   *   The site source term object.
   */
  public function createSiteSourceTerm(): Term {
    $term_name = \Drupal::request()->getHost();

    $term = $this->entityTypeManager
      ->getStorage('taxonomy_term')
      ->loadByProperties(['name' => $term_name, 'vid' => self::VID]);
    $term = reset($term);
    if ($term === FALSE) {
      $term = Term::create(['name' => $term_name, 'vid' => self::VID]);
      $term->save();
    }

    return $term;
  }

  /**
   * Add the site source term if needed.
   */
  public function addSiteSourceTermToPressRelease(EntityInterface $entity): void {

    // Make sure term for site exists.
    $term = $this->createSiteSourceTerm();

    // Ensure site term is assigned to node.
    $hasSiteTerm = FALSE;
    $currentTerms = $entity->get('field_press_release_topics')->getValue();
    foreach ($currentTerms as $termArray) {
      if ($termArray['target_id'] === $term->id()) {
        $hasSiteTerm = TRUE;
        break;
      }
    }
    if (!$hasSiteTerm) {
      $currentTerms[] = ['target_id' => $term->id()];
      $entity->set('field_press_release_topics', $currentTerms);
    }

  }

}
