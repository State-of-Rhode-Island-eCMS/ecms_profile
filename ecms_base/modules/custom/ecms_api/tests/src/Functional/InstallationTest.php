<?php

declare(strict_types = 1);

namespace Drupal\Tests\ecms_api\Functional;

// Require the all profiles abstract class since autoloading doesn't work.
require_once dirname(__FILE__) . '/../../../../../../../tests/src/Functional/AllProfileInstallationTestsAbstract.php';

use Drupal\Tests\ecms_profile\Functional\AllProfileInstallationTestsAbstract;

/**
 * Tests that installation finished correctly and known resources are available.
 *
 * @group ecms
 * @group ecms_api
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
   * The modules to install.
   *
   * @var string[]
   */
  protected static $modules = ['ecms_api'];

  /**
   * Test the eCMS API configuration settings.
   */
  public function testEcmsApiInstallation(): void {
    $account = $this->drupalCreateUser([
      'administer modules',
      'administer site configuration',
      'administer simple_oauth entities',
      ]);
    $this->drupalLogin($account);

    // Ensure the Json API allows CRUD operations.
    $this->drupalGet('admin/config/services/jsonapi');
    $this->assertSession()->statusCodeEquals(200);

    $this->assertSession()->checkboxChecked('edit-read-only-rw');
    $this->assertSession()->checkboxNotChecked('edit-read-only-r');

    // Ensure the API Path is correct.
    $this->drupalGet('admin/config/services/jsonapi/extras');
    $this->assertSession()->fieldValueEquals('edit-path-prefix', 'EcmsApi');

    // Ensure the simple oauth public/private key values.
    $this->drupalGet('admin/config/people/simple_oauth');
    $this->assertSession()->fieldValueEquals('public_key', '../ecms_api_public.key');
    $this->assertSession()->fieldValueEquals('private_key', '../ecms_api_private.key');
  }

}
