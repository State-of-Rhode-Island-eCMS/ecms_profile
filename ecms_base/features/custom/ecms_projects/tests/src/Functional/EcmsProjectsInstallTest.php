<?php

declare(strict_types = 1);

namespace Drupal\Tests\ecms_projects\Functional;

// Require the all profiles abstract class since autoloading doesn't work.
require_once dirname(__FILE__) . '/../../../../../../../tests/src/Functional/AllProfileInstallationTestsAbstract.php';

use Drupal\Tests\ecms_profile\Functional\AllProfileInstallationTestsAbstract;

/**
 * Functional tests for the EcmsProjectsInstall feature.
 *
 * @package Drupal\Tests\ecms_projects\Functional
 * @group ecms
 * @group ecms_projects
 */
class EcmsProjectsInstallTest extends AllProfileInstallationTestsAbstract {

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
  protected $defaultTheme = 'classy';

  /**
   * Test the ecms_projects installation.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testEcmsProjectsInstallation(): void {
    $account = $this->drupalCreateUser([
      'administer modules',
      'administer site configuration',
      'access administration pages',
    ]);
    $this->drupalLogin($account);

    $this->drupalGet('admin/modules');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->checkboxNotChecked('edit-modules-ecms-projects-enable');

    // Enable the ecms_projects feature.
    $edit = [];
    $edit["modules[ecms_projects][enable]"] = TRUE;
    $this->drupalPostForm(NULL, $edit, t('Install'));
    $this->assertText('Module eCMS Projects has been enabled.');

  }

}
