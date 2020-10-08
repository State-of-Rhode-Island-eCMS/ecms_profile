<?php

declare(strict_types = 1);

namespace Drupal\Tests\ecms_hotels\ExistingSite;

// Require the all profiles abstract class since autoloading doesn't work.
require_once dirname(__FILE__) . '/../../../../../../../tests/src/ExistingSite/AllProfileInstallationTestsAbstract.php';

use Drupal\Tests\ecms_profile\ExistingSite\AllProfileInstallationTestsAbstract;

/**
 * Functional tests for the EcmsHotelInstall feature.
 *
 * @package Drupal\Tests\ecms_hotels\ExistingSite
 * @group ecms
 * @group ecms_hotels
 */
class EcmsHotelInstallTest extends AllProfileInstallationTestsAbstract {

  /**
   * Test the ecms_hotels installation.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testEcmsHotelInstallation(): void {
    $account = $this->createUser([
      'administer modules',
      'administer site configuration',
      'access administration pages',
    ]);
    $this->drupalLogin($account);

    $this->drupalGet('admin/modules');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->checkboxNotChecked('edit-modules-ecms-hotel-enable');

    // Enable the ecms_hotel feature.
    $edit = [];
    $edit["modules[ecms_hotel][enable]"] = TRUE;
    $this->drupalPostForm(NULL, $edit, t('Install'));
    $this->assertSession()->pageTextContainsOnce('Module eCMS Hotels has been enabled.');

  }

}
