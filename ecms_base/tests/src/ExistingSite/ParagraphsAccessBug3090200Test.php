<?php

declare(strict_types=1);

namespace Drupal\Tests\ecms_base\ExistingSite;

// Require the all profiles abstract class since autoloading doesn't work.
require_once dirname(__FILE__) . '/../../../../tests/src/ExistingSite/AllProfileInstallationTestsAbstract.php';

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\Tests\ecms_profile\ExistingSite\AllProfileInstallationTestsAbstract;
use Drupal\user\Entity\Role;
use PHPUnit\Framework\Attributes\Group;

/**
 * Regression test for drupal/paragraphs issue #3090200.
 *
 * Asserts that ParagraphAccessControlHandler::checkAccess() grants view access
 * to a paragraph that is referenced by a published parent revision, even when
 * the parent's DEFAULT revision is archived (unpublished).
 *
 * Background: the unpatched handler calls $paragraph->getParentEntity(), which
 * resolves to the DEFAULT revision via EntityStorageInterface::load(). Under
 * the editorial workflow, archiving a node creates a new unpublished default
 * revision, causing checkAccess() to return neutral() instead of allowed() for
 * paragraphs that belong to the earlier published revision.
 *
 * The fix (drupal.org/files/issues/2026-07-06/
 * paragraphs-parent-access-block-content-skip-3090200-97.patch) introduces
 * resolveAccessHost(), which queries the database for the specific parent
 * revision that references the paragraph's revision ID, so the published
 * revision is used for the access check regardless of what the default revision
 * is.
 *
 * Scenario built from site configuration:
 *   - node type:      press_release  (ecms_press_release feature)
 *   - paragraph type: formatted_text (ecms_paragraphs feature)
 *   - workflow:       editorial      (ecms_workflow module, archived state)
 *
 * Why checkAccess() is called via reflection:
 *   The paragraphs_type_permissions submodule implements
 *   hook_ENTITY_TYPE_access() for paragraphs, returning AccessResult::allowed()
 *   or ::forbidden() based solely on per-type permissions. Because
 *   EntityAccessControlHandler::access() combines hook results with
 *   checkAccess() via orIf(), an allowed() from the hook masks any neutral()
 *   returned by the buggy checkAccess(). Calling checkAccess() directly via
 *   reflection isolates the logic the patch changes.
 *
 * @see https://www.drupal.org/project/paragraphs/issues/3090200
 * @see \Drupal\paragraphs\ParagraphAccessControlHandler::checkAccess()
 *
 * @package Drupal\Tests\ecms_base\ExistingSite
 */
#[Group('ecms')]
#[Group('ecms_paragraphs')]
#[Group('ecms_press_release')]
class ParagraphsAccessBug3090200Test extends AllProfileInstallationTestsAbstract {

  /**
   * The formatted_text paragraph created for the initial published node.
   *
   * @var \Drupal\paragraphs\Entity\Paragraph|null
   */
  private ?Paragraph $paragraph = NULL;

  /**
   * The revision ID of $paragraph after the parent node was first published.
   *
   * Stored after the first node save because entity_reference_revisions may
   * create a new paragraph revision when the parent entity is saved.
   *
   * @var int|null
   */
  private ?int $paragraphRevisionId = NULL;

  /**
   * The press_release node created for the test.
   *
   * @var \Drupal\node\Entity\Node|null
   */
  private ?Node $node = NULL;

  /**
   * The node revision ID of the published revision (version 1).
   *
   * Used to assert that this revision is accessible and to verify
   * resolveAccessHost() returns it after the fix is applied.
   *
   * @var int|null
   */
  private ?int $publishedNodeRevisionId = NULL;

  /**
   * The second formatted_text paragraph used only in the archived node.
   *
   * @var \Drupal\paragraphs\Entity\Paragraph|null
   */
  private ?Paragraph $archivedParagraph = NULL;

  /**
   * A restricted user with only 'access content'.
   *
   * @var \Drupal\Core\Session\AccountInterface|null
   */
  private ?AccountInterface $account = NULL;

  /**
   * The machine name of the role assigned to the restricted user.
   *
   * @var string|false
   */
  private string|false $role = FALSE;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    // --- Step 1: Create the initial formatted_text paragraph. ---
    // This paragraph will be embedded in the published revision of the node.
    $paragraph = Paragraph::create([
      'type' => 'formatted_text',
    ]);
    $paragraph->save();
    $this->paragraph = $paragraph;

    // --- Step 2: Create a press_release node, PUBLISHED, containing the
    // paragraph.  The editorial workflow sets default_revision = TRUE and
    // status = TRUE for the "published" moderation state. ---
    $node = Node::create([
      'type' => 'press_release',
      'title' => 'Bug 3090200 test node',
      'moderation_state' => 'published',
      'field_press_release_paragraphs' => [
        [
          'target_id' => $paragraph->id(),
          'target_revision_id' => $paragraph->getRevisionId(),
        ],
      ],
    ]);
    $node->save();
    $this->node = $node;
    $this->publishedNodeRevisionId = (int) $node->getRevisionId();

    // Reload the paragraph so we get the current revision ID.
    // entity_reference_revisions may have created a new paragraph revision
    // when the parent node was saved (to record the parent_id / parent_type
    // fields), so we capture the revision ID *after* the node save.
    $this->paragraph = Paragraph::load($paragraph->id());
    $this->paragraphRevisionId = (int) $this->paragraph->getRevisionId();

    // --- Step 3: Create a second paragraph used only in the archived node. ---
    $archivedParagraph = Paragraph::create([
      'type' => 'formatted_text',
    ]);
    $archivedParagraph->save();
    $this->archivedParagraph = $archivedParagraph;

    // --- Step 4: Archive the node.  This creates a NEW default revision
    // whose moderation_state is "archived" (status = FALSE, default = TRUE).
    // The archived revision references $archivedParagraph, not $paragraph. ---
    $node->setNewRevision(TRUE);
    $node->set('moderation_state', 'archived');
    $node->set('field_press_release_paragraphs', [
      [
        'target_id' => $archivedParagraph->id(),
        'target_revision_id' => $archivedParagraph->getRevisionId(),
      ],
    ]);
    $node->save();
    // Node::load($node->id()) now returns the archived (unpublished) default.

    // --- Step 5: Restricted user (no paragraph-type or unpublished perms). ---
    $this->role = $this->coreCreateRole(['access content']);
    $this->account = $this->createUser();
    $this->account->addRole($this->role);
    $this->account->save();
  }

  // ---------------------------------------------------------------------------
  // Sanity checks — these should always pass.
  // ---------------------------------------------------------------------------

  /**
   * Confirms that the default node revision is now the archived (unpublished)
   * one.  This is the pre-condition that triggers bug #3090200.
   */
  public function testDefaultNodeRevisionIsArchivedAndUnpublished(): void {
    $defaultNode = Node::load($this->node->id());
    $this->assertNotNull($defaultNode);
    $this->assertFalse(
      $defaultNode->isPublished(),
      'The default revision of the node must be unpublished (archived state).'
    );
    $this->assertNotEquals(
      $this->publishedNodeRevisionId,
      (int) $defaultNode->getRevisionId(),
      'The default revision ID must differ from the published revision ID.'
    );
  }

  /**
   * Confirms that the published node revision still references the paragraph.
   *
   * This validates the test setup: resolveAccessHost() (in the fix) must be
   * able to find the published revision via the paragraph's revision ID.
   */
  public function testPublishedRevisionReferencesParagraph(): void {
    /** @var \Drupal\node\Entity\Node $publishedRevision */
    $publishedRevision = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->loadRevision($this->publishedNodeRevisionId);

    $this->assertNotNull($publishedRevision);
    $this->assertTrue(
      $publishedRevision->isPublished(),
      'The stored published revision must report itself as published.'
    );

    $referencedRevisionIds = array_column(
      $publishedRevision->get('field_press_release_paragraphs')->getValue(),
      'target_revision_id'
    );
    $this->assertContains(
      (string) $this->paragraphRevisionId,
      $referencedRevisionIds,
      'The published node revision must reference the paragraph revision ID '
      . 'stored in setUp().'
    );
  }

  /**
   * Helper to invoke ParagraphAccessControlHandler::checkAccess() directly.
   *
   * Bypasses hook_ENTITY_TYPE_access() implementations (notably
   * paragraphs_type_permissions) so that only the handler's own logic is
   * tested.
   *
   * @param \Drupal\paragraphs\Entity\Paragraph $paragraph
   *   The paragraph entity to check.
   * @param string $operation
   *   The operation ('view', 'update', 'delete').
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account to check against.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The raw checkAccess() result before any hooks are applied.
   */
  private function invokeCheckAccess(
    Paragraph $paragraph,
    string $operation,
    AccountInterface $account
  ): AccessResult {
    $handler = \Drupal::entityTypeManager()
      ->getAccessControlHandler('paragraph');
    $method = new \ReflectionMethod($handler, 'checkAccess');
    $method->setAccessible(TRUE);
    return $method->invoke($handler, $paragraph, $operation, $account);
  }

  /**
   * Regression test for bug #3090200.
   *
   * The fix's resolveAccessHost() queries the database for the specific parent
   * revision that references this paragraph's revision ID.  That revision is
   * the published one, so checkAccess() must return allowed() even though
   * getParentEntity() (default-revision load) returns the archived, unpublished
   * revision.
   *
   * @see https://www.drupal.org/project/paragraphs/issues/3090200
   */
  public function testCheckAccessAllowsViewWhenDefaultParentIsUnpublished(): void {
    $paragraph = \Drupal::entityTypeManager()
      ->getStorage('paragraph')
      ->loadRevision($this->paragraphRevisionId);
    $this->assertNotNull($paragraph);

    // Confirm the scenario: getParentEntity() still returns the unpublished
    // default revision, which is what triggered the original bug.
    $defaultParent = $paragraph->getParentEntity();
    $this->assertNotNull($defaultParent);
    $this->assertFalse(
      $defaultParent->isPublished(),
      'getParentEntity() must return the unpublished default revision to '
      . 'confirm the bug scenario is active.'
    );

    // Despite the unpublished default parent, checkAccess() must return
    // allowed() because resolveAccessHost() locates the published revision.
    $result = $this->invokeCheckAccess($paragraph, 'view', $this->account);
    $this->assertTrue(
      $result->isAllowed(),
      'ParagraphAccessControlHandler::checkAccess() must return allowed() for '
      . 'a paragraph referenced by a published parent revision, even when the '
      . 'parent\'s DEFAULT revision is archived/unpublished (#3090200).'
    );
  }

  // ---------------------------------------------------------------------------
  // tearDown
  // ---------------------------------------------------------------------------

  /**
   * {@inheritdoc}
   */
  public function tearDown(): void {
    parent::tearDown();

    if ($this->account !== NULL) {
      try {
        $this->account->delete();
      }
      catch (\Exception $e) {}
      $this->account = NULL;
    }

    if ($this->role !== FALSE) {
      $role = Role::load($this->role);
      if ($role !== NULL) {
        $role->delete();
      }
      $this->role = FALSE;
    }

    // Deleting the node also deletes its revisions and inline entities.
    if ($this->node !== NULL) {
      try {
        $node = \Drupal::entityTypeManager()
          ->getStorage('node')
          ->load($this->node->id());
        if ($node !== NULL) {
          $node->delete();
        }
      }
      catch (\Exception $e) {}
      $this->node = NULL;
    }

    foreach (['paragraph', 'archivedParagraph'] as $prop) {
      if ($this->$prop !== NULL) {
        try {
          $entity = \Drupal::entityTypeManager()
            ->getStorage('paragraph')
            ->load($this->$prop->id());
          if ($entity !== NULL) {
            $entity->delete();
          }
        }
        catch (\Exception $e) {}
        $this->$prop = NULL;
      }
    }

    $this->paragraphRevisionId = NULL;
    $this->publishedNodeRevisionId = NULL;
  }

}
