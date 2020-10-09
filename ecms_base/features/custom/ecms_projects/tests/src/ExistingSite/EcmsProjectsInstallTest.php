<?php

declare(strict_types = 1);

namespace Drupal\Tests\ecms_projects\ExistingSite;

// Require the all profiles abstract class since autoloading doesn't work.
require_once dirname(__FILE__) . '/../../../../../../../tests/src/ExistingSite/AllProfileInstallationTestsAbstract.php';

use Drupal\Core\Entity\EntityStorageException;
use Drupal\media\Entity\Media;
use Drupal\node\Entity\Node;
use Drupal\Tests\ecms_profile\ExistingSite\AllProfileInstallationTestsAbstract;
use Drupal\user\Entity\Role;

/**
 * ExistingSite tests for the EcmsProjectsInstall feature.
 *
 * @package Drupal\Tests\ecms_projects\ExistingSite
 * @group ecms
 * @group ecms_projects
 */
class EcmsProjectsInstallTest extends AllProfileInstallationTestsAbstract {

  /**
   * Required fields for the project content type.
   */
  const PROJECT_TRANSLATABLE_FIELDS = [
    'title[0][value]' => 'This is the project title',
    'field_project_body[0][value]' => 'This is the project body',
    'field_project_detours_needed[0][value]' => 'Detours needed',
    'field_project_end_year[0][value]' => '2021',
    'field_project_location[0][value]' => 'Providence',
    'field_project_start_year[0][value]' => '2020',
    'field_project_total_cost[0][value]' => '1 million',
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
    ]);

    $this->account = $this->createUser();
    $this->account->addRole($this->role);
    $this->account->save();
  }

  /**
   * Test the ecms_projects installation.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testEcmsProjectsInstallation(): void {
    $this->drupalLogin($this->account);

    $this->drupalGet('admin/modules');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->checkboxNotChecked('edit-modules-ecms-projects-enable');

    // Enable the ecms_projects feature.
    $edit = [];
    $edit["modules[ecms_projects][enable]"] = TRUE;
    $this->drupalPostForm(NULL, $edit, t('Install'));
    $this->assertSession()->pageTextContainsOnce('Module eCMS Projects has been enabled.');

    // Create the entities to test with after installation.
    $this->createTestEntities();

    // Once the project feature has been enabled,
    // add the new permissions to the role.
    /** @var \Drupal\user\RoleInterface $role */
    $role = Role::load($this->role);
    $role->grantPermission('create project content');
    $role->grantPermission('edit any project content');
    $role->grantPermission('translate project node');
    $role->save();

    // Browse to the project page and ensure we can choose a language.
    $this->drupalGet('node/add/project');
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

    foreach (self::PROJECT_TRANSLATABLE_FIELDS as $key => $value) {
      $this->assertSession()->pageTextContainsOnce($value);
    }

    foreach (self::DEFAULT_INSTALLED_LANGUAGES as $lang) {
      if ($lang === 'en') {
        continue;
      }

      $this->drupalGet("{$lang}/node/{$nodeId}/translations/add/en/{$lang}");
      $this->assertSession()->statusCodeEquals(200);

      $translationTitle = "Translation in {$lang}";
      $postTranslation = self::PROJECT_TRANSLATABLE_FIELDS;
      $postTranslation['title[0][value]'] = $translationTitle;
      $this->drupalPostForm(NULL, $postTranslation, t('Save (this translation)'));
      $this->assertSession()->pageTextContainsOnce("Project {$translationTitle} has been updated.");
      $translatedUrl = $this->getUrl();
      $translatedUrl = parse_url($translatedUrl, PHP_URL_PATH);
      $this->assertEqual($translatedUrl, "/{$lang}/node/{$nodeId}/latest");
    }

    $this->drupalGet('admin/modules/uninstall');
    $this->assertSession()->checkboxNotChecked('uninstall[ecms_projects]');

    $edit = [];
    $edit["uninstall[ecms_projects]"] = TRUE;
    // Submit the uninstall form.
    $this->drupalPostForm(NULL, $edit, t('Uninstall'));

    // Submit the confirmation form.
    $this->drupalPostForm(NULL, [], t('Uninstall'));

    $this->assertSession()->fieldNotExists('uninstall[ecms_projects]');

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

  }

  /**
   * Helper method to create test entities.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  private function createTestEntities(): void {

    $this->media = Media::create([
      'bundle' => 'project_main_image',
      'name' => 'Test Image',
    ]);

    try {
      $this->media->save();
    }
    catch (EntityStorageException $e) {
      // Trap storage errors.
    }

    $this->node = Node::create([
      'type' => 'project',
      'title' => 'This is the project title',
      'field_project_body' => 'This is the project body',
      'field_project_detours_needed' => 'Detours needed',
      'field_project_end_year' => '2021',
      'field_project_location' => 'Providence',
      'field_project_start_year' => '2020',
      'field_project_total_cost' => '1 million',
      'field_project_main_image' => $this->media->id(),
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
