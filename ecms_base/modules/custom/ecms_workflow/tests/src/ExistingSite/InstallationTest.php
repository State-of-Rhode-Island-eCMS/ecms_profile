<?php

declare(strict_types = 1);

namespace Drupal\Tests\ecms_workflow\ExistingSite;

// Require the all profiles abstract class since autoloading doesn't work.
require_once dirname(__FILE__) . '/../../../../../../../tests/src/ExistingSite/AllProfileInstallationTestsAbstract.php';

use Drupal\Tests\ecms_profile\ExistingSite\AllProfileInstallationTestsAbstract;
use Drupal\user\Entity\Role;
use Drupal\node\Entity\NodeType;
use Drupal\Core\Entity\EntityStorageException;

/**
 * ExistingSite tests for the ecms_workflow module.
 *
 * @package Drupal\Tests\ecms_workflow\ExistingSite
 * @group ecms
 * @group ecms_workflow
 */
class InstallationTest extends AllProfileInstallationTestsAbstract {

  /**
   * The role to create for testing.
   *
   * @var false|string
   */
  private $role;

  /**
   * The user entity to test with.
   *
   * @var \Drupal\user\Entity\User|false
   */
  private $account;

  /**
   * {@inheritDoc}
   */
  public function setUp(): void {
    parent::setUp();

    // Provide the role with known permissions to start.
    $this->role = $this->coreCreateRole([
      'administer permissions',
      'access administration pages',
    ]);

    $this->account = $this->createUser();
    $this->account->addRole($this->role);
    $this->account->save();
  }

  /**
   * Test the ecms_workflow permissions.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testEcmsWorkflowPermissions(): void {
    $this->drupalLogin($this->account);

    // Ensure content types have proper permissions.
    $this->drupalGet('admin/people/permissions');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->checkboxChecked('edit-content-author-create-basic-page-content');
    $this->assertSession()->checkboxChecked('edit-content-publisher-create-basic-page-content');
    $this->assertSession()->checkboxChecked('edit-content-publisher-edit-any-basic-page-content');
    $this->assertSession()->checkboxNotChecked('edit-content-author-edit-any-basic-page-content');

  }

  /**
   * Test the ecms_workflow new content type creation.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   * @throws
   */
  public function testEcmsWorkflowNewContentType(): void {
    $this->drupalLogin($this->account);

    $type = NodeType::create([
      'type' => 'test_content_type',
      'name' => 'Test Content Type',
    ]);

    try {
      $type->save();
    }
    catch (EntityStorageException $e) {
      return;
    }

    // Ensure the new test content type has proper permissions.
    $this->drupalGet('admin/people/permissions');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->checkboxChecked('edit-content-author-create-test-content-type-content');
    $this->assertSession()->checkboxChecked('edit-content-publisher-create-test-content-type-content');
    $this->assertSession()->checkboxChecked('edit-content-publisher-edit-any-test-content-type-content');
    $this->assertSession()->checkboxNotChecked('edit-content-author-edit-any-test-content-type-content');

    // We can remove the test content type now.
    try {
      $type->delete();
    }
    catch (EntityStorageException $e) {
      return;
    }
  }

  /**
   * {@inheritDoc}
   */
  public function tearDown(): void {
    parent::tearDown();

    if (!empty($this->account)) {
      $this->account->delete();
    }

    $role = Role::load($this->role);
    if (!empty($role)) {
      $role->delete();
    }

  }

}
