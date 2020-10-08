<?php

declare(strict_types = 1);

namespace Drupal\Tests\ecms_acquia\Functional;

// Require the all profiles abstract class since autoload doesn't work.
require_once dirname(__FILE__) . '/../../../../tests/src/Functional/AllProfileInstallationTestsAbstract.php';

use Drupal\Tests\ecms_profile\Functional\AllProfileInstallationTestsAbstract;

/**
 * Tests that installation finished correctly and known resources are available.
 *
 * @group ecms
 * @group ecms_acquia
 */
class InstallationTest extends AllProfileInstallationTestsAbstract {

  /**
   * The profile to install.
   *
   * @var string
   */
  protected $profile = 'ecms_acquia';

  /**
   * The theme to test with.
   *
   * @var string
   */
  protected $defaultTheme = 'ecms';

  /**
   * Test that the ACSF modules are installed.
   */
  public function testAcsfModulesEnabled(): void {
    $account = $this->createUser(['administer modules']);
    $this->drupalLogin($account);

    $this->drupalGet('admin/modules');
    $this->assertSession()->statusCodeEquals(200);

    $this->assertSession()->checkboxChecked('edit-modules-acsf-enable');
    $this->assertSession()->checkboxChecked('edit-modules-acsf-duplication-enable');
    $this->assertSession()->checkboxChecked('edit-modules-acsf-theme-enable');
    $this->assertSession()->checkboxChecked('edit-modules-acsf-variables-enable');
    $this->drupalLogout();
  }

}
