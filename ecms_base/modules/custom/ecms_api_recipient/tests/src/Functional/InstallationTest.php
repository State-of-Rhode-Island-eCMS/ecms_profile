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
    $account = $this->drupalCreateUser(['administer permissions']);
    $this->drupalLogin($account);

    // Ensure the ecms_api_recipient role was installed.
    $this->drupalGet('admin/people/roles/manage/ecms_api_recipient');
    $this->assertSession()->statusCodeEquals(200);
  }

}
