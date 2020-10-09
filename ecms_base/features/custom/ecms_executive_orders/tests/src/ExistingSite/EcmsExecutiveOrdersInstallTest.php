<?php

declare(strict_types = 1);

namespace Drupal\Tests\ecms_executive_orders\ExistingSite;

// Require the all profiles abstract class since autoloading doesn't work.
require_once dirname(__FILE__) . '/../../../../../../../tests/src/ExistingSite/AllProfileInstallationTestsAbstract.php';

use Drupal\Core\Entity\EntityStorageException;
use Drupal\media\Entity\Media;
use Drupal\node\Entity\Node;
use Drupal\Tests\ecms_profile\ExistingSite\AllProfileInstallationTestsAbstract;
use Drupal\user\Entity\Role;

/**
 * ExistingSite tests for the EcmsExecutiveOrdersInstall feature.
 *
 * @package Drupal\Tests\ecms_executive_orders\ExistingSite
 * @group ecms
 * @group ecms_executive_orders
 */
class EcmsExecutiveOrdersInstallTest extends AllProfileInstallationTestsAbstract {

  /**
   * Test the ecms_executive_orders installation.
   * Required fields for the executive_order content type.
   */
  const EXECUTIVE_ORDER_TRANSLATABLE_FIELDS = [
    'title[0][value]' => 'This is an executive order title',
    'field_executive_order_long_title[0][value]' => 'This is an executive order body',
    'field_executive_order_text[0][value]' => 'This is an executive order long text field',
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
      'rabbit hole bypass node',
    ]);

    $this->account = $this->createUser();
    $this->account->addRole($this->role);
    $this->account->save();
  }

  /**
   * Test the ecms_executive_orders installation.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testEcmsExecutiveOrdersInstallation(): void {
    $this->drupalLogin($this->account);

    $this->drupalGet('admin/modules');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->checkboxNotChecked('edit-modules-ecms-executive-orders-enable');

    // Enable the ecms_executive_orders feature.
    $edit = [];
    $edit["modules[ecms_executive_orders][enable]"] = TRUE;
    $this->drupalPostForm(NULL, $edit, t('Install'));
    $this->assertSession()->pageTextContainsOnce('Module eCMS Executive Orders has been enabled.');

    // Create the entities to test with after installation.
    $this->createTestEntities();

    // Once the executive_order feature has been enabled,
    // add the new permissions to the role.
    /** @var \Drupal\user\RoleInterface $role */
    $role = Role::load($this->role);
    $role->grantPermission('create executive_order content');
    $role->grantPermission('edit any executive_order content');
    $role->grantPermission('translate executive_order node');
    $role->save();

    // Browse to the executive_order page and ensure we can choose a language.
    $this->drupalGet('node/add/executive_order');
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

    foreach (self::EXECUTIVE_ORDER_TRANSLATABLE_FIELDS as $key => $value) {
      $this->assertSession()->pageTextContainsOnce($value);
    }

    foreach (self::DEFAULT_INSTALLED_LANGUAGES as $lang) {
      if ($lang === 'en') {
        continue;
      }

      $this->drupalGet("{$lang}/node/{$nodeId}/translations/add/en/{$lang}");
      $this->assertSession()->statusCodeEquals(200);

      $translationTitle = "Translation in {$lang}";
      $postTranslation = self::EXECUTIVE_ORDER_TRANSLATABLE_FIELDS;
      $postTranslation['title[0][value]'] = $translationTitle;
      $this->drupalPostForm(NULL, $postTranslation, t('Save (this translation)'));
      $this->assertSession()->pageTextContainsOnce("Executive Order {$translationTitle} has been updated.");
      $translatedUrl = $this->getUrl();
      $translatedUrl = parse_url($translatedUrl, PHP_URL_PATH);
      $this->assertEqual($translatedUrl, "/{$lang}/node/{$nodeId}/latest");
    }

    $this->drupalGet('admin/modules/uninstall');
    $this->assertSession()->checkboxNotChecked('uninstall[ecms_executive_orders]');

    $edit = [];
    $edit["uninstall[ecms_executive_orders]"] = TRUE;
    // Submit the uninstall form.
    $this->drupalPostForm(NULL, $edit, t('Uninstall'));

    // Submit the confirmation form.
    $this->drupalPostForm(NULL, [], t('Uninstall'));

    $this->assertSession()->fieldNotExists('uninstall[ecms_executive_orders]');

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
      'bundle' => 'executive_order_pdf',
      'name' => 'Test PDF',
    ]);

    try {
      $this->media->save();
    }
    catch (EntityStorageException $e) {
      // Trap storage errors.
    }

    $this->node = Node::create([
      'type' => 'executive_order',
      'title' => 'This is an executive order title',
      'field_executive_order_date' => '2020-10-09',
      'field_executive_order_long_title' => 'This is an executive order body',
      'field_executive_order_text' => 'This is an executive order long text field',
      'field_executive_order_pdf' => $this->media->id(),
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
