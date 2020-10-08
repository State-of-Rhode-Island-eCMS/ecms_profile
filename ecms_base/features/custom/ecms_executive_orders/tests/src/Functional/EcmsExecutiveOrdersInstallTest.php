<?php

declare(strict_types = 1);

namespace Drupal\Tests\ecms_executive_orders\Functional;

// Require the all profiles abstract class since autoloading doesn't work.
require_once dirname(__FILE__) . '/../../../../../../../tests/src/Functional/AllProfileInstallationTestsAbstract.php';

use Drupal\Tests\ecms_profile\Functional\AllProfileInstallationTestsAbstract;

/**
 * Functional tests for the EcmsExecutiveOrdersInstall feature.
 *
 * @package Drupal\Tests\ecms_executive_orders\Functional
 * @group ecms
 * @group ecms_executive_orders
 */
class EcmsExecutiveOrdersInstallTest extends AllProfileInstallationTestsAbstract {

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
   * Test the ecms_executive_orders installation.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testEcmsExecutiveOrdersInstallation(): void {
    $account = $this->drupalCreateUser([
      'administer modules',
      'administer site configuration',
      'access administration pages',
    ]);
    $this->drupalLogin($account);

    $this->drupalGet('admin/modules');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->checkboxNotChecked('edit-modules-ecms-executive-orders-enable');

    // Enable the ecms_executive_orders feature.
    $edit = [];
    $edit["modules[ecms_executive_orders][enable]"] = TRUE;
    $this->drupalPostForm(NULL, $edit, t('Install'));
    $this->assertText('Module eCMS Executive Orders has been enabled.');

  }

}
