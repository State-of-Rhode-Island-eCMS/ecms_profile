<?php

declare(strict_types = 1);

namespace Drupal\Tests\ecms_profile\Functional;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\SchemaCheckTestTrait;

/**
 * Class AllProfileInstallationTestsAbstract.
 *
 * This class defines common tests between all profiles and should be extended
 * for installation testing. Autoloading doesn't work, so it will need to be
 * required with:
 * ```
 * require_once dirname(__FILE__)
 *   . '/../tests/src/Functional/AllProfileInstallationTestsAbstract.php';
 * ```
 *
 * @package Drupal\Tests\ecms_profile\Functional
 */
abstract class AllProfileInstallationTestsAbstract extends BrowserTestBase {

  use SchemaCheckTestTrait;

  /**
   * Override the default drupalLogin method.
   *
   * The openid_connect modules is configured to alter the user.login route and
   * remove the standard drupal login form. This method updates the route
   * to use the ?showcore query parameter, bypassing the OIDC restriction.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The drupal account interface to login with.
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
   * Combine all the private tests into one method so they all get run under
   * a single Drupal installation.
   */
  public function globalTests(): void {
    $this->testOpenIdConnect();
    $this->testNotificationFeatureInstalled();
    $this->testConfigInstall();
  }

  /**
   * Test the openid_connect module is installed properly.
   */
  private function testOpenIdConnect(): void {
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
    $this->drupalLogout();
  }

  /**
   * Test whether the ecms_notification feature installed properly.
   */
  private function testNotificationFeatureInstalled(): void {
    $account = $this->drupalCreateUser(['create notification content']);
    $this->drupalLogin($account);

    // Ensure the notification entity add formis available.
    $this->drupalGet('node/add/notification');
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalLogout();
  }

  /**
   * Ensure the configuration installed properly.
   */
  private function testConfigInstall(): void {
    // Ensure all configuration imported.
    $names = $this->container->get('config.storage')->listAll();
    /** @var \Drupal\Core\Config\TypedConfigManagerInterface $typed_config */
    $typed_config = $this->container->get('config.typed');
    foreach ($names as $name) {
      $config = $this->config($name);
      $this->assertConfigSchema($typed_config, $name, $config->get());
    }

  }

  /**
   * Ensure the webform requirement installed properly.
   */
  public function testWebformInstall(): void {
    $account = $this->drupalCreateUser(['administer webform']);
    $this->drupalLogin($account);

    // Ensure the form configuration page is available.
    $this->drupalGet('admin/structure/webform');
    $this->assertSession()->statusCodeEquals(200);

  }

}
