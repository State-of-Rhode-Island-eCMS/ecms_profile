<?php

declare(strict_types=1);

namespace Drupal\ecms_search_settings_test\Authentication;

use Drupal\Core\Authentication\AuthenticationProviderInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\SessionConfigurationInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Logs in the user whose ID is the value of the session cookie.
 */
class SessionCookieProvider implements AuthenticationProviderInterface {

  /**
   * Constructs a SessionCookieProvider.
   *
   * @param \Drupal\Core\Session\SessionConfigurationInterface $sessionConfiguration
   *   The session configuration.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(
    protected SessionConfigurationInterface $sessionConfiguration,
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function applies(Request $request) {
    return $this->sessionConfiguration->hasSession($request);
  }

  /**
   * {@inheritdoc}
   */
  public function authenticate(Request $request) {
    $name = $this->sessionConfiguration->getOptions($request)['name'];
    return $this->entityTypeManager->getStorage('user')->load((int) $request->cookies->get($name));
  }

}
