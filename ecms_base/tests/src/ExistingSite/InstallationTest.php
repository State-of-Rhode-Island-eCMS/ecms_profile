<?php

declare(strict_types=1);

namespace Drupal\Tests\ecms_base\ExistingSite;

// Require the all profiles abstract class since autoloading doesn't work.
require_once dirname(__FILE__) . '/../../../../tests/src/ExistingSite/AllProfileInstallationTestsAbstract.php';

use Drupal\Tests\ecms_profile\ExistingSite\AllProfileInstallationTestsAbstract;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests that installation finished correctly and known resources are available.
 *
 */
#[Group("ecms_base")]
#[Group("ecms")]
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
  protected $defaultTheme = 'ecms';

  /**
   * Disable failure for watchdog messages.
   *
   * @var bool
   */
  protected $failOnPhpWatchdogMessages = FALSE;

  /**
    * Test the openid_connect module is installed properly.
    */
  public function testOpenIdConnect(): void {
    $this->ensureOpenIdConnectTest();
  }

  /**
   * Test whether the ecms_notification feature installed properly.
   */
  public function testNotifications(): void {
    $this->ensureNotificationFeatureInstalled();
  }

  /**
   * Test whether the ecms_press_release feature installed properly.
   */
  public function testPressReleaseInstalled(): void {
    $this->ensurePressReleaseFeatureInstalled();
  }

  /**
   * Test whether the ecms_person feature installed properly.
   */
  public function testPersonPFeatureInstalled(): void {
    $this->ensurePersonFeatureInstalled();
  }

  /**
   * Test whether the ecms_location feature installed properly.
   */
  public function testLocationFeatureInstalled(): void {
    $this->ensureLocationFeatureInstalled();
  }

  /**
   * Test whether the ecms_webform feature installed properly.
   */
  public function testEnsureWebformInstall(): void {
    $this->ensureWebformInstall();
  }

  /**
   * Test whether the ecms_publish_content feature installed properly.
   */
  public function testEnsurePublishContentInstalled(): void {
    $this->ensurePublishContentInstalled();
  }

  /**
   * Test whether the ecms_moderation_notification feature installed properly.
   */
  public function testEnsureModerationNotificationInstall(): void {
    $this->ensureModerationNotificationInstall();
  }

  /**
   * Test whether the ecms_moderation_dashboard feature installed properly.
   */
  public function testEnsureModerationDashboardInstall(): void {
    $this->ensureModerationDashboardInstall();
  }

  /**
   * Test whether the ecms_event feature installed properly.
   */
  public function testEnsureEventFeatureInstalled(): void {
    $this->ensureEventFeatureInstalled();
  }

  /**
   * Test whether the ecms_promotions feature installed properly.
   */
  public function testEnsurePromotionsFeatureInstalled(): void {
    $this->ensurePromotionsFeatureInstalled();
  }

  /**
   * Test whether the ecms_basic_page feature installed properly.
   */
  public function testEnsureBasicPageFeatureInstalled(): void {
    $this->ensureBasicPageFeatureInstalled();
  }

  /**
   * Test whether the ecms_landing_page feature installed properly.
   */
  public function testEnsureLandingPageFeatureInstalled(): void {
    $this->ensureLandingPageFeatureInstalled();
  }

  /**
   * Test that the ACSF modules are installed.
   */
  public function testEnsureAcsfModulesEnabled(): void {
    $account = $this->createUser(['administer modules']);
    $this->drupalLogin($account);

    $this->drupalGet('admin/modules');
    $this->assertSession()->statusCodeEquals(200);

    $this->assertSession()->checkboxChecked('edit-modules-acsf-enable');
    $this->assertSession()->checkboxChecked('edit-modules-acsf-duplication-enable');
    $this->assertSession()->checkboxChecked('edit-modules-acsf-theme-enable');
    $this->assertSession()->checkboxChecked('edit-modules-acsf-variables-enable');
    $this->drupalLogout();
  }

}
