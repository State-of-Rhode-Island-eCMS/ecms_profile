<?php

declare(strict_types = 1);

namespace Drupal\Tests\ecms_workflow\Functional;

// Require the all profiles abstract class since autoloading doesn't work.
require_once dirname(__FILE__) . '/../../../../../../../tests/src/Functional/AllProfileInstallationTestsAbstract.php';

use Drupal\Tests\ecms_profile\Functional\AllProfileInstallationTestsAbstract;

/**
 * Functional tests for the ecms_workflow module.
 *
 * @package Drupal\Tests\ecms_workflow\Functional
 * @group ecms
 * @group ecms_workflow
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
  protected static $modules = ['ecms_workflow'];

  /**
   * Test the ecms_workflow installation.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testEcmsApiRecipientInstallation(): void {
    $account = $this->drupalCreateUser([
      'administer permissions',
      'access administration pages',
    ]);
    $this->drupalLogin($account);

    // Ensure content types have proper permissions.
    $this->drupalGet('admin/people/permissions');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->checkboxChecked('edit-content-author-create-basic-page-content');
    $this->assertSession()->checkboxChecked('edit-content-publisher-create-basic-page-content');
    $this->assertSession()->checkboxChecked('edit-content-publisher-edit-any-basic-page-content');

    $this->assertSession()->checkboxNotChecked('edit-content-author-edit-any-basic-page-content');

  }

}
