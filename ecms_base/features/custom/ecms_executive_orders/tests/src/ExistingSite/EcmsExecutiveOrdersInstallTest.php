<?php

declare(strict_types = 1);

namespace Drupal\Tests\ecms_executive_orders\ExistingSite;

// Require the all profiles abstract class since autoloading doesn't work.
require_once dirname(__FILE__) . '/../../../../../../../tests/src/ExistingSite/AllProfileInstallationTestsAbstract.php';

use Drupal\Tests\ecms_profile\ExistingSite\AllProfileInstallationTestsAbstract;

/**
 * Functional tests for the EcmsExecutiveOrdersInstall feature.
 *
 * @package Drupal\Tests\ecms_executive_orders\ExistingSite
 * @group ecms
 * @group ecms_executive_orders
 */
class EcmsExecutiveOrdersInstallTest extends AllProfileInstallationTestsAbstract {

  /**
   * Test the ecms_executive_orders installation.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testEcmsExecutiveOrdersInstallation(): void {
    $account = $this->createUser([
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
    $this->assertSession()->pageTextContainsOnce('Module eCMS Executive Orders has been enabled.');

  }

}
