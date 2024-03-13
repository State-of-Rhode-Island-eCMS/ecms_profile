<?php

declare(strict_types=1);

namespace Drupal\Tests\ecms_profile\Functional;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AnonymousUserSession;
use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

/**
 * Abstract class handling global testing for the profile.
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

  /**
   * The theme to test with.
   *
   * @var string
   */
  protected $defaultTheme = 'stark';

  /**
   * Whether to strictly check schema on installed configuration.
   *
   * @var bool
   */
  protected $strictConfigSchema = FALSE;

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
   * Override the default drupalLogout method.
   *
   * @see \Drupal\Tests\ecms_profile\ExistingSite\AllProfileInstallationTestsAbstract::drupalLogin()
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   */
  protected function drupalLogout(): void {
    // Make a request to the logout page, and redirect to the user page, the
    // idea being if you were properly logged out you should be seeing a login
    // screen.
    $assert_session = $this->assertSession();
    $destination = Url::fromRoute('user.page')->toString();
    $this->drupalGet(Url::fromRoute('user.logout', [], ['query' => ['destination' => $destination]]));

    // Assert the openid button on logout.
    $assert_session->buttonExists('edit-openid-connect-client-windows-aad-login');

    // @see BrowserTestBase::drupalUserIsLoggedIn()
    unset($this->loggedInUser->sessionId);
    $this->loggedInUser = FALSE;
    \Drupal::currentUser()->setAccount(new AnonymousUserSession());
  }

}
