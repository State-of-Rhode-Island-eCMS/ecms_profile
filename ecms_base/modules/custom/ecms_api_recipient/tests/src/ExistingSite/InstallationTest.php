<?php

declare(strict_types = 1);

namespace Drupal\Tests\ecms_api_recipient\ExistingSite;

// Require the all profiles abstract class since autoloading doesn't work.
require_once dirname(__FILE__) . '/../../../../../../../tests/src/ExistingSite/AllProfileInstallationTestsAbstract.php';

use Drupal\Tests\ecms_profile\ExistingSite\AllProfileInstallationTestsAbstract;
use Drupal\user\Entity\Role;

/**
 * ExistingSite testing for the ecms_api_recipient module.
 *
 * @package Drupal\Tests\ecms_api_recipient\ExistingSite
 * @group ecms
 * @group ecms_api
 * @group ecms_api_recipient
 */
class InstallationTest extends AllProfileInstallationTestsAbstract {

  /**
   * The user account to test with.
   *
   * @var \Drupal\user\Entity\User
   */
  private $account;

  /**
   * The user role id to test with.
   *
   * @var string
   */
  private $role;

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->role = $this->coreCreateRole([
      'administer modules',
      'administer permissions',
      'administer users',
      'administer site configuration',
      'access administration pages',
    ]);

    $this->account = $this->coreCreateUser();
    $this->account->addRole($this->role);
    $this->account->save();
  }

  /**
   * Test the ecms_api_recipient installation.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testEcmsApiRecipientInstallation(): void {

    $this->drupalLogin($this->account);

    $this->drupalGet('admin/modules');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->checkboxNotChecked('modules[ecms_api_recipient][enable]');

    // Enable the ecms_api_recipient module.
    $edit = [];
    $edit["modules[ecms_api_recipient][enable]"] = TRUE;
    $this->drupalPostForm(NULL, $edit, t('Install'));

    // Submit the confirmation form.
    $this->drupalPostForm(NULL, [], t('Continue'));

    // Give the user the correct permissions.
    $role = Role::load($this->role);
    $role->grantPermission('administer consumer entities');
    $role->save();

    // Ensure the ecms_api_recipient role was installed.
    $this->drupalGet('admin/people/roles/manage/ecms_api_recipient');
    $this->assertSession()->statusCodeEquals(200);

    // Ensure the correct permissions are selected on install.
    $this->drupalGet('admin/people/permissions/ecms_api_recipient');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->checkboxChecked('ecms_api_recipient[create notification content]');
    $this->assertSession()->checkboxChecked('ecms_api_recipient[edit own notification content]');
    $this->assertSession()->checkboxChecked('ecms_api_recipient[use editorial transition archive]');
    $this->assertSession()->checkboxChecked('ecms_api_recipient[use editorial transition create_new_draft]');
    $this->assertSession()->checkboxChecked('ecms_api_recipient[use editorial transition archived_published]');
    $this->assertSession()->checkboxChecked('ecms_api_recipient[use editorial transition publish]');
    $this->assertSession()->checkboxChecked('ecms_api_recipient[use editorial transition review]');
    $this->assertSession()->checkboxChecked('ecms_api_recipient[create content translations]');
    $this->assertSession()->checkboxChecked('ecms_api_recipient[translate notification node]');
    $this->assertSession()->checkboxChecked('ecms_api_recipient[view own unpublished content]');

    // Ensure the user account was created.
    $this->drupalGet('admin/people');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->linkExists('ecms_api_recipient');
    $this->clickLink('ecms_api_recipient');
    $this->assertSession()->statusCodeEquals(200);
    $accountUrl = $this->getUrl();

    // Parse the url to get the user account id.
    $accountDetails = explode('/', $accountUrl);
    $accountId = end($accountDetails);
    $this->assertIsNumeric($accountId);

    // Browse to the user edit page.
    $this->drupalGet("{$accountUrl}/edit");
    // Ensure the user has the correct role selected.
    $this->assertSession()->checkboxChecked('edit-roles-ecms-api-recipient');
    // Ensure the user is active.
    $this->assertSession()->checkboxChecked('edit-status-1');

    // Assert the consumer is in the consumer's list.
    $this->drupalGet('admin/config/services/consumer');
    $this->assertSession()->statusCodeEquals(200);
    // Ensure the created consumer exists.
    $this->assertSession()->linkExists('eCMS Recipient');
    $this->clickLink('eCMS Recipient');
    $url = $this->getUrl();
    // Edit the consumer.
    $this->drupalGet("{$url}/edit");
    $this->assertSession()->statusCodeEquals(200);
    // Ensure the api user is set.
    $this->assertSession()->fieldValueEquals('edit-user-id-0-target-id', "ecms_api_recipient ({$accountId})");
    // Ensure the correct role is set.
    $this->assertSession()->checkboxChecked('edit-roles-ecms-api-recipient');

    $this->drupalGet('admin/config/ecms_api/ecms_api_recipient/settings');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->checkboxChecked('edit-allowed-content-types-notification');

    $this->assertSession()->fieldExists('edit-allowed-content-types-basic-page');
    $configFormSubmission = [
      'edit-allowed-content-types-basic-page' => 1,
      'edit-allowed-content-types-notification' => 1,
    ];
    $this->drupalPostForm('admin/config/ecms_api/ecms_api_recipient/settings', $configFormSubmission, 'Save configuration');
    $this->drupalGet('admin/people/permissions/ecms_api_recipient');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->checkboxChecked('edit-ecms-api-recipient-use-editorial-transition-publish');
    $this->assertSession()->checkboxChecked('edit-ecms-api-recipient-create-notification-content');
    $this->assertSession()->checkboxChecked('edit-ecms-api-recipient-edit-own-notification-content');
    $this->assertSession()->checkboxChecked('edit-ecms-api-recipient-translate-notification-node');
    $this->assertSession()->checkboxChecked('edit-ecms-api-recipient-create-basic-page-content');
    $this->assertSession()->checkboxChecked('edit-ecms-api-recipient-edit-own-basic-page-content');
    $this->assertSession()->checkboxChecked('edit-ecms-api-recipient-create-content-translations');
    $this->assertSession()->checkboxChecked('edit-ecms-api-recipient-translate-basic-page-node');

    $this->assertSession()->checkboxNotChecked('edit-ecms-api-recipient-create-event-content');
    $this->assertSession()->checkboxNotChecked('edit-ecms-api-recipient-edit-own-event-content');

    // Ensure the menu link is available.
    $this->drupalGet('admin/config/services');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->linkExists('eCMS API Allowed Recipients');

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
  }

}
