<?php

declare(strict_types = 1);

namespace Drupal\Tests\ecms_publications\ExistingSite;

// Require the all profiles abstract class since autoloading doesn't work.
require_once dirname(__FILE__) . '/../../../../../../../tests/src/ExistingSite/AllProfileInstallationTestsAbstract.php';

use Drupal\Core\Entity\EntityStorageException;
use Drupal\media\Entity\Media;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
use Drupal\Tests\ecms_profile\ExistingSite\AllProfileInstallationTestsAbstract;
use Drupal\user\Entity\Role;

/**
 * ExistingSite testing for the EcmsPublicationsInstall feature.
 *
 * @package Drupal\Tests\ecms_publications\ExistingSite
 * @group ecms
 * @group ecms_publications
 */
class EcmsPublicationsInstallTest extends AllProfileInstallationTestsAbstract {

  /**
   * Required fields for the publication content type.
   */
  const PUBLICATION_TRANSLATABLE_FIELDS = [
    'title[0][value]' => 'This is a publication title',
    'field_publication_url[0][uri]' => 'https://oomphinc.com',
    'field_publication_url[0][title]' => 'Link Title',
  ];

  /**
   * The user entity to test with.
   *
   * @var \Drupal\user\Entity\User|false
   */
  private $account;

  /**
   * The role to create for testing.
   *
   * @var false|string
   */
  private $role;

  /**
   * The node to test with.
   *
   * @var \Drupal\node\NodeInterface
   */
  private $node;

  /**
   * The media element to attach to the node.
   *
   * @var \Drupal\media\MediaInterface
   */
  private $media;

  /**
   * The topic taxonomy term to test with.
   *
   * @var \Drupal\taxonomy\Entity\Term
   */
  private $topicTerm;

  /**
   * The audience taxonomy term to test with.
   *
   * @var \Drupal\taxonomy\Entity\Term
   */
  private $audienceTerm;

  /**
   * The publication taxonomy term to test with.
   *
   * @var \Drupal\taxonomy\Entity\Term
   */
  private $publicationTerm;

  /**
   * {@inheritDoc}
   */
  public function setUp(): void {
    parent::setUp();

    // Provide the role with known permissions to start.
    $this->role = $this->coreCreateRole([
      'administer modules',
      'administer site configuration',
      'access administration pages',
      'use editorial transition create_new_draft',
      'view any unpublished content',
      'create content translations',
      'rabbit hole bypass node',
    ]);

    $this->account = $this->createUser();
    $this->account->addRole($this->role);
    $this->account->save();
  }

  /**
   * Test the ecms_publications installation.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testEcmsPublicationInstallation(): void {

    $this->drupalLogin($this->account);

    $this->drupalGet('admin/modules');
    $this->assertSession()->statusCodeEquals(200);

    $this->assertSession()->checkboxNotChecked('edit-modules-ecms-publications-enable');

    // Enable the ecms_publications feature.
    $edit = [];
    $edit["modules[ecms_publications][enable]"] = TRUE;
    $this->drupalPostForm(NULL, $edit, t('Install'));
    $this->assertSession()->pageTextContainsOnce('Module eCMS Publications has been enabled.');

    // Create the entities to test with after installation.
    $this->createTestEntities();

    // Once the ecms_publications feature has been enabled,
    // add the new permissions to the role.
    /** @var \Drupal\user\RoleInterface $role */
    $role = Role::load($this->role);
    $role->grantPermission('create publication content');
    $role->grantPermission('edit any publication content');
    $role->grantPermission('translate publication node');
    $role->save();

    // Browse to the publication page and ensure we can choose a language.
    $this->drupalGet('node/add/publication');
    $this->assertSession()->statusCodeEquals(200);

    // Ensure the language selection exists on the node form.
    $this->assertSession()->fieldExists('edit-langcode-0-value');
    // Ensure the default languages exist on the node form.
    foreach (self::DEFAULT_INSTALLED_LANGUAGES as $langcode) {
      $this->assertSession()->optionExists('edit-langcode-0-value', $langcode);
    }

    $nodeId = $this->node->id();

    $this->drupalGet("node/{$nodeId}");
    $this->assertSession()->statusCodeEquals(200);

    foreach (self::DEFAULT_INSTALLED_LANGUAGES as $lang) {
      if ($lang === 'en') {
        continue;
      }

      $this->drupalGet("{$lang}/node/{$nodeId}/translations/add/en/{$lang}");
      $this->assertSession()->statusCodeEquals(200);

      $translationTitle = "Translation in {$lang}";
      $postTranslation = self::PUBLICATION_TRANSLATABLE_FIELDS;
      $postTranslation['title[0][value]'] = $translationTitle;
      $this->drupalPostForm(NULL, $postTranslation, t('Save (this translation)'));
      $this->assertSession()->pageTextContainsOnce("Publication {$translationTitle} has been updated.");
      $translatedUrl = $this->getUrl();
      $translatedUrl = parse_url($translatedUrl, PHP_URL_PATH);
      $this->assertEqual($translatedUrl, "/{$lang}/node/{$nodeId}");
    }

    $this->drupalGet('admin/modules/uninstall');
    $this->assertSession()->checkboxNotChecked('uninstall[ecms_publications]');

    $edit = [];
    $edit["uninstall[ecms_publications]"] = TRUE;
    // Submit the uninstall form.
    $this->drupalPostForm(NULL, $edit, t('Uninstall'));

    // Submit the confirmation form.
    $this->drupalPostForm(NULL, [], t('Uninstall'));

    $this->assertSession()->fieldNotExists('uninstall[ecms_publications]');

    $this->drupalLogout();

  }

  /**
   * {@inheritDoc}
   */
  public function tearDown(): void {
    parent::tearDown();

    if (!empty($this->account)) {
      $this->account->delete();
    }

    $role = Role::load($this->role);
    if (!empty($role)) {
      $role->delete();
    }

    $this->node->delete();
    $this->media->delete();
    $this->publicationTerm->delete();
    $this->audienceTerm->delete();
    $this->topicTerm->delete();

  }

  /**
   * Helper method to create test entities.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  private function createTestEntities(): void {

    $this->media = Media::create([
      'bundle' => 'publication_file',
      'name' => 'Test File',
    ]);

    try {
      $this->media->save();
    }
    catch (EntityStorageException $e) {
      // Trap storage errors.
    }

    $this->publicationTerm = Term::create([
      'vid' => 'publication_type',
      'name' => $this->randomMachineName(),
    ]);

    $this->audienceTerm = Term::create([
      'vid' => 'publication_audience',
      'name' => $this->randomMachineName(),
    ]);

    $this->topicTerm = Term::create([
      'vid' => 'publication_topic',
      'name' => $this->randomMachineName(),
    ]);

    try {
      $this->publicationTerm->save();
      $this->audienceTerm->save();
      $this->topicTerm->save();
    }
    catch (EntityStorageException $e) {
      // Trap storage errors.
    }

    $this->node = Node::create([
      'type' => 'publication',
      'title' => 'This is a publication title',
      'field_publication_date_modified' => '2020-10-12T00:08:45',
      'field_publication_audiences' => $this->audienceTerm->id(),
      'field_publication_topics' => $this->topicTerm->id(),
      'field_publication_types' => $this->publicationTerm->id(),
      'field_publication_file_download' => $this->media->id(),
      'field_publication_url' => [
        'uri' => 'https://oomphinc.com',
        'title' => 'Link Title',
      ],
      'uid' => $this->account->id(),
    ]);

    try {
      $this->node->save();
    }
    catch (EntityStorageException $e) {
      // Trap storage errors.
    }
  }

}
