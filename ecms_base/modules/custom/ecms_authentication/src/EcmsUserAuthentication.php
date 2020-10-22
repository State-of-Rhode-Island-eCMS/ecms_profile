<?php

declare(strict_types = 1);

namespace Drupal\ecms_authentication;

use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class to determine if the AAD user has access to the site.
 *
 * This is a custom service that will check the groups of a user and will
 * determine if the user is allowed to access the site.
 *
 * @package Drupal\ecms_authentication
 */
class EcmsUserAuthentication {

  /**
   * Constant of the administrator group/role.
   */
  const DRUPAL_ADMINISTRATOR = 'Drupal_Admin';

  /**
   * The request_stack service.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  private $requestStack;

  /**
   * EcmsUserAuthentication constructor.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request_stack service.
   */
  public function __construct(RequestStack $requestStack) {
    $this->requestStack = $requestStack;
  }

  /**
   * Check the user groups for site access.
   *
   * @param array $groups
   *   An array of group names coming from Active Directory.
   *
   * @return bool
   *   Return True if the user is allowed to access.
   */
  public function checkGroupAccess(array $groups): bool {

    // Check if the user is in the administrator group.
    if ($this->isAdministrator($groups)) {
      return TRUE;
    }

    return $this->isSiteMember($groups);
  }

  /**
   * Check if the groups contain the administration group.
   *
   * @param array $groups
   *   Array of group names to check.
   *
   * @return bool
   *   True if the groups contain the admin group.
   */
  private function isAdministrator(array $groups): bool {
    return in_array(self::DRUPAL_ADMINISTRATOR, $groups, TRUE);
  }

  /**
   * Check if the groups contain the hostname of the site.
   *
   * @param array $groups
   *   Array of group names to check.
   *
   * @return bool
   *   True if the host is in the groups array.
   */
  private function isSiteMember(array $groups): bool {
    // Get the hostname of the current site.
    $host = $this->requestStack->getCurrentRequest()->getHttpHost();

    return in_array($host, $groups, TRUE);
  }

}
