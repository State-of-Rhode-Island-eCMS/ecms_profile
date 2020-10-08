<?php

declare(strict_types = 1);

namespace Drupal\Tests\ecms_speeches\ExistingSite;

// Require the all profiles abstract class since autoloading doesn't work.
require_once dirname(__FILE__) . '/../../../../../../../tests/src/ExistingSite/AllProfileInstallationTestsAbstract.php';

use Drupal\Tests\ecms_profile\ExistingSite\AllProfileInstallationTestsAbstract;

/**
 * Functional tests for the EcmsSpeechesInstall feature.
 *
 * @package Drupal\Tests\ecms_hotels\ExistingSite
 * @group ecms
 * @group ecms_speeches
 */
class EcmsSpeechesInstallTest extends AllProfileInstallationTestsAbstract {

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
   * Test the ecms_speeches installation.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testEcmsProjectsInstallation(): void {
    $account = $this->createUser([
      'administer modules',
      'administer site configuration',
      'access administration pages',
    ]);
    $this->drupalLogin($account);

    $this->drupalGet('admin/modules');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->checkboxNotChecked('edit-modules-ecms-speeches-enable');

    // Enable the ecms_speeches feature.
    $edit = [];
    $edit["modules[ecms_speeches][enable]"] = TRUE;
    $this->drupalPostForm(NULL, $edit, t('Install'));
    $this->assertSession()->pageTextContainsOnce('Module eCMS Speeches has been enabled.');

  }

}
