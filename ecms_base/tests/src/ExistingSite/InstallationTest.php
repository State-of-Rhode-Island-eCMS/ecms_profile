<?php

declare(strict_types=1);

namespace Drupal\Tests\ecms_base\ExistingSite;

// Require the all profiles abstract class since autoloading doesn't work.
require_once dirname(__FILE__) . '/../../../../tests/src/ExistingSite/AllProfileInstallationTestsAbstract.php';

use Drupal\Tests\ecms_profile\ExistingSite\AllProfileInstallationTestsAbstract;

/**
 * Tests that installation finished correctly and known resources are available.
 *
 * @group ecms
 * @group ecms_base
 */
class InstallationTest extends AllProfileInstallationTestsAbstract {

  /**
   * The profile to install.
   *
   * @var string
   */
  protected $profile = 'ecms_base';

  /**
   * The theme to test with.
   *
   * @var string
   */
  protected $defaultTheme = 'ecms';

  /**
   * Run all available tests.
   *
   * This combines all of the functional tests into one allowing for only one
   * Drupal installation. This shuold significantly increase the speed of
   * all of the tests.
   */
  public function testAllTheThings(): void {
    // Run our profile tests only.
    $this->ensureAcsfModulesDisabled();

    // Run all of the global tests.
    $this->globalTests();
  }

  /**
   * Test that the ACSF modules are not installed.
   */
  private function ensureAcsfModulesDisabled(): void {
    $account = $this->createUser(['administer modules']);
    $this->drupalLogin($account);

    $this->drupalGet('admin/modules');
    $this->assertSession()->statusCodeEquals(200);

    $this->assertSession()->checkboxNotChecked('edit-modules-acsf-enable');
    $this->assertSession()->checkboxNotChecked('edit-modules-acsf-duplication-enable');
    $this->assertSession()->checkboxNotChecked('edit-modules-acsf-theme-enable');
    $this->assertSession()->checkboxNotChecked('edit-modules-acsf-variables-enable');
    $this->drupalLogout();
  }

}
