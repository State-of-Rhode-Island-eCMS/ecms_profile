<?php

declare(strict_types = 1);

namespace Drupal\Tests\ecms_profile\Functional;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
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
   * Override the default drupalLogin.
   *
   * The openid_connect modules is configured to disable the user/login route.
   * This method updates the route supplied to use the ?showcore
   * query parameter, bypassing the OIDC restriction.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   */
  protected function drupalLogin(AccountInterface $account): void {
    if ($this->loggedInUser) {
      $this->drupalLogout();
    }

    $this->drupalGet(Url::fromRoute('user.login', [], ['query' => ['showcore' => 1]]));
    $this->submitForm([
      'name' => $account->getAccountName(),
      'pass' => $account->passRaw,
    ], t('Log in'));

    // @see ::drupalUserIsLoggedIn()
    $account->sessionId = $this->getSession()->getCookie(\Drupal::service('session_configuration')->getOptions(\Drupal::request())['name']);
    $markup = new FormattableMarkup('User %name successfully logged in.', ['%name' => $account->getAccountName()]);
    $this->assertTrue($this->drupalUserIsLoggedIn($account), $markup->__toString());

    $this->loggedInUser = $account;
    $this->container->get('current_user')->setAccount($account);
  }

  /**
   * Test the openid_connect module is installed properly.
   */
  public function testOpenIDConnect(): void {
    $this->drupalGet('user/login');
    $this->assertSession()->buttonExists('edit-openid-connect-client-generic-login');
    $this->assertSession()->fieldNotExists('name');
    $this->assertSession()->fieldNotExists('pass');

    $account = $this->drupalCreateUser(['administer openid connect clients']);
    $this->drupalLogin($account);

    // Ensure the settings page is available.
    $this->drupalGet('admin/config/services/openid-connect');
    $this->assertSession()->statusCodeEquals(200);

    // Ensure the Generic service is enabled.
    $this->assertSession()->checkboxChecked('edit-clients-enabled-generic');

    // Ensure no other service is available.
    $this->assertSession()->checkboxNotChecked('edit-clients-enabled-facebook');
    $this->assertSession()->checkboxNotChecked('edit-clients-enabled-github');
    $this->assertSession()->checkboxNotChecked('edit-clients-enabled-google');
    $this->assertSession()->checkboxNotChecked('edit-clients-enabled-linkedin');

    // Ensure the configuration imported properly.
    $this->assertSession()->fieldValueEquals('edit-clients-generic-settings-client-id', 'REDACTED');
    $this->assertSession()->fieldValueEquals('edit-clients-generic-settings-client-secret', 'REDACTED');

    // Ensure the additional settings are selected.
    $this->assertSession()->checkboxChecked('edit-override-registration-settings');
    $this->assertSession()->checkboxChecked('edit-always-save-userinfo');
    $this->assertSession()->checkboxChecked('edit-connect-existing-users');
    $this->assertSession()->checkboxChecked('edit-user-login-display-replace');

  }

}