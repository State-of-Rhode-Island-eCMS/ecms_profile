<?php

declare(strict_types = 1);

namespace Drupal\Tests\ecms_profile\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Class AllProfileInstallationTestsAbstract
 *
 * This class defines common tests between all profiles and should be extended
 * for each installation test.
 *
 * @package Drupal\Tests\ecms_profile\Functional
 */
abstract class AllProfileInstallationTestsAbstract extends BrowserTestBase {

  /**
   * Test the openid_connect module is installed properly.
   */
//  public function testOpenIDConnect(): void {
//    $account = $this->drupalCreateUser(['administer openid connect clients']);
//    $this->drupalLogin($account);
//
//    // Ensure the settings page is available.
//    $this->drupalGet('admin/config/services/openid-connect');
//    $this->assertSession()->statusCodeEquals(200);
//
//    // Ensure the Generic service is enabled.
//    $this->assertSession()->checkboxChecked('edit-clients-enabled-generic');
//
//    // Ensure no other service is available.
//    $this->assertSession()->checkboxNotChecked('edit-clients-enabled-facebook');
//    $this->assertSession()->checkboxNotChecked('edit-clients-enabled-github');
//    $this->assertSession()->checkboxNotChecked('edit-clients-enabled-google');
//    $this->assertSession()->checkboxNotChecked('edit-clients-enabled-linkedin');
//
//    // Ensure the configuration imported properly.
//    $this->assertSession()->fieldValueEquals('edit-clients-generic-settings-client-id', 'REDACTED');
//    $this->assertSession()->fieldValueEquals('edit-clients-generic-settings-client-secret', 'REDACTED');
//
//    // Ensure the additional settings are selected.
//    $this->assertSession()->checkboxChecked('edit-override-registration-settings');
//    $this->assertSession()->checkboxChecked('edit-always-save-userinfo');
//    $this->assertSession()->checkboxChecked('edit-connect-existing-users');
//    $this->assertSession()->checkboxChecked('edit-user-login-display-replace');
//
//  }

}