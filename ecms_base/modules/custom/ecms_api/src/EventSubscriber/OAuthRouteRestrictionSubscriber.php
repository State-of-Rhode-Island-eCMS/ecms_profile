<?php

declare(strict_types=1);

namespace Drupal\ecms_api\EventSubscriber;

use Drupal\Component\Utility\Crypt;
use Drupal\ecms_api\EcmsApiBase;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Restricts access to the /oauth/token endpoint.
 *
 * On Acquia environments (AH_SITE_ENVIRONMENT is set), requires incoming
 * requests to carry an X-ECMS-Env header containing an HMAC-signed
 * environment identifier. This prevents external bots from consuming server
 * resources and enforces environment isolation (staging cannot authenticate
 * against production and vice versa).
 *
 * The .htaccess layer enforces header presence before PHP boots; this
 * subscriber handles HMAC validation and environment matching.
 *
 * @package Drupal\ecms_api\EventSubscriber
 */
class OAuthRouteRestrictionSubscriber implements EventSubscriberInterface {

  /**
   * The Acquia environment variable name.
   */
  const AH_ENV_VAR = 'AH_SITE_ENVIRONMENT';

  /**
   * The shared secret environment variable name.
   */
  const SHARED_SECRET_VAR = 'ECMS_SHARED_SECRET';

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [KernelEvents::REQUEST => ['onRequest', 100]];
  }

  /**
   * Validate ECMS authentication headers on /oauth/token requests.
   *
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   The kernel request event.
   */
  public function onRequest(RequestEvent $event): void {
    if (!$event->isMainRequest()) {
      return;
    }

    if ($event->getRequest()->getPathInfo() !== '/oauth/token') {
      return;
    }

    $currentEnv = getenv(self::AH_ENV_VAR);
    if (empty($currentEnv)) {
      // Not on Acquia — allow (local dev).
      return;
    }

    $sharedSecret = getenv(self::SHARED_SECRET_VAR);
    if (empty($sharedSecret)) {
      // On Acquia but shared secret not configured — fail closed.
      $event->setResponse(new JsonResponse(['error' => 'Forbidden'], 403));
      return;
    }

    $envHeader = $event->getRequest()->headers->get(EcmsApiBase::ENV_HEADER);

    if (empty($envHeader) || !str_contains($envHeader, ':')) {
      $event->setResponse(new JsonResponse(['error' => 'Forbidden'], 403));
      return;
    }

    // Header format: "{env}:{hmac-signature}".
    [$originEnv, $signature] = explode(':', $envHeader, 2);

    // Verify the HMAC signature to prevent spoofing.
    $expectedSignature = Crypt::hmacBase64($originEnv, $sharedSecret);
    if (!hash_equals($expectedSignature, $signature)) {
      $event->setResponse(new JsonResponse(['error' => 'Forbidden'], 403));
      return;
    }

    // Verify the publisher's environment matches this recipient's environment.
    if ($originEnv !== $currentEnv) {
      $event->setResponse(new JsonResponse(['error' => 'Forbidden'], 403));
    }
  }

}
