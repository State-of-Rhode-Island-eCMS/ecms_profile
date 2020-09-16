<?php

declare(strict_types = 1);

namespace Drupal\Tests\ecms_api_publisher\Functional;

// Require the all profiles abstract class since autoloading doesn't work.
require_once dirname(__FILE__) . '/../../../../../../../tests/src/Functional/AllProfileInstallationTestsAbstract.php';

use Drupal\Tests\ecms_profile\Functional\AllProfileInstallationTestsAbstract;

/**
 * Class InstallationTest.
 *
 * @package Drupal\Tests\ecms_api_publisher\Functional
 * @group ecms
 * @group ecms_api
 * @group ecms_api_publisher
 */
class InstallationTest extends AllProfileInstallationTestsAbstract {

  const API_HOST = 'https://oomphinc.com';

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
  protected static $modules = ['ecms_api_publisher'];

  /**
   * Test the ecms_api_recipient installation.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testEcmsApiPublisherEntityForms(): void {
    $account = $this->drupalCreateUser([
      'administer ecms api site entities',
      'add ecms api site entities',
      'edit ecms api site entities',
      'delete ecms api site entities',
      'view any published ecms api site entities',
    ]);
    $this->drupalLogin($account);

    $this->drupalGet('admin/config/ecms_api/ecms_api_publisher/site/add');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->fieldExists('edit-name-0-value');
    $this->assertSession()->fieldExists('edit-content-type-basic-page');
    $this->assertSession()->fieldExists('edit-api-host-0-uri');

    $values = [
      'edit-name-0-value' => 'Test Endpoint',
      'edit-content-type-basic-page' => 1,
      'edit-api-host-0-uri' => self::API_HOST,
    ];

    $this->drupalPostForm('admin/config/ecms_api/ecms_api_publisher/site/add', $values, 'Save');
    $url = $this->getUrl();

    // Parse the url to get the user account id.
    $details = explode('/', $url);
    $id = end($details);
    $this->assertIsNumeric($id);

    $this->drupalGet('admin/config/ecms_api/ecms_api_publisher/sites');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->linkExists('Test Endpoint');

    $this->drupalGet("admin/config/ecms_api/ecms_api_publisher/site/{$id}/edit");
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->checkboxChecked('edit-content-type-basic-page');
    $this->assertSession()->fieldValueEquals('edit-api-host-0-uri', self::API_HOST);

    $this->drupalGet("admin/config/ecms_api/ecms_api_publisher/site/{$id}/delete");
    $this->assertSession()->statusCodeEquals(200);

    $this->drupalPostForm("admin/config/ecms_api/ecms_api_publisher/site/{$id}/delete", [], 'Delete');
    $this->drupalGet('admin/config/ecms_api/ecms_api_publisher/sites');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->linkNotExists('Test Endpoint');

  }

}
