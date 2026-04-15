<?php

declare(strict_types=1);

namespace Drupal\Tests\ecms_api\Unit\EventSubscriber;

use Drupal\Component\Utility\Crypt;
use Drupal\ecms_api\EventSubscriber\OAuthRouteRestrictionSubscriber;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Unit tests for OAuthRouteRestrictionSubscriber.
 *
 * @package Drupal\Tests\ecms_api\Unit\EventSubscriber
 */
#[Group('ecms_api')]
#[Group('ecms')]
#[CoversClass(\Drupal\ecms_api\EventSubscriber\OAuthRouteRestrictionSubscriber::class)]
class OAuthRouteRestrictionSubscriberTest extends UnitTestCase {

  /**
   * The shared secret used in tests.
   */
  const TEST_SECRET = 'test-shared-secret-abc';

  /**
   * {@inheritDoc}
   */
  protected function tearDown(): void {
    parent::tearDown();

    // Clean up env vars to avoid polluting other tests.
    putenv('AH_SITE_ENVIRONMENT');
    putenv('ECMS_SHARED_SECRET');
  }

  /**
   * Build a RequestEvent for the given path and headers.
   *
   * @param string $path
   *   The request path.
   * @param array $headers
   *   Optional headers to set on the request.
   * @param bool $mainRequest
   *   Whether to simulate a main request (TRUE) or sub-request (FALSE).
   *
   * @return \Symfony\Component\HttpKernel\Event\RequestEvent
   *   The configured event.
   */
  private function buildEvent(string $path, array $headers = [], bool $mainRequest = TRUE): RequestEvent {
    $request = Request::create($path, 'POST');
    foreach ($headers as $name => $value) {
      $request->headers->set($name, $value);
    }

    $kernel = $this->createMock(HttpKernelInterface::class);
    $requestType = $mainRequest ? HttpKernelInterface::MAIN_REQUEST : HttpKernelInterface::SUB_REQUEST;

    return new RequestEvent($kernel, $request, $requestType);
  }

  /**
   * Helper to compute a valid signed env header value.
   *
   * @param string $env
   *   The environment value.
   * @param string $secret
   *   The shared secret.
   *
   * @return string
   *   The header value in "{env}:{signature}" format.
   */
  private static function signedEnvHeader(string $env, string $secret): string {
    return $env . ':' . Crypt::hmacBase64($env, $secret);
  }

  /**
   * Test that getSubscribedEvents returns the expected event mapping.
   */
  public function testGetSubscribedEvents(): void {
    $events = OAuthRouteRestrictionSubscriber::getSubscribedEvents();
    $this->assertArrayHasKey('kernel.request', $events);
    $this->assertEquals(['onRequest', 100], $events['kernel.request']);
  }

  /**
   * Test onRequest with various scenarios.
   *
   * @param string|null $ahEnv
   *   Value for AH_SITE_ENVIRONMENT, or NULL to leave unset.
   * @param string|null $secret
   *   Value for ECMS_SHARED_SECRET, or NULL to leave unset.
   * @param string $path
   *   The request URI path.
   * @param array $headers
   *   Request headers to include.
   * @param bool $mainRequest
   *   Whether this is a main request.
   * @param bool $expectForbidden
   *   TRUE if a 403 response is expected, FALSE if the event should pass through.
   */
  #[DataProvider('dataProviderForOnRequest')]
  public function testOnRequest(
    ?string $ahEnv,
    ?string $secret,
    string $path,
    array $headers,
    bool $mainRequest,
    bool $expectForbidden,
  ): void {
    if ($ahEnv !== NULL) {
      putenv("AH_SITE_ENVIRONMENT={$ahEnv}");
    }
    if ($secret !== NULL) {
      putenv("ECMS_SHARED_SECRET={$secret}");
    }

    $event = $this->buildEvent($path, $headers, $mainRequest);
    $subscriber = new OAuthRouteRestrictionSubscriber();
    $subscriber->onRequest($event);

    if ($expectForbidden) {
      $response = $event->getResponse();
      $this->assertInstanceOf(JsonResponse::class, $response);
      $this->assertEquals(403, $response->getStatusCode());
    }
    else {
      $this->assertNull($event->getResponse());
    }
  }

  /**
   * Data provider for testOnRequest().
   *
   * @return array[]
   *   Keyed test cases.
   */
  public static function dataProviderForOnRequest(): array {
    $secret = self::TEST_SECRET;

    return [
      'local dev — no env vars set' => [
        NULL, NULL, '/oauth/token', [], TRUE, FALSE,
      ],
      'non-oauth path is ignored' => [
        '01live', $secret, '/some/other/path', [], TRUE, FALSE,
      ],
      'sub-request is ignored' => [
        '01live', $secret, '/oauth/token', [], FALSE, FALSE,
      ],
      'missing ECMS_SHARED_SECRET on Acquia — fail closed' => [
        '01live', NULL, '/oauth/token', [], TRUE, TRUE,
      ],
      'missing X-ECMS-Env header' => [
        '01live', $secret, '/oauth/token',
        ['X-ECMS-Origin' => 'site.prod-riecms.acsitefactory.com'],
        TRUE, TRUE,
      ],
      'X-ECMS-Env header has no colon separator' => [
        '01live', $secret, '/oauth/token',
        ['X-ECMS-Env' => '01live'],
        TRUE, TRUE,
      ],
      'invalid HMAC signature' => [
        '01live', $secret, '/oauth/token',
        ['X-ECMS-Env' => '01live:bad-signature'],
        TRUE, TRUE,
      ],
      'valid signature but cross-env (test claims prod)' => [
        '01live', $secret, '/oauth/token',
        ['X-ECMS-Env' => self::signedEnvHeader('01test', $secret)],
        TRUE, TRUE,
      ],
      'valid signature but cross-env (prod claims test)' => [
        '01test', $secret, '/oauth/token',
        ['X-ECMS-Env' => self::signedEnvHeader('01live', $secret)],
        TRUE, TRUE,
      ],
      'valid prod to prod' => [
        '01live', $secret, '/oauth/token',
        ['X-ECMS-Env' => self::signedEnvHeader('01live', $secret)],
        TRUE, FALSE,
      ],
      'valid test to test' => [
        '01test', $secret, '/oauth/token',
        ['X-ECMS-Env' => self::signedEnvHeader('01test', $secret)],
        TRUE, FALSE,
      ],
      'valid dev to dev' => [
        '01dev', $secret, '/oauth/token',
        ['X-ECMS-Env' => self::signedEnvHeader('01dev', $secret)],
        TRUE, FALSE,
      ],
      'valid custom domain (tax.ri.gov) prod to prod' => [
        '01live', $secret, '/oauth/token',
        [
          'X-ECMS-Origin' => 'tax.ri.gov',
          'X-ECMS-Env' => self::signedEnvHeader('01live', $secret),
        ],
        TRUE, FALSE,
      ],
      'signature from wrong secret is rejected' => [
        '01live', $secret, '/oauth/token',
        ['X-ECMS-Env' => self::signedEnvHeader('01live', 'wrong-secret')],
        TRUE, TRUE,
      ],
    ];
  }

}
