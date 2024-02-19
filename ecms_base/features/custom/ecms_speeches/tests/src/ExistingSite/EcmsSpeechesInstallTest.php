<?php

declare(strict_types=1);

namespace Drupal\Tests\ecms_speeches\ExistingSite;

// Require the all profiles abstract class since autoloading doesn't work.
require_once dirname(__FILE__) . '/../../../../../../../tests/src/ExistingSite/AllProfileInstallationTestsAbstract.php';

use Drupal\Core\Entity\EntityStorageException;
use Drupal\node\Entity\Node;
use Drupal\Tests\ecms_profile\ExistingSite\AllProfileInstallationTestsAbstract;
use Drupal\user\Entity\Role;

/**
 * ExistingSite tests for the EcmsSpeechesInstall feature.
 *
 * @package Drupal\Tests\ecms_hotels\ExistingSite
 * @group ecms
 * @group ecms_speeches
 */
class EcmsSpeechesInstallTest extends AllProfileInstallationTestsAbstract {

  /**
   * Required fields for the speech content type.
   */
  const SPEECH_TRANSLATABLE_FIELDS = [
    'title[0][value]' => 'This is a speech title',
    'field_speech_long_title[0][value]' => 'This is a speech long title.',
    'field_speech_text[0][value]' => 'This is a speech text field.',
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
   * Test the ecms_speeches installation.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testEcmsSpeechesInstallation(): void {
    $this->drupalLogin($this->account);

    $this->drupalGet('admin/modules');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->checkboxNotChecked('edit-modules-ecms-speeches-enable');

    // Enable the ecms_speeches feature.
    $edit = [];
    $edit["modules[ecms_speeches][enable]"] = TRUE;
    $this->drupalPostForm(NULL, $edit, t('Install'));
    $this->assertSession()->pageTextContainsOnce('Module eCMS Speeches has been enabled.');

    // Create the entities to test with after installation.
    $this->createTestEntities();

    // Once the ecms_speeches feature has been enabled,
    // add the new permissions to the role.
    /** @var \Drupal\user\RoleInterface $role */
    $role = Role::load($this->role);
    $role->grantPermission('create speech content');
    $role->grantPermission('edit any speech content');
    $role->grantPermission('translate speech node');
    $role->save();

    // Browse to the speech page and ensure we can choose a language.
    $this->drupalGet('node/add/speech');
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

    foreach (self::SPEECH_TRANSLATABLE_FIELDS as $key => $value) {
      $this->assertSession()->pageTextContains($value);
    }

    foreach (self::DEFAULT_INSTALLED_LANGUAGES as $lang) {
      if ($lang === 'en') {
        continue;
      }

      $this->drupalGet("{$lang}/node/{$nodeId}/translations/add/en/{$lang}");
      $this->assertSession()->statusCodeEquals(200);

      $translationTitle = "Translation in {$lang}";
      $postTranslation = self::SPEECH_TRANSLATABLE_FIELDS;
      $postTranslation['title[0][value]'] = $translationTitle;
      $this->drupalPostForm(NULL, $postTranslation, t('Save (this translation)'));
      $this->assertSession()->pageTextContains("Speech {$translationTitle} has been updated.");
      $translatedUrl = $this->getUrl();
      $translatedUrl = parse_url($translatedUrl, PHP_URL_PATH);
      $this->assertEqual($translatedUrl, "/{$lang}/node/{$nodeId}");
    }

    $this->drupalGet('admin/modules/uninstall');
    $this->assertSession()->checkboxNotChecked('uninstall[ecms_speeches]');

    $edit = [];
    $edit["uninstall[ecms_speeches]"] = TRUE;
    // Submit the uninstall form.
    $this->drupalPostForm(NULL, $edit, t('Uninstall'));

    // Submit the confirmation form.
    $this->drupalPostForm(NULL, [], t('Uninstall'));

    $this->assertSession()->fieldNotExists('uninstall[ecms_speeches]');

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

  }

  /**
   * Helper method to create test entities.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  private function createTestEntities(): void {

    $this->node = Node::create([
      'type' => 'speech',
      'title' => 'This is a speech title',
      'field_speech_long_title' => 'This is a speech long title.',
      'field_speech_text' => 'This is a speech text field.',
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
