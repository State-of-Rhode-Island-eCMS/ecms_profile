<?php

declare(strict_types=1);

namespace Drupal\Tests\ecms_api_recipient\Unit;

use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\ecms_api\EcmsApiHelper;
use Drupal\ecms_api_recipient\EcmsApiCreateNotifications;
use Drupal\ecms_api_recipient\JsonApiHelper;
use Drupal\jsonapi_extras\EntityToJsonApi;
use Drupal\node\NodeInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\user\UserInterface;
use GuzzleHttp\ClientInterface;

/**
 * Unit tests for the EcmsApiCreateNotifications class.
 *
 * @package Drupal\Tests\ecms_api_recipient\Unit
 * @group ecms_api
 * @group ecms_api_recipient
 */
class EcmsApiCreateNotificationsTest extends UnitTestCase {

  /**
   * The user id to test with.
   */
  const USER_ID = 123456;

  /**
   * The entity uuid to test with.
   */
  const ENTITY_UUID = '2e434fe8-0fcd-48ae-941e-ea78c4f348f7';

  /**
   * The scope used to connect to the api.
   */
  const API_SCOPE = 'ecms_api_recipient';

  /**
   * The attributes to be returned from conversion.
   */
  const JSON_ATTRIBUTES = [
    "langcode" => "en",
    "revision_log" => "Revision log message",
    "status" => TRUE,
    "title" => "Notification - Cui Inhibeo (en)",
    "default_langcode" => FALSE,
    "revision_translation_affected" => TRUE,
    "moderation_state" => "published",
    "content_translation_source" => "und",
    "content_translation_outdated" => FALSE,
    "field_notification_expire_date" => "2020-10-10T04:21:00+00:00",
    "field_notification_global" => TRUE,
    "field_notification_text" => "Global Notification Text",
    "uuid" => self::ENTITY_UUID,
  ];

  /**
   * The json object to test with.
   */
  const JSON_DATA_OBJECT_STRING = '{"jsonapi":{"version":"1.0","meta":{"links":{"self":{"href":"http:\/\/jsonapi.org\/format\/1.0\/"}}}},"data":{"type":"node--notification","id":"2e434fe8-0fcd-48ae-941e-ea78c4f348f7","links":{"self":{"href":"https:\/\/develop-ecms-profile.lndo.site\/EcmsApi\/node\/notification\/2e434fe8-0fcd-48ae-941e-ea78c4f348f7?resourceVersion=id%3A593"}},"attributes":{"drupal_internal__nid":218,"drupal_internal__vid":593,"langcode":"en","revision_timestamp":"2020-10-23T15:23:06+00:00","revision_log":"Bulk operation publish revision ","status":true,"title":"Notification - Cui Inhibeo (en)","created":"2020-10-20T03:49:23+00:00","changed":"2020-10-23T15:23:06+00:00","promote":false,"sticky":false,"default_langcode":false,"revision_translation_affected":true,"moderation_state":"published","path":{"alias":null,"pid":null,"langcode":"en"},"rh_action":null,"rh_redirect":null,"rh_redirect_response":null,"content_translation_source":"und","content_translation_outdated":false,"field_notification_expire_date":"2020-10-10T04:21:00+00:00","field_notification_global":true,"field_notification_text":"Global Notification Text"},"relationships":{"node_type":{"data":{"type":"node_type--node_type","id":"8dafd8ea-debc-4f84-91e8-78a781304d11"},"links":{"related":{"href":"https:\/\/develop-ecms-profile.lndo.site\/EcmsApi\/node\/notification\/2e434fe8-0fcd-48ae-941e-ea78c4f348f7\/node_type?resourceVersion=id%3A593"},"self":{"href":"https:\/\/develop-ecms-profile.lndo.site\/EcmsApi\/node\/notification\/2e434fe8-0fcd-48ae-941e-ea78c4f348f7\/relationships\/node_type?resourceVersion=id%3A593"}}},"revision_uid":{"data":{"type":"user--user","id":"8f102cd0-8202-4916-8641-f2da52ef7639"},"links":{"related":{"href":"https:\/\/develop-ecms-profile.lndo.site\/EcmsApi\/node\/notification\/2e434fe8-0fcd-48ae-941e-ea78c4f348f7\/revision_uid?resourceVersion=id%3A593"},"self":{"href":"https:\/\/develop-ecms-profile.lndo.site\/EcmsApi\/node\/notification\/2e434fe8-0fcd-48ae-941e-ea78c4f348f7\/relationships\/revision_uid?resourceVersion=id%3A593"}}},"uid":{"data":{"type":"user--user","id":"8f102cd0-8202-4916-8641-f2da52ef7639"},"links":{"related":{"href":"https:\/\/develop-ecms-profile.lndo.site\/EcmsApi\/node\/notification\/2e434fe8-0fcd-48ae-941e-ea78c4f348f7\/uid?resourceVersion=id%3A593"},"self":{"href":"https:\/\/develop-ecms-profile.lndo.site\/EcmsApi\/node\/notification\/2e434fe8-0fcd-48ae-941e-ea78c4f348f7\/relationships\/uid?resourceVersion=id%3A593"}}}}},"links":{"self":{"href":"https:\/\/develop-ecms-profile.lndo.site\/EcmsApi\/node\/notification\/2e434fe8-0fcd-48ae-941e-ea78c4f348f7"}}}';

  /**
   * The full json array.
   */
  const JSON_ARRAY = [
    'type' => 'node--notification',
    'id' => self::ENTITY_UUID,
    'attributes' => self::JSON_ATTRIBUTES,
  ];

  /**
   * Mock of the entity_type.manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  private $entityTypeManager;

  /**
   * Mock of the ecms_api_recipient.jsonapi_helper service.
   *
   * @var \Drupal\ecms_api_recipient\JsonApiHelper|\PHPUnit\Framework\MockObject\MockObject
   */
  private $jsonApiHelper;

  /**
   * Mock of the ecms_api_helper service.
   *
   * @var \Drupal\ecms_api\EcmsApiHelper|\PHPUnit\Framework\MockObject\MockObject
   */
  private $ecmsApiHelper;

  /**
   * Mock of the recipient user.
   *
   * @var \Drupal\user\UserInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  private $recipientUser;

  /**
   * Mock of the user entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  private $userStorage;

  /**
   * Mock of the node entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  private $nodeStorage;

  /**
   * Mock of the http_client service.
   *
   * @var \GuzzleHttp\ClientInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  private $httpClient;

  /**
   * Mock of the jsonapi_extras.entity.to_jsonapi service.
   *
   * @var \Drupal\jsonapi_extras\EntityToJsonApi|\PHPUnit\Framework\MockObject\MockObject
   */
  private $entityToJsonApi;

  /**
   * Mock node to test with.
   *
   * @var \Drupal\node\NodeInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  private $node;

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->jsonApiHelper = $this->createMock(JsonApiHelper::class);
    $this->ecmsApiHelper = $this->createMock(EcmsApiHelper::class);
    $this->recipientUser = $this->createMock(UserInterface::class);
    $this->userStorage = $this->createMock(EntityStorageInterface::class);
    $this->nodeStorage = $this->createMock(EntityStorageInterface::class);
    $this->httpClient = $this->createMock(ClientInterface::class);
    $this->entityToJsonApi = $this->createMock(EntityToJsonApi::class);
    $this->node = $this->createMock(NodeInterface::class);
  }

  /**
   * Test the createNotificationFromJson method.
   *
   * @param int $testNumber
   *   What test number we are running.
   * @param bool $expected
   *   The expected result.
   *
   * @dataProvider dataProviderForTestCreateNotificationFromJson
   */
  public function testCreateNotificationFromJson(int $testNumber, bool $expected): void {
    $object = json_decode(self::JSON_DATA_OBJECT_STRING);

    switch ($testNumber) {
      case 1:
        $this->userStorage->expects($this->once())
          ->method('loadByProperties')
          ->with(['name' => self::API_SCOPE])
          ->willReturn([]);

        $this->entityTypeManager->expects($this->once())
          ->method('getStorage')
          ->with('user')
          ->willReturn($this->userStorage);
        break;

      case 2:
        // This will error on the extract entity method.
        $this->userStorage->expects($this->once())
          ->method('loadByProperties')
          ->with(['name' => self::API_SCOPE])
          ->willReturn([$this->recipientUser]);

        $this->entityTypeManager->expects($this->once())
          ->method('getStorage')
          ->with('user')
          ->willReturn($this->userStorage);

        $this->jsonApiHelper->expects($this->once())
          ->method('convertJsonDataToArray')
          ->with($object->data)
          ->willreturn(self::JSON_ARRAY);

        $this->jsonApiHelper->expects($this->once())
          ->method('extractEntity')
          ->with(self::JSON_ARRAY)
          ->willReturn([]);

        break;

      case 3:
        // This will error on the node->save() method.
        $exception = $this->createMock(EntityStorageException::class);
        $this->recipientUser->expects($this->once())
          ->method('id')
          ->willReturn(self::USER_ID);

        $this->node->expects($this->once())
          ->method('set')
          ->with('uid', self::USER_ID)
          ->willReturnSelf();

        $this->node->expects($this->once())
          ->method('save')
          ->willThrowException($exception);

        $this->userStorage->expects($this->once())
          ->method('loadByProperties')
          ->with(['name' => self::API_SCOPE])
          ->willReturn([$this->recipientUser]);

        $this->entityTypeManager->expects($this->once())
          ->method('getStorage')
          ->with('user')
          ->willReturn($this->userStorage);

        $this->jsonApiHelper->expects($this->once())
          ->method('convertJsonDataToArray')
          ->with($object->data)
          ->willreturn(self::JSON_ARRAY);

        $this->jsonApiHelper->expects($this->once())
          ->method('extractEntity')
          ->with(self::JSON_ARRAY)
          ->willReturn($this->node);

        break;

      case 4:
        // This will be successful.
        $this->recipientUser->expects($this->once())
          ->method('id')
          ->willReturn(self::USER_ID);

        $this->node->expects($this->once())
          ->method('set')
          ->with('uid', self::USER_ID)
          ->willReturnSelf();

        $this->node->expects($this->once())
          ->method('save')
          ->willReturnSelf();

        $this->userStorage->expects($this->once())
          ->method('loadByProperties')
          ->with(['name' => self::API_SCOPE])
          ->willReturn([$this->recipientUser]);

        $this->entityTypeManager->expects($this->once())
          ->method('getStorage')
          ->with('user')
          ->willReturn($this->userStorage);

        $this->jsonApiHelper->expects($this->once())
          ->method('convertJsonDataToArray')
          ->with($object->data)
          ->willreturn(self::JSON_ARRAY);

        $this->jsonApiHelper->expects($this->once())
          ->method('extractEntity')
          ->with(self::JSON_ARRAY)
          ->willReturn($this->node);

        break;
    }

    $ecmsApiCreateNotification = new EcmsApiCreateNotifications(
      $this->httpClient,
      $this->entityToJsonApi,
      $this->ecmsApiHelper,
      $this->entityTypeManager,
      $this->jsonApiHelper
    );

    $result = $ecmsApiCreateNotification->createNotificationFromJson($object->data);

    $this->assertEquals($expected, $result);
  }

  /**
   * Data provider for the testCreateNotificationFromJson method.
   *
   * @return array[]
   *   Array of parameters to pass to the testCreateNotificationFromJson method.
   */
  public function dataProviderForTestCreateNotificationFromJson(): array {
    return [
      'test1' => [
        1,
        FALSE,
      ],
      'test2' => [
        2,
        FALSE,
      ],
      'test3' => [
        3,
        FALSE,
      ],
      'test4' => [
        4,
        TRUE,
      ],
    ];
  }

  /**
   * Test the createNotificationTranslationFromJson method.
   *
   * @param int $testNumber
   *   What test number we are running.
   * @param bool $expected
   *   The expected result of the method.
   *
   * @dataProvider dataProviderForTestCreateNotificationTranslationFromJson
   */
  public function testCreateNotificationTranslationFromJson(int $testNumber, bool $expected): void {
    $object = json_decode(self::JSON_DATA_OBJECT_STRING);

    switch ($testNumber) {
      case 1:
        $this->userStorage->expects($this->once())
          ->method('loadByProperties')
          ->with(['name' => self::API_SCOPE])
          ->willReturn([]);

        $this->entityTypeManager->expects($this->once())
          ->method('getStorage')
          ->with('user')
          ->willReturn($this->userStorage);
        break;

      case 2:
        // This will error on the loadExistingNode method.
        $this->nodeStorage->expects($this->once())
          ->method('loadByProperties')
          ->with(['uuid' => self::ENTITY_UUID])
          ->willReturn([]);

        $this->userStorage->expects($this->once())
          ->method('loadByProperties')
          ->with(['name' => self::API_SCOPE])
          ->willReturn([$this->recipientUser]);

        $this->entityTypeManager->expects($this->exactly(2))
          ->method('getStorage')
          ->will($this->returnValueMap([
            ['user', $this->userStorage], ['node', $this->nodeStorage]
          ]));

        $this->jsonApiHelper->expects($this->once())
          ->method('convertJsonDataToArray')
          ->with($object->data)
          ->willreturn(self::JSON_ARRAY);
        break;

      case 3:
        // This will error on the hasTranslation check.
        $this->node->expects($this->once())
          ->method('hasTranslation')
          ->with(self::JSON_ARRAY['attributes']['langcode'])
          ->willReturn(TRUE);

        $this->node->expects($this->never())
          ->method('addTranslation');
        $this->node->expects($this->never())
          ->method('save');

        $this->nodeStorage->expects($this->once())
          ->method('loadByProperties')
          ->with(['uuid' => self::ENTITY_UUID])
          ->willReturn([$this->node]);

        $this->userStorage->expects($this->once())
          ->method('loadByProperties')
          ->with(['name' => self::API_SCOPE])
          ->willReturn([$this->recipientUser]);

        $this->entityTypeManager->expects($this->exactly(2))
          ->method('getStorage')
          ->will($this->returnValueMap([
            ['user', $this->userStorage], ['node', $this->nodeStorage]
          ]));

        $this->jsonApiHelper->expects($this->once())
          ->method('convertJsonDataToArray')
          ->with($object->data)
          ->willreturn(self::JSON_ARRAY);
        break;

      case 4:
        // This will throw a storage exception on node save.
        $this->node->expects($this->once())
          ->method('hasTranslation')
          ->with(self::JSON_ARRAY['attributes']['langcode'])
          ->willReturn(FALSE);

        $this->node->expects($this->once())
          ->method('addTranslation')
          ->with(self::JSON_ARRAY['attributes']['langcode'], self::JSON_ATTRIBUTES)
          ->willReturnSelf();

        $exception = $this->createMock(EntityStorageException::class);

        $this->node->expects($this->once())
          ->method('save')
          ->willThrowException($exception);

        $this->nodeStorage->expects($this->once())
          ->method('loadByProperties')
          ->with(['uuid' => self::ENTITY_UUID])
          ->willReturn([$this->node]);

        $this->userStorage->expects($this->once())
          ->method('loadByProperties')
          ->with(['name' => self::API_SCOPE])
          ->willReturn([$this->recipientUser]);

        $this->entityTypeManager->expects($this->exactly(2))
          ->method('getStorage')
          ->will($this->returnValueMap([
            ['user', $this->userStorage],
            ['node', $this->nodeStorage]
          ]));

        $this->jsonApiHelper->expects($this->once())
          ->method('convertJsonDataToArray')
          ->with($object->data)
          ->willreturn(self::JSON_ARRAY);
        break;

      case 5:
        // This will be a successful test.
        $this->node->expects($this->once())
          ->method('hasTranslation')
          ->with(self::JSON_ARRAY['attributes']['langcode'])
          ->willReturn(FALSE);

        $this->node->expects($this->once())
          ->method('addTranslation')
          ->with(self::JSON_ARRAY['attributes']['langcode'], self::JSON_ATTRIBUTES)
          ->willReturnSelf();

        $this->node->expects($this->once())
          ->method('save')
          ->willReturnSelf();

        $this->nodeStorage->expects($this->once())
          ->method('loadByProperties')
          ->with(['uuid' => self::ENTITY_UUID])
          ->willReturn([$this->node]);

        $this->userStorage->expects($this->once())
          ->method('loadByProperties')
          ->with(['name' => self::API_SCOPE])
          ->willReturn([$this->recipientUser]);

        $this->entityTypeManager->expects($this->exactly(2))
          ->method('getStorage')
          ->will($this->returnValueMap([
            ['user', $this->userStorage], ['node', $this->nodeStorage]
          ]));

        $this->jsonApiHelper->expects($this->once())
          ->method('convertJsonDataToArray')
          ->with($object->data)
          ->willreturn(self::JSON_ARRAY);
        break;
    }

    $ecmsApiCreateNotification = new EcmsApiCreateNotifications(
      $this->httpClient,
      $this->entityToJsonApi,
      $this->ecmsApiHelper,
      $this->entityTypeManager,
      $this->jsonApiHelper
    );

    $result = $ecmsApiCreateNotification->createNotificationTranslationFromJson($object->data);

    $this->assertEquals($expected, $result);
  }

  /**
   * Data provider for the testCreateNotificationTranslationFromJson method.
   *
   * @return array[]
   *   Array of parameters for the
   *   testCreateNotificationTranslationFromJson method.
   */
  public function dataProviderForTestCreateNotificationTranslationFromJson(): array {
    return [
      'test1' => [
        1,
        FALSE,
      ],
      'test2' => [
        2,
        FALSE,
      ],
      'test3' => [
        3,
        TRUE,
      ],
      'test4' => [
        4,
        FALSE,
      ],
      'test5' => [
        5,
        TRUE,
      ],
    ];
  }

  /**
   * Test the CheckEntityUuidExists method.
   *
   * @param string $uuid
   *   The uuid to test.
   * @param bool $expected
   *   The expected result of the method.
   *
   * @dataProvider dataProviderForTestCheckEntityUuidExists
   */
  public function testCheckEntityUuidExists(string $uuid, bool $expected): void {
    $return = [$this->node];

    if ($uuid === 'none') {
      $return = [];
    }

    $this->nodeStorage->expects($this->once())
      ->method('loadByProperties')
      ->with(['uuid' => $uuid])
      ->willReturn($return);

    $this->entityTypeManager->expects($this->once())
      ->method('getStorage')
      ->with('node')
      ->willReturn($this->nodeStorage);

    $ecmsApiCreateNotification = new EcmsApiCreateNotifications(
      $this->httpClient,
      $this->entityToJsonApi,
      $this->ecmsApiHelper,
      $this->entityTypeManager,
      $this->jsonApiHelper
    );

    $result = $ecmsApiCreateNotification->checkEntityUuidExists($uuid);

    $this->assertEquals($expected, $result);
  }

  /**
   * Data provider for testCheckEntityUuidExists method.
   *
   * @return array[]
   *   Array of parameters to pass to the testCheckEntityUuidExists method.
   */
  public function dataProviderForTestCheckEntityUuidExists(): array {
    return [
      'test1' => [
        self::ENTITY_UUID,
        TRUE,
      ],
      'test2' => [
        'none',
        FALSE,
      ],
    ];
  }

}
