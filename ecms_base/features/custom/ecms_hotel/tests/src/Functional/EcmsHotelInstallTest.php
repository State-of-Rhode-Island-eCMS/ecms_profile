<?php

declare(strict_types = 1);

namespace Drupal\Tests\ecms_hotels\Functional;

// Require the all profiles abstract class since autoloading doesn't work.
require_once dirname(__FILE__) . '/../../../../../../../tests/src/Functional/AllProfileInstallationTestsAbstract.php';

use Drupal\Tests\ecms_profile\Functional\AllProfileInstallationTestsAbstract;

/**
 * Functional tests for the EcmsHotelInstall feature.
 *
 * @package Drupal\Tests\ecms_hotels\Functional
 * @group ecms
 * @group ecms_hotels
 */
class EcmsHotelInstallTest extends AllProfileInstallationTestsAbstract {

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
   * Test the ecms_hotels installation.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testEcmsHotelInstallation(): void {
    $account = $this->drupalCreateUser([
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
    $this->assertText('Module eCMS Hotels has been enabled.');

  }

}
