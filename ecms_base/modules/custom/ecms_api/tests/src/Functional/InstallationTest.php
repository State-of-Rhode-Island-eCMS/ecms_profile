<?php

declare(strict_types = 1);

namespace Drupal\Tests\ecms_api\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests that installation finished correctly and known resources are available.
 *
 * @group ecms
 * @group ecms_api
 */
class InstallationTest extends BrowserTestBase {

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
   * Test the API configuration settings.
   */
  public function testEcmsApiInstallation(): void {
    $account = $this->drupalCreateUser(['administer modules']);
    $this->drupalLogin($account);

    $this->drupalGet('admin/config/services/jsonapi');
    $this->assertSession()->statusCodeEquals(200);

    $this->assertSession()->checkboxChecked('edit-read-only-rw');
    $this->assertSession()->checkboxNotChecked('edit-read-only-r');

    $this->drupalGet('admin/config/services/jsonapi/extras');
    $this->assertSession()->fieldValueEquals('edit-path-prefix', 'EcmsApi');
  }

}