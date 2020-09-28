<?php

declare(strict_types = 1);

namespace Drupal\Tests\ecms_publications\Functional;

// Require the all profiles abstract class since autoloading doesn't work.
require_once dirname(__FILE__) . '/../../../../../../../tests/src/Functional/AllProfileInstallationTestsAbstract.php';

use Drupal\Tests\ecms_profile\Functional\AllProfileInstallationTestsAbstract;

/**
 * Class InstallationTest.
 *
 * @package Drupal\Tests\ecms_publications\Functional
 * @group ecms
 * @group ecms_publications
 */
class EcmsPublicationsInstallTest extends AllProfileInstallationTestsAbstract {

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
   * Test the ecms_api_recipient installation.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testEcmsPublicationInstallation(): void {
    $account = $this->drupalCreateUser([
      'administer modules',
      'administer site configuration',
      'access administration pages',
    ]);
    $this->drupalLogin($account);

    $this->drupalGet('admin/modules');
    $this->assertSession()->statusCodeEquals(200);

    $this->assertSession()->checkboxNotChecked('edit-modules-ecms-publications-enable');

    // Enable the ecms_publications feature.
    $edit = [];
    $edit["modules[ecms_publications][enable]"] = TRUE;
    $this->drupalPostForm(NULL, $edit, t('Install'));
    $this->assertText('Module eCMS Publications has been enabled.');

  }

}
