<?php

declare(strict_types = 1);

namespace Drupal\Tests\ecms_api_recipient\Functional;

// Require the all profiles abstract class since autoloading doesn't work.
require_once dirname(__FILE__) . '/../../../../../../../tests/src/Functional/AllProfileInstallationTestsAbstract.php';

use Drupal\Tests\ecms_profile\Functional\AllProfileInstallationTestsAbstract;

/**
 * Class InstallationTest.
 *
 * @package Drupal\Tests\ecms_api_recipient\Functional
 * @group ecms
 * @group ecms_api
 * @group ecms_api_recipient
 */
class InstallationTest extends AllProfileInstallationTestsAbstract {

  /**
   * Define the profile to test.
   *
   * @var string
   */
  protected $profile = 'ecms_base';

  /**
   * Define the additional modules to install.
   *
   * @var string[]
   */
  protected static $modules = ['ecms_api_recipient'];

  /**
   * Test the ecms_api_recipient installation.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testEcmsApiRecipientInstallation(): void {
    $account = $this->drupalCreateUser([
      'administer permissions',
      'administer consumer entities',
      'administer users',
    ]);
    $this->drupalLogin($account);

    // Ensure the ecms_api_recipient role was installed.
    $this->drupalGet('admin/people/roles/manage/ecms_api_recipient');
    $this->assertSession()->statusCodeEquals(200);

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
  }

}
