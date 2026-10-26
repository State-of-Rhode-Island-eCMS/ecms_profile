<?php

declare(strict_types=1);

namespace Drupal\ecms_layout;

use Drupal\Core\Menu\MenuActiveTrail;

/**
 * Restores the pre-11.4 active link selection for disabled menu links.
 *
 * Drupal 11.4.0 changed MenuActiveTrail::getActiveLink() to return the first
 * *enabled* link matching the current route. Before that it returned the first
 * match regardless of status, sorted by depth, weight and ID.
 *
 * Agencies (RIEMA, RIDOH) build per-section sidebar menus on top of that: each
 * section is a disabled top-level link in the main menu, which keeps it out of
 * the primary navigation, with its pages as enabled children. A second, enabled
 * shortcut link to the section's landing page sits under a visible parent so
 * the primary navigation can reach it. The sidebar menu block starts at level
 * 2, so it lists the children of whichever top-level link is in the active
 * trail.
 *
 * Under 11.4 the landing page resolves to the enabled shortcut instead of the
 * disabled section link, so its sidebar shows the shortcut's siblings rather
 * than the section's own pages. Selecting the shallowest match again, as
 * before, keeps those sections working.
 *
 * @see \Drupal\Core\Menu\MenuActiveTrail::getActiveLink()
 */
final class EcmsMenuActiveTrail extends MenuActiveTrail {

  /**
   * {@inheritdoc}
   */
  public function getActiveLink($menu_name = NULL) {
    $links = [];

    $route_name = $this->routeMatch->getRouteName();
    if ($route_name) {
      $route_parameters = $this->routeMatch->getRawParameters()->all();
      $links = $this->menuLinkManager->loadLinksByRoute($route_name, $route_parameters, $menu_name);
    }

    if ($this->pathMatcher->isFrontPage()) {
      $links = array_merge($links, $this->menuLinkManager->loadLinksByRoute('<front>', [], $menu_name));
    }

    // The links are already sorted by depth, weight and ID. Unlike core, do
    // not skip disabled links.
    return $links ? reset($links) : NULL;
  }

}
