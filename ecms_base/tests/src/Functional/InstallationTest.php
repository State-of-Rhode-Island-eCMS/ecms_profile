<?php

declare(strict_types = 1);

namespace Drupal\Tests\ecms_base\Functional;

// Require the all profiles abstract class since autoloading doesn't work.
require_once dirname(__FILE__) . '/../../../../tests/src/Functional/AllProfileInstallationTestsAbstract.php';

use Drupal\Tests\ecms_profile\Functional\AllProfileInstallationTestsAbstract;

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
  protected $defaultTheme = 'stark';

  /**
   * Test that the ACSF modules are not installed.
   */
  public function testAcsfModulesDisabled(): void {
    $account = $this->drupalCreateUser(['administer modules']);
    $this->drupalLogin($account);

    $this->drupalGet('admin/modules');
    $this->assertSession()->statusCodeEquals(200);

    $this->assertSession()->checkboxNotChecked('edit-modules-acsf-enable');
    $this->assertSession()->checkboxNotChecked('edit-modules-acsf-duplication-enable');
    $this->assertSession()->checkboxNotChecked('edit-modules-acsf-theme-enable');
    $this->assertSession()->checkboxNotChecked('edit-modules-acsf-variables-enable');
  }

}
