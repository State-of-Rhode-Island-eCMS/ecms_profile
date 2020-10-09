<?php

declare(strict_types = 1);

namespace Drupal\Tests\ecms_hotels\ExistingSite;

// Require the all profiles abstract class since autoloading doesn't work.
require_once dirname(__FILE__) . '/../../../../../../../tests/src/ExistingSite/AllProfileInstallationTestsAbstract.php';

use Drupal\Core\Entity\EntityStorageException;
use Drupal\media\Entity\Media;
use Drupal\node\Entity\Node;
use Drupal\Tests\ecms_profile\ExistingSite\AllProfileInstallationTestsAbstract;
use Drupal\user\Entity\Role;

/**
 * Functional tests for the EcmsHotelInstall feature.
 *
 * @package Drupal\Tests\ecms_hotels\ExistingSite
 * @group ecms
 * @group ecms_hotels
 */
class EcmsHotelInstallTest extends AllProfileInstallationTestsAbstract {

  /**
   * Required fields for the hotel content type.
   */
  const HOTEL_TRANSLATABLE_FIELDS = [
    'title[0][value]' => 'This is the hotel title',
    'field_hotel_body[0][value]' => 'This is the hotel body',
    'field_hotel_address[0][address][address_line1]' => '150 Chestnut Street',
    'field_hotel_address[0][address][locality]' => 'Providence',
    'field_hotel_address[0][address][postal_code]' => '02903',
    'field_hotel_rate[0][value]' => '100 dollars',
    'field_hotel_rate_amount[0][value]' => '1999',
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
   * Test the ecms_hotels installation.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testEcmsHotelInstallation(): void {
    $this->drupalLogin($this->account);

    $this->drupalGet('admin/modules');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->checkboxNotChecked('edit-modules-ecms-hotel-enable');

    // Enable the ecms_hotel feature.
    $edit = [];
    $edit["modules[ecms_hotel][enable]"] = TRUE;
    $this->drupalPostForm(NULL, $edit, t('Install'));
    $this->assertSession()->pageTextContainsOnce('Module eCMS Hotels has been enabled.');

    // Create the entities to test with after installation.
    $this->createTestEntities();

    // Once the hotel feature has been enabled,
    // add the new permissions to the role.
    /** @var \Drupal\user\RoleInterface $role */
    $role = Role::load($this->role);
    $role->grantPermission('create hotel content');
    $role->grantPermission('edit any hotel content');
    $role->grantPermission('translate hotel node');
    $role->save();

    // Browse to the hotel page and ensure we can choose a language.
    $this->drupalGet('node/add/hotel');
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

    foreach (self::HOTEL_TRANSLATABLE_FIELDS as $key => $value) {
      $this->assertSession()->pageTextContainsOnce($value);
    }

    foreach (self::DEFAULT_INSTALLED_LANGUAGES as $lang) {
      if ($lang === 'en') {
        continue;
      }

      $this->drupalGet("{$lang}/node/{$nodeId}/translations/add/en/{$lang}");
      $this->assertSession()->statusCodeEquals(200);

      $translationTitle = "Translation in {$lang}";
      $postTranslation = self::HOTEL_TRANSLATABLE_FIELDS;
      $postTranslation['title[0][value]'] = $translationTitle;
      $this->drupalPostForm(NULL, $postTranslation, t('Save (this translation)'));
      $this->assertSession()->pageTextContainsOnce("Hotel {$translationTitle} has been updated.");
      $translatedUrl = $this->getUrl();
      $translatedUrl = parse_url($translatedUrl, PHP_URL_PATH);
      $this->assertEqual($translatedUrl, "/{$lang}/node/{$nodeId}");
    }

    $this->drupalGet('admin/modules/uninstall');
    $this->assertSession()->checkboxNotChecked('uninstall[ecms_hotel]');

    $edit = [];
    $edit["uninstall[ecms_hotel]"] = TRUE;
    // Submit the uninstall form.
    $this->drupalPostForm(NULL, $edit, t('Uninstall'));

    // Submit the confirmation form.
    $this->drupalPostForm(NULL, [], t('Uninstall'));

    $this->assertSession()->fieldNotExists('uninstall[ecms_hotel]');

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
      'bundle' => 'hotel_image',
      'name' => 'Test Image',
    ]);

    try {
      $this->media->save();
    }
    catch (EntityStorageException $e) {
      // Trap storage errors.
    }

    $this->node = Node::create([
      'type' => 'hotel',
      'title' => 'This is the hotel title',
      'field_hotel_body' => 'This is the hotel body',
      'field_hotel_address' => [
        'country_code' => 'US',
        'address_line1' => '150 Chestnut Street',
        'locality' => 'Providence',
        'administrative_area' => 'US-RI',
        'postal_code' => '02903',
      ],
      'field_hotel_phone' => '401.228.7660',
      'field_hotel_rate' => '100 dollars',
      'field_hotel_rate_amount' => '1999',
      'field_hotel_main_image' => $this->media->id(),
      'field_hotel_cover_image' => $this->media->id(),
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
