<?php

declare(strict_types = 1);

namespace Drupal\Tests\ecms_profile\Functional;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AnonymousUserSession;
use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

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

  /**
   * The default installed languages.
   */
  const DEFAULT_INSTALLED_LANGUAGES = [
    'en',
    'pt-pt',
    'es',
  ];

  /**
   * The default installated content types.
   */
  const DEFAULT_INSTALLED_CONTENT_TYPES = [
    'basic_page',
    'event',
    'landing_page',
    'location',
    'notification',
    'person',
    'press_release',
    'promotions',
  ];

  /**
   * The theme to test with.
   *
   * @var string
   */
  protected $defaultTheme = 'ecms';

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
   * @see \Drupal\Tests\ecms_profile\Functional\AllProfileInstallationTestsAbstract::drupalLogin()
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

  /**
   * Combine all the private tests into one method.
   *
   * This will combine all tests into one to keep the tests in one Drupal
   * installation. Otherwise, each test function re-installs Drupal.
   *
   * Tests in extending classes should call the $this->globalTests() to
   * include these tests in their profile tests.
   */
  public function globalTests(): void {
    $this->ensureOpenIdConnect();
    $this->ensureNotificationFeatureInstalled();
    $this->ensurePressReleaseFeatureInstalled();
    $this->ensurePersonFeatureInstalled();
    $this->ensureLocationFeatureInstalled();
    $this->ensureWebformInstall();
    $this->ensurePublishContentInstalled();
    $this->ensureEventFeatureInstalled();
    $this->ensurePromotionsFeatureInstalled();
    $this->ensureBasicPageFeatureInstalled();
    $this->ensureLandingPageFeatureInstalled();
    $this->ensureModerationNotificationInstall();
    $this->ensureModerationDashboardInstall();
    $this->ensureLanguagesInstalled();
  }

  /**
   * Test the openid_connect module is installed properly.
   */
  private function ensureOpenIdConnect(): void {
    $this->drupalGet('user/login');
    $this->assertSession()->buttonExists('edit-openid-connect-client-windows-aad-login');
    $this->assertSession()->fieldNotExists('name');
    $this->assertSession()->fieldNotExists('pass');

    $account = $this->drupalCreateUser(['administer openid connect clients']);
    $this->drupalLogin($account);

    // Ensure the settings page is available.
    $this->drupalGet('admin/config/services/openid-connect');
    $this->assertSession()->statusCodeEquals(200);

    // Ensure the Windows AAD service is enabled.
    $this->assertSession()->checkboxChecked('edit-clients-enabled-windows-aad');

    // Ensure no other service is available.
    $this->assertSession()->checkboxNotChecked('edit-clients-enabled-facebook');
    $this->assertSession()->checkboxNotChecked('edit-clients-enabled-github');
    $this->assertSession()->checkboxNotChecked('edit-clients-enabled-generic');
    $this->assertSession()->checkboxNotChecked('edit-clients-enabled-google');
    $this->assertSession()->checkboxNotChecked('edit-clients-enabled-linkedin');

    // Ensure the configuration imported properly.
    $this->assertSession()->fieldValueEquals('edit-clients-windows-aad-settings-client-id', 'REDACTED');
    $this->assertSession()->fieldValueEquals('edit-clients-windows-aad-settings-client-secret', 'REDACTED');

    // Ensure the additional settings are selected.
    $this->assertSession()->checkboxChecked('edit-override-registration-settings');
    $this->assertSession()->checkboxChecked('edit-always-save-userinfo');
    $this->assertSession()->checkboxChecked('edit-connect-existing-users');
    $this->assertSession()->checkboxChecked('edit-user-login-display-replace');

    $this->drupalLogout();
  }

  /**
   * Ensure the languages are installed correctly.
   */
  private function ensureLanguagesInstalled(): void {
    $account = $this->drupalCreateUser(['administer languages', 'administer content']);
    $this->drupalLogin($account);

    // Ensure the default languages are available.
    $this->drupalGet('admin/config/regional/language');
    $this->assertSession()->statusCodeEquals(200);
    foreach (self::DEFAULT_INSTALLED_LANGUAGES as $key => $langcode) {
      $field = "edit-site-default-language-{$langcode}";
      $this->assertSession()->fieldExists($field);

      if ($key === 0) {
        $this->assertSession()->checkboxChecked($field);
      }
    }

    // Ensure the content types have the language options available.
    foreach (self::DEFAULT_INSTALLED_CONTENT_TYPES as $type) {
      $this->drupalGet("node/add/{$type}");
      $this->assertSession()->statusCodeEquals(200);

      // Ensure the language selection exists on the node form.
      $this->assertSession()->fieldExists('edit-langcode-0-value');
      // Ensure the default languages exist on the node form.
      foreach (self::DEFAULT_INSTALLED_LANGUAGES as $langcode) {
        $this->assertSession()->optionExists('edit-langcode-0-value', $langcode);
      }
    }

    $this->drupalLogout();
  }

  /**
   * Test whether the ecms_notification feature installed properly.
   */
  private function ensureNotificationFeatureInstalled(): void {
    $account = $this->drupalCreateUser(['create notification content']);
    $this->drupalLogin($account);

    // Ensure the notification entity add form is available.
    $this->drupalGet('node/add/notification');
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalLogout();
  }

  /**
   * Test whether the ecms_press_release feature installed properly.
   */
  private function ensurePressReleaseFeatureInstalled(): void {
    $account = $this->drupalCreateUser([
      'create press_release content',
      'use editorial transition create_new_draft',
      'view own unpublished content',
    ]);
    $this->drupalLogin($account);

    // Ensure the press release entity add form is available.
    $this->drupalGet('node/add/press_release');
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalLogout();
  }

  /**
   * Test whether the ecms_location feature installed properly.
   */
  private function ensureLocationFeatureInstalled(): void {
    $account = $this->drupalCreateUser([
      'create location content',
      'use editorial transition create_new_draft',
      'view own unpublished content',
    ]);
    $this->drupalLogin($account);

    // Ensure the location entity add form is available.
    $this->drupalGet('node/add/location');
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalLogout();
  }

  /**
   * Test whether the ecms_person feature installed properly.
   */
  private function ensurePersonFeatureInstalled(): void {
    $account = $this->drupalCreateUser([
      'create person content',
      'create terms in person_taxonomy',
      'use editorial transition create_new_draft',
      'view own unpublished content',
    ]);
    $this->drupalLogin($account);

    // Ensure the notification entity add formis available.
    $this->drupalGet('node/add/person');
    $this->assertSession()->statusCodeEquals(200);

    $fields = [
      'field_person_first_name[0][value]' => 'Test',
      'field_person_last_name[0][value]' => 'User',
      'field_person_job_title[0][value]' => 'Developer',
    ];

    // Check that the required fields exist in the form.
    foreach ($fields as $key => $value) {
      $this->assertFieldByName($key);
    }

    $this->drupalPostForm('node/add/person', $fields, 'Save');

    // Ensure the auto entity label tokens were applied.
    $this->assertText('Person Test User has been created.');

    // Ensure the taxonomy is accessible.
    $this->drupalGet('admin/structure/taxonomy/manage/person_taxonomy/add');
    $this->assertSession()->statusCodeEquals(200);

    $this->drupalLogout();
  }

  /**
   * Ensure the webform requirement installed properly.
   */
  private function ensureWebformInstall(): void {
    $account = $this->drupalCreateUser(['administer webform']);
    $this->drupalLogin($account);

    // Ensure the form configuration page is available.
    $this->drupalGet('admin/structure/webform');
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Ensure Publish Content module installed properly.
   */
  private function ensurePublishContentInstalled(): void {
    $account = $this->drupalCreateUser(['administer permissions']);
    $this->drupalLogin($account);

    // Ensure the permissions exist and roles are assigned.
    $this->drupalGet('admin/people/permissions');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->checkboxChecked('edit-site-admin-unpublish-any-content');
    $this->drupalLogout();
  }

  /**
   * Ensure the content moderation notification requirement installed properly.
   */
  private function ensureModerationNotificationInstall(): void {
    $account = $this->drupalCreateUser(['administer content moderation notifications']);
    $this->drupalLogin($account);

    // Ensure the form configuration page is available.
    $this->drupalGet('admin/config/workflow/notifications');
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Ensure the moderation dashboard requirement installed properly.
   */
  private function ensureModerationDashboardInstall(): void {
    $account = $this->drupalCreateUser(['view any moderation dashboard']);
    $this->drupalLogin($account);

    // Ensure the dashboard loads.
    $this->drupalGet("user/{$account->id()}/moderation/dashboard");
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Test whether the ecms_event feature installed properly.
   */
  private function ensureEventFeatureInstalled(): void {
    $account = $this->drupalCreateUser([
      'create event content',
      'create terms in event_taxonomy',
      'use editorial transition create_new_draft',
      'view own unpublished content',
    ]);
    $this->drupalLogin($account);

    // Ensure the event entity add form is available.
    $this->drupalGet('node/add/event');
    $this->assertSession()->statusCodeEquals(200);

    $fields = [
      'title[0][value]' => 'Test event',
      'field_event_date[0][value][date]' => '1980-11-09',
      'field_event_date[0][end_value][date]' => '1980-11-09',
      'field_event_date[0][value][time]' => '14:44:44',
      'field_event_date[0][end_value][time]' => '15:44:44',
    ];

    // Check that the required fields exist in the form.
    foreach ($fields as $key => $value) {
      $this->assertFieldByName($key);
    }

    $this->drupalPostForm('node/add/event', $fields, 'Save');

    // Ensure the auto entity label tokens were applied.
    $this->assertText('Event Test event has been created.');

    // Ensure the taxonomy is accessible.
    $this->drupalGet('admin/structure/taxonomy/manage/event_taxonomy/add');
    $this->assertSession()->statusCodeEquals(200);

    $this->drupalLogout();
  }

  /**
   * Test whether the ecms_promotions feature installed properly.
   */
  private function ensurePromotionsFeatureInstalled(): void {
    $account = $this->drupalCreateUser([
      'create promotions content',
      'use editorial transition create_new_draft',
      'view own unpublished content',
    ]);
    $this->drupalLogin($account);

    // Ensure the promotions entity add form is available.
    $this->drupalGet('node/add/promotions');
    $this->assertSession()->statusCodeEquals(200);

    $this->drupalLogout();
  }

  /**
   * Test whether the ecms_basic_page feature installed properly.
   */
  private function ensureBasicPageFeatureInstalled(): void {
    $account = $this->drupalCreateUser([
      'create basic_page content',
      'use editorial transition create_new_draft',
      'view own unpublished content',
    ]);
    $this->drupalLogin($account);

    // Ensure the basic_page entity add form is available.
    $this->drupalGet('node/add/basic_page');
    $this->assertSession()->statusCodeEquals(200);

    $this->drupalLogout();
  }

  /**
   * Test whether the ecms_landing_page feature installed properly.
   */
  private function ensureLandingPageFeatureInstalled(): void {
    $account = $this->drupalCreateUser([
      'create landing_page content',
    ]);
    $this->drupalLogin($account);

    // Ensure the landing page entity add form is available.
    $this->drupalGet('node/add/landing_page');
    $this->assertSession()->statusCodeEquals(200);

    $this->drupalLogout();
  }

}
