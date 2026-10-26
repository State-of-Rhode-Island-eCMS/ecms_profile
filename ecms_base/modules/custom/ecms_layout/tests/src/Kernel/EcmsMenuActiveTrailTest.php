<?php

declare(strict_types=1);

namespace Drupal\Tests\ecms_layout\Kernel;

use Drupal\Core\Menu\MenuActiveTrail;
use Drupal\Core\Menu\MenuActiveTrailInterface;
use Drupal\Core\Routing\RouteObjectInterface;
use Drupal\ecms_layout\EcmsMenuActiveTrail;
use Drupal\KernelTests\KernelTestBase;
use Drupal\menu_link_content\Entity\MenuLinkContent;
use Drupal\menu_link_content\MenuLinkContentInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

/**
 * Tests that a disabled menu link can still be the active link.
 *
 * The menu set up here mirrors the RIEMA and RIDOH per-section sidebar menus:
 * a section is a disabled top-level link in the main menu, keeping it out of
 * the primary navigation, with its pages as enabled children. A second,
 * enabled shortcut link to the section's landing page sits under a visible
 * top-level parent.
 *
 * @see \Drupal\ecms_layout\EcmsMenuActiveTrail
 * @see https://thinkoomph.jira.com/browse/RIGA-917
 */
#[Group('ecms_layout')]
#[CoversClass(EcmsMenuActiveTrail::class)]
class EcmsMenuActiveTrailTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'link',
    'menu_link_content',
    'block',
    'contextual',
    'layout_discovery',
    'layout_builder',
    'ecms_layout',
  ];

  /**
   * The route every page in these tests is rendered on.
   */
  private const ROUTE_NAME = 'entity.user.canonical';

  /**
   * The disabled top-level section link.
   */
  private MenuLinkContentInterface $section;

  /**
   * The enabled shortcut to the section landing page, under a visible parent.
   */
  private MenuLinkContentInterface $shortcut;

  /**
   * The visible top-level parent of the shortcut.
   */
  private MenuLinkContentInterface $visibleParent;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('menu_link_content');
    $this->installConfig(['system']);

    // The section landing page is user/1. Its section link is disabled and
    // owns the section's pages.
    $this->section = $this->createLink('Section', 'entity:user/1', FALSE, -50);
    $this->createLink('Section page', 'entity:user/2', TRUE, 0, $this->section);

    // The visible parent links to the same landing page through an enabled
    // shortcut one level down.
    $this->visibleParent = $this->createLink('About', 'entity:user/3', TRUE, -60);
    $this->shortcut = $this->createLink('Section shortcut', 'entity:user/1', TRUE, 0, $this->visibleParent);
  }

  /**
   * The decorator replaces core's active trail service.
   */
  public function testDecoratorReplacesCoreActiveTrail(): void {
    $this->assertInstanceOf(
      EcmsMenuActiveTrail::class,
      $this->container->get('menu.active_trail'),
      'ecms_layout.menu_active_trail must decorate menu.active_trail. Without ' .
      'the decoration, section landing pages resolve to their enabled ' .
      'shortcut and lose their sidebar menu.'
    );
  }

  /**
   * The disabled section link wins over the deeper enabled shortcut.
   */
  public function testDisabledSectionLinkIsTheActiveLink(): void {
    $this->setCurrentRoute(['user' => '1']);

    $active_link = $this->activeTrail()->getActiveLink('main');

    $this->assertNotNull($active_link);
    $this->assertSame($this->pluginId($this->section), $active_link->getPluginId());
    $this->assertFalse($active_link->isEnabled());
  }

  /**
   * The active trail is the section, so a level 2 menu block lists its pages.
   */
  public function testActiveTrailIsRootedAtTheSection(): void {
    $this->setCurrentRoute(['user' => '1']);

    $this->assertSame(
      [$this->pluginId($this->section), ''],
      array_keys($this->activeTrail()->getActiveTrailIds('main'))
    );
  }

  /**
   * Documents the core behaviour the decorator exists to prevent.
   *
   * If this test starts failing, core went back to selecting disabled links
   * and the decorator may no longer be needed. The assertions above would
   * then pass trivially, so this test is what keeps them meaningful.
   */
  public function testCoreActiveTrailSkipsTheDisabledSectionLink(): void {
    $this->setCurrentRoute(['user' => '1']);

    $core_trail = new MenuActiveTrail(
      $this->container->get('plugin.manager.menu.link'),
      $this->container->get('current_route_match'),
      $this->container->get('cache.menu'),
      $this->container->get('lock'),
      $this->container->get('path.matcher'),
    );

    $active_link = $core_trail->getActiveLink('main');

    $this->assertNotNull($active_link);
    $this->assertSame(
      $this->pluginId($this->shortcut),
      $active_link->getPluginId(),
      'Since Drupal 11.4, core returns the first enabled link, which is the ' .
      'shortcut rather than the section.'
    );
  }

  /**
   * Among links at the same depth, the lighter one wins, enabled or not.
   */
  public function testLinksAtTheSameDepthAreOrderedByWeight(): void {
    $disabled = $this->createLink('Disabled', 'entity:user/4', FALSE, -10);
    $this->createLink('Enabled', 'entity:user/4', TRUE, 0);
    $this->setCurrentRoute(['user' => '4']);

    $this->assertSame(
      $this->pluginId($disabled),
      $this->activeTrail()->getActiveLink('main')->getPluginId()
    );
  }

  /**
   * A page with a single enabled link resolves to it, as in core.
   */
  public function testSingleEnabledLinkIsTheActiveLink(): void {
    $this->setCurrentRoute(['user' => '2']);

    $active_link = $this->activeTrail()->getActiveLink('main');

    $this->assertNotNull($active_link);
    $this->assertSame('Section page', $active_link->getTitle());
    $this->assertSame(
      ['Section page', 'Section'],
      $this->trailTitles('main')
    );
  }

  /**
   * A page without a menu link has no active link.
   */
  public function testPageWithoutLinkHasNoActiveLink(): void {
    $this->setCurrentRoute(['user' => '99']);

    $this->assertNull($this->activeTrail()->getActiveLink('main'));
    $this->assertSame([''], array_keys($this->activeTrail()->getActiveTrailIds('main')));
  }

  /**
   * Only links in the requested menu are considered.
   */
  public function testLinksInOtherMenusAreIgnored(): void {
    $this->createLink('Footer', 'entity:user/5', TRUE, 0, NULL, 'footer');
    $this->setCurrentRoute(['user' => '5']);

    $this->assertNull($this->activeTrail()->getActiveLink('main'));
    $this->assertSame('Footer', $this->activeTrail()->getActiveLink('footer')->getTitle());
  }

  /**
   * Without a menu name, links from every menu are considered.
   */
  public function testNoMenuNameConsidersEveryMenu(): void {
    $this->createLink('Footer', 'entity:user/5', TRUE, 0, NULL, 'footer');
    $this->setCurrentRoute(['user' => '5']);

    $this->assertSame('Footer', $this->activeTrail()->getActiveLink()->getTitle());
  }

  /**
   * On the front page, links to <front> are considered, as in core.
   */
  public function testFrontPageConsidersFrontLinks(): void {
    $this->container->get('router.builder')->rebuild();
    $this->config('system.site')->set('page.front', '/user/6')->save();
    $this->createLink('Home', 'internal:/', FALSE, 0);
    $this->setCurrentRoute(['user' => '6']);

    $active_link = $this->activeTrail()->getActiveLink('main');

    $this->assertNotNull($active_link);
    $this->assertSame('Home', $active_link->getTitle());
  }

  /**
   * Returns the menu active trail service.
   */
  private function activeTrail(): MenuActiveTrailInterface {
    return $this->container->get('menu.active_trail');
  }

  /**
   * Makes a request on the test route with the given raw parameters current.
   *
   * The active trail caches its cache ID on first use, so this must be called
   * once per test, before the active trail service is used.
   */
  private function setCurrentRoute(array $raw_parameters): void {
    $request = Request::create('/user/' . reset($raw_parameters));
    $request->attributes->set(RouteObjectInterface::ROUTE_NAME, self::ROUTE_NAME);
    $request->attributes->set(RouteObjectInterface::ROUTE_OBJECT, new Route('/user/{user}'));
    $request->attributes->set('_raw_variables', new InputBag($raw_parameters));
    $request_stack = $this->container->get('request_stack');
    // KernelTestBase expects the current request to carry a session.
    $request->setSession($request_stack->getCurrentRequest()->getSession());
    $request_stack->push($request);
  }

  /**
   * Creates and saves a menu link.
   */
  private function createLink(string $title, string $uri, bool $enabled, int $weight, ?MenuLinkContentInterface $parent = NULL, string $menu_name = 'main'): MenuLinkContentInterface {
    $link = MenuLinkContent::create([
      'title' => $title,
      'link' => ['uri' => $uri],
      'menu_name' => $menu_name,
      'enabled' => $enabled,
      'weight' => $weight,
      'parent' => $parent ? $this->pluginId($parent) : '',
    ]);
    $link->save();
    return $link;
  }

  /**
   * Returns the menu link plugin ID of a menu link content entity.
   */
  private function pluginId(MenuLinkContentInterface $link): string {
    return $link->getPluginId();
  }

  /**
   * Returns the titles of the links in the active trail, child first.
   */
  private function trailTitles(string $menu_name): array {
    $manager = $this->container->get('plugin.manager.menu.link');
    $ids = array_filter(array_keys($this->activeTrail()->getActiveTrailIds($menu_name)));
    return array_values(array_map(
      fn (string $id): string => (string) $manager->createInstance($id)->getTitle(),
      $ids
    ));
  }

}
