<?php

declare(strict_types = 1);

namespace Drupal\Tests\ecms_api_recipient\Unit\Plugin\QueueWorker;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Utility\UnroutedUrlAssemblerInterface;
use Drupal\ecms_api_recipient\EcmsApiCreateNotifications;
use Drupal\ecms_api_recipient\EcmsApiRecipientRetrieveNotifications;
use Drupal\ecms_api_recipient\Plugin\QueueWorker\NotificationCreationQueueWorker;
use Drupal\Tests\UnitTestCase;
use GuzzleHttp\ClientInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Class NotificationCreationQueueWorkerTest
 *
 * @package Drupal\Tests\ecms_api_recipient\Unit\Plugin\QueueWorker
 * @group ecms_api
 * @group ecms_api_recipient
 * @group ecms_api_test
 */
class NotificationCreationQueueWorkerTest extends UnitTestCase {

  const ENTITY_UUID = '2e434fe8-0fcd-48ae-941e-ea78c4f348f7';
  const LANGCODE = 'de';
  const DEFAULT_LANGCODE = 'en';
  const HUB_URI = 'https://oomphinc.com';
  const ENDPOINT_STRING = 'https://oomphinc.com/de/EcmpApi/node/notification/abcd-efgh-ijkl-mnop';
  const DEFAULT_ENDPOINT_STRING = 'https://oomphinc.com/EcmpApi/node/notification/abcd-efgh-ijkl-mnop';
  const JSON_DATA_OBJECT_STRING = '{"jsonapi":{"version":"1.0","meta":{"links":{"self":{"href":"http:\/\/jsonapi.org\/format\/1.0\/"}}}},"data":{"type":"node--notification","id":"2e434fe8-0fcd-48ae-941e-ea78c4f348f7","links":{"self":{"href":"https:\/\/develop-ecms-profile.lndo.site\/EcmsApi\/node\/notification\/2e434fe8-0fcd-48ae-941e-ea78c4f348f7?resourceVersion=id%3A593"}},"attributes":{"drupal_internal__nid":218,"drupal_internal__vid":593,"langcode":"de","revision_timestamp":"2020-10-23T15:23:06+00:00","revision_log":"Bulk operation publish revision ","status":true,"title":"Notification - Cui Inhibeo (en)","created":"2020-10-20T03:49:23+00:00","changed":"2020-10-23T15:23:06+00:00","promote":false,"sticky":false,"default_langcode":false,"revision_translation_affected":true,"moderation_state":"published","path":{"alias":null,"pid":null,"langcode":"de"},"rh_action":null,"rh_redirect":null,"rh_redirect_response":null,"content_translation_source":"en","content_translation_outdated":false,"field_notification_expire_date":"2020-10-10T04:21:00+00:00","field_notification_global":true,"field_notification_text":"Global Notification Text"},"relationships":{"node_type":{"data":{"type":"node_type--node_type","id":"8dafd8ea-debc-4f84-91e8-78a781304d11"},"links":{"related":{"href":"https:\/\/develop-ecms-profile.lndo.site\/EcmsApi\/node\/notification\/2e434fe8-0fcd-48ae-941e-ea78c4f348f7\/node_type?resourceVersion=id%3A593"},"self":{"href":"https:\/\/develop-ecms-profile.lndo.site\/EcmsApi\/node\/notification\/2e434fe8-0fcd-48ae-941e-ea78c4f348f7\/relationships\/node_type?resourceVersion=id%3A593"}}},"revision_uid":{"data":{"type":"user--user","id":"8f102cd0-8202-4916-8641-f2da52ef7639"},"links":{"related":{"href":"https:\/\/develop-ecms-profile.lndo.site\/EcmsApi\/node\/notification\/2e434fe8-0fcd-48ae-941e-ea78c4f348f7\/revision_uid?resourceVersion=id%3A593"},"self":{"href":"https:\/\/develop-ecms-profile.lndo.site\/EcmsApi\/node\/notification\/2e434fe8-0fcd-48ae-941e-ea78c4f348f7\/relationships\/revision_uid?resourceVersion=id%3A593"}}},"uid":{"data":{"type":"user--user","id":"8f102cd0-8202-4916-8641-f2da52ef7639"},"links":{"related":{"href":"https:\/\/develop-ecms-profile.lndo.site\/EcmsApi\/node\/notification\/2e434fe8-0fcd-48ae-941e-ea78c4f348f7\/uid?resourceVersion=id%3A593"},"self":{"href":"https:\/\/develop-ecms-profile.lndo.site\/EcmsApi\/node\/notification\/2e434fe8-0fcd-48ae-941e-ea78c4f348f7\/relationships\/uid?resourceVersion=id%3A593"}}}}},"links":{"self":{"href":"https:\/\/develop-ecms-profile.lndo.site\/EcmsApi\/node\/notification\/2e434fe8-0fcd-48ae-941e-ea78c4f348f7"}}}';
  const JSON_DATA_OBJECT_STRING_DEFAULT = '{"jsonapi":{"version":"1.0","meta":{"links":{"self":{"href":"http:\/\/jsonapi.org\/format\/1.0\/"}}}},"data":{"type":"node--notification","id":"2e434fe8-0fcd-48ae-941e-ea78c4f348f7","links":{"self":{"href":"https:\/\/develop-ecms-profile.lndo.site\/EcmsApi\/node\/notification\/2e434fe8-0fcd-48ae-941e-ea78c4f348f7?resourceVersion=id%3A593"}},"attributes":{"drupal_internal__nid":218,"drupal_internal__vid":593,"langcode":"en","revision_timestamp":"2020-10-23T15:23:06+00:00","revision_log":"Bulk operation publish revision ","status":true,"title":"Notification - Cui Inhibeo (en)","created":"2020-10-20T03:49:23+00:00","changed":"2020-10-23T15:23:06+00:00","promote":false,"sticky":false,"default_langcode":true,"revision_translation_affected":true,"moderation_state":"published","path":{"alias":null,"pid":null,"langcode":"en"},"rh_action":null,"rh_redirect":null,"rh_redirect_response":null,"content_translation_source":"und","content_translation_outdated":false,"field_notification_expire_date":"2020-10-10T04:21:00+00:00","field_notification_global":true,"field_notification_text":"Global Notification Text"},"relationships":{"node_type":{"data":{"type":"node_type--node_type","id":"8dafd8ea-debc-4f84-91e8-78a781304d11"},"links":{"related":{"href":"https:\/\/develop-ecms-profile.lndo.site\/EcmsApi\/node\/notification\/2e434fe8-0fcd-48ae-941e-ea78c4f348f7\/node_type?resourceVersion=id%3A593"},"self":{"href":"https:\/\/develop-ecms-profile.lndo.site\/EcmsApi\/node\/notification\/2e434fe8-0fcd-48ae-941e-ea78c4f348f7\/relationships\/node_type?resourceVersion=id%3A593"}}},"revision_uid":{"data":{"type":"user--user","id":"8f102cd0-8202-4916-8641-f2da52ef7639"},"links":{"related":{"href":"https:\/\/develop-ecms-profile.lndo.site\/EcmsApi\/node\/notification\/2e434fe8-0fcd-48ae-941e-ea78c4f348f7\/revision_uid?resourceVersion=id%3A593"},"self":{"href":"https:\/\/develop-ecms-profile.lndo.site\/EcmsApi\/node\/notification\/2e434fe8-0fcd-48ae-941e-ea78c4f348f7\/relationships\/revision_uid?resourceVersion=id%3A593"}}},"uid":{"data":{"type":"user--user","id":"8f102cd0-8202-4916-8641-f2da52ef7639"},"links":{"related":{"href":"https:\/\/develop-ecms-profile.lndo.site\/EcmsApi\/node\/notification\/2e434fe8-0fcd-48ae-941e-ea78c4f348f7\/uid?resourceVersion=id%3A593"},"self":{"href":"https:\/\/develop-ecms-profile.lndo.site\/EcmsApi\/node\/notification\/2e434fe8-0fcd-48ae-941e-ea78c4f348f7\/relationships\/uid?resourceVersion=id%3A593"}}}}},"links":{"self":{"href":"https:\/\/develop-ecms-profile.lndo.site\/EcmsApi\/node\/notification\/2e434fe8-0fcd-48ae-941e-ea78c4f348f7"}}}';
  /**
   * The default language code of the hub.
   */
  const HUB_DEFAULT_LANGCODE = 'en';

  /**
   * The ecms_api_recipient.retrieve_notifications service.
   *
   * @var \Drupal\ecms_api_recipient\EcmsApiRecipientRetrieveNotifications
   */
  private $ecmsNotificationRetriever;

  /**
   * The http_client service.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  private $httpClient;

  /**
   * The ecms_api_recipient.settings configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  private $config;

  private $configFactory;

  /**
   * The ecms_api_recipient.create_notifications service.
   *
   * @var \Drupal\ecms_api_recipient\EcmsApiCreateNotifications
   */
  private $ecmsApiCreateNotification;

  private $plugin;

  private $urlAssembler;
  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->ecmsNotificationRetriever = $this->createMock(EcmsApiRecipientRetrieveNotifications::class);
    $this->httpClient = $this->createMock(ClientInterface::class);
    $this->configFactory = $this->createMock(ConfigFactoryInterface::class);
    $this->config = $this->createMock(ImmutableConfig::class);
    $this->ecmsApiCreateNotification = $this->createMock(EcmsApiCreateNotifications::class);
    $this->urlAssembler = $this->createMock(UnroutedUrlAssemblerInterface::class);

    $this->configFactory->expects($this->once())
      ->method('get')
      ->with('ecms_api_recipient.settings')
      ->willReturn($this->config);

    $container = new ContainerBuilder();
    $container->set('ecms_api_recipient.retrieve_notifications', $this->ecmsNotificationRetriever);
    $container->set('http_client', $this->httpClient);
    $container->set('config.factory', $this->configFactory);
    $container->set('ecms_api_recipient.create_notifications', $this->ecmsApiCreateNotification);
    $container->set('unrouted_url_assembler', $this->urlAssembler);
    \Drupal::setContainer($container);

    $this->plugin = NotificationCreationQueueWorker::create($container, [],'id', []);
  }

  /**
   * @param int $testNumber
   *   The test number currently running.
   *
   * @dataProvider dataProviderForTestProcessItem
   */
  public function testProcessItem(int $testNumber = 1): void {
    $notification = NULL;

    switch ($testNumber) {
      case 2:
        $notification = ['uuid' => self::ENTITY_UUID];
        break;
      case 3:
        $notification = ['uuid' => self::ENTITY_UUID, 'langcode' => self::LANGCODE];

        $this->config->expects($this->once())
          ->method('get')
          ->with('api_main_hub')
          ->willReturn('');

        $this->urlAssembler->expects($this->never())
          ->method('assemble');

        $this->expectException('\Drupal\Core\Queue\RequeueException');
        break;
      case 4:
        $notification = ['uuid' => self::ENTITY_UUID, 'langcode' => self::LANGCODE];

        $this->config->expects($this->once())
          ->method('get')
          ->with('api_main_hub')
          ->willReturn(self::HUB_URI);

        $this->urlAssembler->expects($this->exactly(2))
          ->method('assemble')
          ->willReturnOnConsecutiveCalls(self::HUB_URI, self::ENDPOINT_STRING);

        $stream = $this->createMock(StreamInterface::class);
        $stream->expects($this->never())
          ->method('getContents')
          ->willReturn(self::JSON_DATA_OBJECT_STRING);

        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())
          ->method('getStatusCode')
          ->willReturn(404);

        $response->expects($this->never())
          ->method('getBody')
          ->willReturn($stream);

        $this->httpClient->expects($this->once())
          ->method('request')
          ->with('GET', self::ENDPOINT_STRING)
          ->willReturn($response);
        break;

      case 5:
        // Test a non-default language entity that does not have a base entity
        // created yet. Expect the PostponeQueueException.
        $notification = ['uuid' => self::ENTITY_UUID, 'langcode' => self::LANGCODE];

        $this->config->expects($this->once())
          ->method('get')
          ->with('api_main_hub')
          ->willReturn(self::HUB_URI);

        $this->urlAssembler->expects($this->exactly(2))
          ->method('assemble')
          ->willReturnOnConsecutiveCalls(self::HUB_URI, self::ENDPOINT_STRING);

        $stream = $this->createMock(StreamInterface::class);
        $stream->expects($this->once())
          ->method('getContents')
          ->willReturn(self::JSON_DATA_OBJECT_STRING);

        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())
          ->method('getStatusCode')
          ->willReturn(200);

        $response->expects($this->once())
          ->method('getBody')
          ->willReturn($stream);

        $this->httpClient->expects($this->once())
          ->method('request')
          ->with('GET', self::ENDPOINT_STRING)
          ->willReturn($response);

        $this->ecmsApiCreateNotification->expects($this->once())
          ->method('checkEntityUuidExists')
          ->with(self::ENTITY_UUID)
          ->willReturn(FALSE);

        $this->expectException('\Drupal\Core\Queue\PostponeItemException');
        break;
      case 6:
        // Test a non-default language entity that does have a base entity
        // created but a translation save error occurs.
        // Expect the RequeueException.
        $notification = ['uuid' => self::ENTITY_UUID, 'langcode' => self::LANGCODE];

        $this->config->expects($this->once())
          ->method('get')
          ->with('api_main_hub')
          ->willReturn(self::HUB_URI);

        $this->urlAssembler->expects($this->exactly(2))
          ->method('assemble')
          ->willReturnOnConsecutiveCalls(self::HUB_URI, self::ENDPOINT_STRING);

        $stream = $this->createMock(StreamInterface::class);
        $stream->expects($this->once())
          ->method('getContents')
          ->willReturn(self::JSON_DATA_OBJECT_STRING);

        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())
          ->method('getStatusCode')
          ->willReturn(200);

        $response->expects($this->once())
          ->method('getBody')
          ->willReturn($stream);

        $this->httpClient->expects($this->once())
          ->method('request')
          ->with('GET', self::ENDPOINT_STRING)
          ->willReturn($response);

        $this->ecmsApiCreateNotification->expects($this->once())
          ->method('checkEntityUuidExists')
          ->with(self::ENTITY_UUID)
          ->willReturn(TRUE);

        $jsonData = json_decode(self::JSON_DATA_OBJECT_STRING);
        $this->ecmsApiCreateNotification->expects($this->once())
          ->method('createNotificationTranslationFromJson')
          ->with($jsonData->data)
        ->willReturn(FALSE);

        $this->expectException('\Drupal\Core\Queue\RequeueException');
        break;

      case 7:
        // Test a non-default language entity that does have a base entity
        // created and a translation saves correctly.
        $notification = ['uuid' => self::ENTITY_UUID, 'langcode' => self::LANGCODE];

        $this->config->expects($this->once())
          ->method('get')
          ->with('api_main_hub')
          ->willReturn(self::HUB_URI);

        $this->urlAssembler->expects($this->exactly(2))
          ->method('assemble')
          ->willReturnOnConsecutiveCalls(self::HUB_URI, self::ENDPOINT_STRING);

        $stream = $this->createMock(StreamInterface::class);
        $stream->expects($this->once())
          ->method('getContents')
          ->willReturn(self::JSON_DATA_OBJECT_STRING);

        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())
          ->method('getStatusCode')
          ->willReturn(200);

        $response->expects($this->once())
          ->method('getBody')
          ->willReturn($stream);

        $this->httpClient->expects($this->once())
          ->method('request')
          ->with('GET', self::ENDPOINT_STRING)
          ->willReturn($response);

        $this->ecmsApiCreateNotification->expects($this->once())
          ->method('checkEntityUuidExists')
          ->with(self::ENTITY_UUID)
          ->willReturn(TRUE);

        $jsonData = json_decode(self::JSON_DATA_OBJECT_STRING);
        $this->ecmsApiCreateNotification->expects($this->once())
          ->method('createNotificationTranslationFromJson')
          ->with($jsonData->data)
          ->willReturn(TRUE);
        break;

      case 8:
        // Test a default language entity that does not an entity
        // created yet. Expect an error on node save.
        // Expect the RequeueException.
        $notification = ['uuid' => self::ENTITY_UUID, 'langcode' => self::DEFAULT_LANGCODE];

        $this->config->expects($this->once())
          ->method('get')
          ->with('api_main_hub')
          ->willReturn(self::HUB_URI);

        $this->urlAssembler->expects($this->exactly(2))
          ->method('assemble')
          ->willReturnOnConsecutiveCalls(self::HUB_URI, self::DEFAULT_ENDPOINT_STRING);

        $stream = $this->createMock(StreamInterface::class);
        $stream->expects($this->once())
          ->method('getContents')
          ->willReturn(self::JSON_DATA_OBJECT_STRING_DEFAULT);

        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())
          ->method('getStatusCode')
          ->willReturn(200);

        $response->expects($this->once())
          ->method('getBody')
          ->willReturn($stream);

        $this->httpClient->expects($this->once())
          ->method('request')
          ->with('GET', self::DEFAULT_ENDPOINT_STRING)
          ->willReturn($response);

        $this->ecmsApiCreateNotification->expects($this->once())
          ->method('checkEntityUuidExists')
          ->with(self::ENTITY_UUID)
          ->willReturn(FALSE);

        $jsonData = json_decode(self::JSON_DATA_OBJECT_STRING_DEFAULT);
        $this->ecmsApiCreateNotification->expects($this->once())
          ->method('createNotificationFromJson')
          ->with($jsonData->data)
          ->willReturn(FALSE);

        $this->expectException('\Drupal\Core\Queue\RequeueException');
        break;

      case 9:
        // Test a default language entity that does have an entity
        // created. Expect no node save to occur.
        $notification = ['uuid' => self::ENTITY_UUID, 'langcode' => self::DEFAULT_LANGCODE];

        $this->config->expects($this->once())
          ->method('get')
          ->with('api_main_hub')
          ->willReturn(self::HUB_URI);

        $this->urlAssembler->expects($this->exactly(2))
          ->method('assemble')
          ->willReturnOnConsecutiveCalls(self::HUB_URI, self::DEFAULT_ENDPOINT_STRING);

        $stream = $this->createMock(StreamInterface::class);
        $stream->expects($this->once())
          ->method('getContents')
          ->willReturn(self::JSON_DATA_OBJECT_STRING_DEFAULT);

        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())
          ->method('getStatusCode')
          ->willReturn(200);

        $response->expects($this->once())
          ->method('getBody')
          ->willReturn($stream);

        $this->httpClient->expects($this->once())
          ->method('request')
          ->with('GET', self::DEFAULT_ENDPOINT_STRING)
          ->willReturn($response);

        $this->ecmsApiCreateNotification->expects($this->once())
          ->method('checkEntityUuidExists')
          ->with(self::ENTITY_UUID)
          ->willReturn(TRUE);

        $jsonData = json_decode(self::JSON_DATA_OBJECT_STRING_DEFAULT);
        $this->ecmsApiCreateNotification->expects($this->never())
          ->method('createNotificationFromJson')
          ->with($jsonData->data);
        break;
    }

    $this->plugin->processItem($notification);
  }

  /**
   * Data provider for the testProcessItem method.
   *
   * @return array
   *  Arguments to pass to the testProcessItem method.
   */
  public function dataProviderForTestProcessItem(): array {
    return [
      'test1' => [1],
      'test2' => [2],
      'test3' => [3],
      'test4' => [4],
      'test5' => [5],
      'test6' => [6],
      'test7' => [7],
      'test8' => [8],
      'test9' => [9],
    ];
  }

}
