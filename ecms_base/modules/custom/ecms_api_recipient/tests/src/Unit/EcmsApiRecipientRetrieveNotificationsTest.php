<?php

declare(strict_types = 1);

namespace Drupal\Tests\ecms_api_recipient\Unit;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Queue\QueueInterface;
use Drupal\Core\Url;
use Drupal\Core\Utility\UnroutedUrlAssemblerInterface;
use Drupal\ecms_api_recipient\EcmsApiRecipientRetrieveNotifications;
use Drupal\Tests\UnitTestCase;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @group ecms_api_test
 */
class EcmsApiRecipientRetrieveNotificationsTest extends UnitTestCase {

  const JSON_OBJECT = '{"jsonapi":{"version":"1.0","meta":{"links":{"self":{"href":"http:\/\/jsonapi.org\/format\/1.0\/"}}}},"data":[{"type":"node--notification","id":"2e434fe8-0fcd-48ae-941e-ea78c4f348f7","links":{"self":{"href":"https:\/\/develop-ecms-profile.lndo.site\/EcmsApi\/node\/notification\/2e434fe8-0fcd-48ae-941e-ea78c4f348f7?resourceVersion=id%3A593"}},"attributes":{"drupal_internal__nid":218,"drupal_internal__vid":593,"langcode":"en","revision_timestamp":"2020-10-23T15:23:06+00:00","revision_log":"Bulk operation publish revision ","status":true,"title":"Notification - Cui Inhibeo (en)","created":"2020-10-20T03:49:23+00:00","changed":"2020-10-23T15:23:06+00:00","promote":false,"sticky":false,"default_langcode":false,"revision_translation_affected":true,"moderation_state":"published","path":{"alias":null,"pid":null,"langcode":"en"},"rh_action":null,"rh_redirect":null,"rh_redirect_response":null,"content_translation_source":"und","content_translation_outdated":false,"field_notification_expire_date":"2020-10-10T04:21:00+00:00","field_notification_global":true,"field_notification_text":"Caecus letalis oppeto ratis. Commoveo pneum populus rusticus sudo ulciscor. Appellatio defui gravis nimis. Os pecus validus. Blandit caecus diam iaceo nibh nimis scisco valde veniam. Ea elit virtus. Ad adipiscing elit iaceo laoreet luptatum pertineo tation tum vulputate.\n\nAliquam importunus natu nulla pagus pala praesent quadrum suscipit verto. Aptent ea facilisis occuro pecus populus te vicis. Abbas aliquam natu refero similis. Abdo abico dolor genitus iriure nutus paulatim quae volutpat.\n\nAutem huic nunc patria sagaciter scisco tego tum vel. Antehabeo brevitas eligo exerci pecus praemitto qui saepius te ymo. Autem nobis sed. Conventio os saepius secundum suscipere. Accumsan aptent elit natu te.\n\nDuis genitus iaceo in usitas valde. Augue ille imputo laoreet nutus velit veniam. Ad aliquip enim nunc pala pneum si tum veniam. Adipiscing melior neo proprius ratis typicus vulpes. Adipiscing inhibeo iustum premo.\n\nAbdo quis rusticus sagaciter scisco tego validus vero. Commoveo genitus iaceo imputo mos pertineo singularis. Blandit damnum exerci loquor oppeto patria proprius typicus utrum.\n\nAd adipiscing bene diam ibidem scisco secundum te. Esse illum metuo sudo ullamcorper valetudo vel. Abbas iriure jugis pneum si torqueo ulciscor ullamcorper. Decet dolus enim hendrerit jumentum paulatim pneum praesent. Conventio neque nulla refoveo saluto tamen virtus. Antehabeo duis erat facilisis lenis nimis refero suscipere. Acsi defui exputo roto te.\n\nImportunus lucidus magna populus saluto. Augue brevitas caecus capto ideo importunus mos praemitto singularis vindico. Amet caecus jumentum minim modo paratus pneum quidne refero tation. At consequat esca fere inhibeo minim nibh odio. Dolore facilisis genitus minim.\n\nEt neque usitas velit. Adipiscing ea praemitto proprius venio. Capto cogo damnum gravis vindico. Abbas quidne tum ymo. Capto quae ullamcorper vindico. Cogo huic magna paulatim validus zelus. Accumsan inhibeo nibh quis ullamcorper. Abigo causa pagus ratis. Aptent capto os saluto si verto vindico.\n\nDamnum dolor eligo euismod nutus praesent scisco singularis valde vulpes. Augue dolore lobortis lucidus luptatum obruo pala quidne uxor valetudo. Cogo dolus iaceo letalis luctus nobis oppeto quia quidem. Diam genitus loquor secundum. Adipiscing comis ea pagus voco. Commoveo genitus iaceo in inhibeo volutpat.\n\nMinim pecus probo quidem. Euismod nisl paulatim. Abbas capto feugiat huic nostrud premo. Antehabeo appellatio aptent gemino letalis nutus qui quibus sit verto. Facilisis genitus hendrerit loquor turpis uxor valetudo. Abigo at dignissim elit erat importunus jumentum pecus. Abdo commoveo distineo eros nibh quadrum saluto. Modo nulla pneum praemitto praesent quidne tincidunt torqueo ulciscor valetudo.\n\nCausa dolor gilvus refoveo sit. Eu hos illum lobortis neo saluto tego. Ex inhibeo jus macto nutus. Abdo gravis haero mos nunc rusticus. Abigo bene feugiat incassum nibh oppeto pertineo premo. Nisl nobis premo. Esca ibidem jugis odio refero saluto vereor. Distineo fere illum pagus persto veniam vulpes. Abluo antehabeo aptent dolor luptatum mauris plaga singularis ulciscor vulputate.\n\nFacilisis ibidem lobortis magna secundum typicus. Abdo ad consequat dolus elit iaceo si utinam. Cogo ideo pneum refoveo saluto vel. Aliquam erat euismod jus luctus natu uxor.\n\n"},"relationships":{"node_type":{"data":{"type":"node_type--node_type","id":"8dafd8ea-debc-4f84-91e8-78a781304d11"},"links":{"related":{"href":"https:\/\/develop-ecms-profile.lndo.site\/EcmsApi\/node\/notification\/2e434fe8-0fcd-48ae-941e-ea78c4f348f7\/node_type?resourceVersion=id%3A593"},"self":{"href":"https:\/\/develop-ecms-profile.lndo.site\/EcmsApi\/node\/notification\/2e434fe8-0fcd-48ae-941e-ea78c4f348f7\/relationships\/node_type?resourceVersion=id%3A593"}}},"revision_uid":{"data":{"type":"user--user","id":"8f102cd0-8202-4916-8641-f2da52ef7639"},"links":{"related":{"href":"https:\/\/develop-ecms-profile.lndo.site\/EcmsApi\/node\/notification\/2e434fe8-0fcd-48ae-941e-ea78c4f348f7\/revision_uid?resourceVersion=id%3A593"},"self":{"href":"https:\/\/develop-ecms-profile.lndo.site\/EcmsApi\/node\/notification\/2e434fe8-0fcd-48ae-941e-ea78c4f348f7\/relationships\/revision_uid?resourceVersion=id%3A593"}}},"uid":{"data":{"type":"user--user","id":"8f102cd0-8202-4916-8641-f2da52ef7639"},"links":{"related":{"href":"https:\/\/develop-ecms-profile.lndo.site\/EcmsApi\/node\/notification\/2e434fe8-0fcd-48ae-941e-ea78c4f348f7\/uid?resourceVersion=id%3A593"},"self":{"href":"https:\/\/develop-ecms-profile.lndo.site\/EcmsApi\/node\/notification\/2e434fe8-0fcd-48ae-941e-ea78c4f348f7\/relationships\/uid?resourceVersion=id%3A593"}}}}},{"type":"node--notification","id":"8d769f16-265e-47d3-a834-fefc17dc6d4b","links":{"self":{"href":"https:\/\/develop-ecms-profile.lndo.site\/EcmsApi\/node\/notification\/8d769f16-265e-47d3-a834-fefc17dc6d4b?resourceVersion=id%3A596"}},"attributes":{"drupal_internal__nid":219,"drupal_internal__vid":596,"langcode":"en","revision_timestamp":"2020-10-23T15:23:06+00:00","revision_log":"Bulk operation publish revision ","status":true,"title":"Notification - Probo Qui Rusticus (en)","created":"2020-10-22T09:54:25+00:00","changed":"2020-10-23T15:23:06+00:00","promote":true,"sticky":false,"default_langcode":false,"revision_translation_affected":true,"moderation_state":"published","path":{"alias":null,"pid":null,"langcode":"en"},"rh_action":null,"rh_redirect":null,"rh_redirect_response":null,"content_translation_source":"und","content_translation_outdated":false,"field_notification_expire_date":"2020-02-27T03:08:10+00:00","field_notification_global":false,"field_notification_text":""},"relationships":{"node_type":{"data":{"type":"node_type--node_type","id":"8dafd8ea-debc-4f84-91e8-78a781304d11"},"links":{"related":{"href":"https:\/\/develop-ecms-profile.lndo.site\/EcmsApi\/node\/notification\/8d769f16-265e-47d3-a834-fefc17dc6d4b\/node_type?resourceVersion=id%3A596"},"self":{"href":"https:\/\/develop-ecms-profile.lndo.site\/EcmsApi\/node\/notification\/8d769f16-265e-47d3-a834-fefc17dc6d4b\/relationships\/node_type?resourceVersion=id%3A596"}}},"revision_uid":{"data":{"type":"user--user","id":"8f102cd0-8202-4916-8641-f2da52ef7639"},"links":{"related":{"href":"https:\/\/develop-ecms-profile.lndo.site\/EcmsApi\/node\/notification\/8d769f16-265e-47d3-a834-fefc17dc6d4b\/revision_uid?resourceVersion=id%3A596"},"self":{"href":"https:\/\/develop-ecms-profile.lndo.site\/EcmsApi\/node\/notification\/8d769f16-265e-47d3-a834-fefc17dc6d4b\/relationships\/revision_uid?resourceVersion=id%3A596"}}},"uid":{"data":{"type":"user--user","id":"94676826-cd8a-4edc-bb7b-f3f7d906fa65"},"links":{"related":{"href":"https:\/\/develop-ecms-profile.lndo.site\/EcmsApi\/node\/notification\/8d769f16-265e-47d3-a834-fefc17dc6d4b\/uid?resourceVersion=id%3A596"},"self":{"href":"https:\/\/develop-ecms-profile.lndo.site\/EcmsApi\/node\/notification\/8d769f16-265e-47d3-a834-fefc17dc6d4b\/relationships\/uid?resourceVersion=id%3A596"}}}}},{"type":"node--notification","id":"1b6e0980-f185-4974-9e0e-88f341d06e6c","links":{"self":{"href":"https:\/\/develop-ecms-profile.lndo.site\/EcmsApi\/node\/notification\/1b6e0980-f185-4974-9e0e-88f341d06e6c?resourceVersion=id%3A599"}},"attributes":{"drupal_internal__nid":220,"drupal_internal__vid":599,"langcode":"en","revision_timestamp":"2020-10-23T15:23:06+00:00","revision_log":"Bulk operation publish revision ","status":true,"title":"Notification - Roto Sit (en)","created":"2020-10-21T22:25:46+00:00","changed":"2020-10-23T15:23:06+00:00","promote":false,"sticky":false,"default_langcode":false,"revision_translation_affected":true,"moderation_state":"published","path":{"alias":null,"pid":null,"langcode":"en"},"rh_action":null,"rh_redirect":null,"rh_redirect_response":null,"content_translation_source":"und","content_translation_outdated":false,"field_notification_expire_date":"2020-02-06T10:12:19+00:00","field_notification_global":false,"field_notification_text":""},"relationships":{"node_type":{"data":{"type":"node_type--node_type","id":"8dafd8ea-debc-4f84-91e8-78a781304d11"},"links":{"related":{"href":"https:\/\/develop-ecms-profile.lndo.site\/EcmsApi\/node\/notification\/1b6e0980-f185-4974-9e0e-88f341d06e6c\/node_type?resourceVersion=id%3A599"},"self":{"href":"https:\/\/develop-ecms-profile.lndo.site\/EcmsApi\/node\/notification\/1b6e0980-f185-4974-9e0e-88f341d06e6c\/relationships\/node_type?resourceVersion=id%3A599"}}},"revision_uid":{"data":{"type":"user--user","id":"8f102cd0-8202-4916-8641-f2da52ef7639"},"links":{"related":{"href":"https:\/\/develop-ecms-profile.lndo.site\/EcmsApi\/node\/notification\/1b6e0980-f185-4974-9e0e-88f341d06e6c\/revision_uid?resourceVersion=id%3A599"},"self":{"href":"https:\/\/develop-ecms-profile.lndo.site\/EcmsApi\/node\/notification\/1b6e0980-f185-4974-9e0e-88f341d06e6c\/relationships\/revision_uid?resourceVersion=id%3A599"}}},"uid":{"data":{"type":"user--user","id":"94676826-cd8a-4edc-bb7b-f3f7d906fa65"},"links":{"related":{"href":"https:\/\/develop-ecms-profile.lndo.site\/EcmsApi\/node\/notification\/1b6e0980-f185-4974-9e0e-88f341d06e6c\/uid?resourceVersion=id%3A599"},"self":{"href":"https:\/\/develop-ecms-profile.lndo.site\/EcmsApi\/node\/notification\/1b6e0980-f185-4974-9e0e-88f341d06e6c\/relationships\/uid?resourceVersion=id%3A599"}}}}},{"type":"node--notification","id":"6d8894e2-80e5-49ef-be37-d44b1f12f3e1","links":{"self":{"href":"https:\/\/develop-ecms-profile.lndo.site\/EcmsApi\/node\/notification\/6d8894e2-80e5-49ef-be37-d44b1f12f3e1?resourceVersion=id%3A602"}},"attributes":{"drupal_internal__nid":221,"drupal_internal__vid":602,"langcode":"en","revision_timestamp":"2020-10-23T15:23:06+00:00","revision_log":"Bulk operation publish revision ","status":true,"title":"Notification - Camur Verto (en)","created":"2020-10-19T18:05:46+00:00","changed":"2020-10-23T15:23:06+00:00","promote":false,"sticky":false,"default_langcode":false,"revision_translation_affected":true,"moderation_state":"published","path":{"alias":null,"pid":null,"langcode":"en"},"rh_action":null,"rh_redirect":null,"rh_redirect_response":null,"content_translation_source":"und","content_translation_outdated":false,"field_notification_expire_date":"2020-03-11T14:25:12+00:00","field_notification_global":true,"field_notification_text":""},"relationships":{"node_type":{"data":{"type":"node_type--node_type","id":"8dafd8ea-debc-4f84-91e8-78a781304d11"},"links":{"related":{"href":"https:\/\/develop-ecms-profile.lndo.site\/EcmsApi\/node\/notification\/6d8894e2-80e5-49ef-be37-d44b1f12f3e1\/node_type?resourceVersion=id%3A602"},"self":{"href":"https:\/\/develop-ecms-profile.lndo.site\/EcmsApi\/node\/notification\/6d8894e2-80e5-49ef-be37-d44b1f12f3e1\/relationships\/node_type?resourceVersion=id%3A602"}}},"revision_uid":{"data":{"type":"user--user","id":"8f102cd0-8202-4916-8641-f2da52ef7639"},"links":{"related":{"href":"https:\/\/develop-ecms-profile.lndo.site\/EcmsApi\/node\/notification\/6d8894e2-80e5-49ef-be37-d44b1f12f3e1\/revision_uid?resourceVersion=id%3A602"},"self":{"href":"https:\/\/develop-ecms-profile.lndo.site\/EcmsApi\/node\/notification\/6d8894e2-80e5-49ef-be37-d44b1f12f3e1\/relationships\/revision_uid?resourceVersion=id%3A602"}}},"uid":{"data":{"type":"user--user","id":"94fa5b81-7114-495c-88a6-f9fbb69f3696"},"links":{"related":{"href":"https:\/\/develop-ecms-profile.lndo.site\/EcmsApi\/node\/notification\/6d8894e2-80e5-49ef-be37-d44b1f12f3e1\/uid?resourceVersion=id%3A602"},"self":{"href":"https:\/\/develop-ecms-profile.lndo.site\/EcmsApi\/node\/notification\/6d8894e2-80e5-49ef-be37-d44b1f12f3e1\/relationships\/uid?resourceVersion=id%3A602"}}}}},{"type":"node--notification","id":"947192e7-232d-4caa-b9e4-82dbb50d2a41","links":{"self":{"href":"https:\/\/develop-ecms-profile.lndo.site\/EcmsApi\/node\/notification\/947192e7-232d-4caa-b9e4-82dbb50d2a41?resourceVersion=id%3A605"}},"attributes":{"drupal_internal__nid":222,"drupal_internal__vid":605,"langcode":"en","revision_timestamp":"2020-10-23T15:23:06+00:00","revision_log":"Bulk operation publish revision ","status":true,"title":"Notification - Eligo (en)","created":"2020-10-23T00:25:04+00:00","changed":"2020-10-23T15:23:06+00:00","promote":false,"sticky":false,"default_langcode":false,"revision_translation_affected":true,"moderation_state":"published","path":{"alias":null,"pid":null,"langcode":"en"},"rh_action":null,"rh_redirect":null,"rh_redirect_response":null,"content_translation_source":"und","content_translation_outdated":false,"field_notification_expire_date":"2019-11-12T00:36:31+00:00","field_notification_global":true,"field_notification_text":""},"relationships":{"node_type":{"data":{"type":"node_type--node_type","id":"8dafd8ea-debc-4f84-91e8-78a781304d11"},"links":{"related":{"href":"https:\/\/develop-ecms-profile.lndo.site\/EcmsApi\/node\/notification\/947192e7-232d-4caa-b9e4-82dbb50d2a41\/node_type?resourceVersion=id%3A605"},"self":{"href":"https:\/\/develop-ecms-profile.lndo.site\/EcmsApi\/node\/notification\/947192e7-232d-4caa-b9e4-82dbb50d2a41\/relationships\/node_type?resourceVersion=id%3A605"}}},"revision_uid":{"data":{"type":"user--user","id":"8f102cd0-8202-4916-8641-f2da52ef7639"},"links":{"related":{"href":"https:\/\/develop-ecms-profile.lndo.site\/EcmsApi\/node\/notification\/947192e7-232d-4caa-b9e4-82dbb50d2a41\/revision_uid?resourceVersion=id%3A605"},"self":{"href":"https:\/\/develop-ecms-profile.lndo.site\/EcmsApi\/node\/notification\/947192e7-232d-4caa-b9e4-82dbb50d2a41\/relationships\/revision_uid?resourceVersion=id%3A605"}}},"uid":{"data":{"type":"user--user","id":"94676826-cd8a-4edc-bb7b-f3f7d906fa65"},"links":{"related":{"href":"https:\/\/develop-ecms-profile.lndo.site\/EcmsApi\/node\/notification\/947192e7-232d-4caa-b9e4-82dbb50d2a41\/uid?resourceVersion=id%3A605"},"self":{"href":"https:\/\/develop-ecms-profile.lndo.site\/EcmsApi\/node\/notification\/947192e7-232d-4caa-b9e4-82dbb50d2a41\/relationships\/uid?resourceVersion=id%3A605"}}}}},{"type":"node--notification","id":"704a7d7f-4f4e-414a-968e-535c501d5a3d","links":{"self":{"href":"https:\/\/develop-ecms-profile.lndo.site\/EcmsApi\/node\/notification\/704a7d7f-4f4e-414a-968e-535c501d5a3d?resourceVersion=id%3A608"}},"attributes":{"drupal_internal__nid":223,"drupal_internal__vid":608,"langcode":"en","revision_timestamp":"2020-10-23T15:23:06+00:00","revision_log":"Bulk operation publish revision ","status":true,"title":"Notification - Conventio (en)","created":"2020-10-20T22:46:54+00:00","changed":"2020-10-23T15:23:06+00:00","promote":false,"sticky":false,"default_langcode":false,"revision_translation_affected":true,"moderation_state":"published","path":{"alias":null,"pid":null,"langcode":"en"},"rh_action":null,"rh_redirect":null,"rh_redirect_response":null,"content_translation_source":"und","content_translation_outdated":false,"field_notification_expire_date":"2020-09-07T03:55:41+00:00","field_notification_global":false,"field_notification_text":""},"relationships":{"node_type":{"data":{"type":"node_type--node_type","id":"8dafd8ea-debc-4f84-91e8-78a781304d11"},"links":{"related":{"href":"https:\/\/develop-ecms-profile.lndo.site\/EcmsApi\/node\/notification\/704a7d7f-4f4e-414a-968e-535c501d5a3d\/node_type?resourceVersion=id%3A608"},"self":{"href":"https:\/\/develop-ecms-profile.lndo.site\/EcmsApi\/node\/notification\/704a7d7f-4f4e-414a-968e-535c501d5a3d\/relationships\/node_type?resourceVersion=id%3A608"}}},"revision_uid":{"data":{"type":"user--user","id":"8f102cd0-8202-4916-8641-f2da52ef7639"},"links":{"related":{"href":"https:\/\/develop-ecms-profile.lndo.site\/EcmsApi\/node\/notification\/704a7d7f-4f4e-414a-968e-535c501d5a3d\/revision_uid?resourceVersion=id%3A608"},"self":{"href":"https:\/\/develop-ecms-profile.lndo.site\/EcmsApi\/node\/notification\/704a7d7f-4f4e-414a-968e-535c501d5a3d\/relationships\/revision_uid?resourceVersion=id%3A608"}}},"uid":{"data":{"type":"user--user","id":"8f102cd0-8202-4916-8641-f2da52ef7639"},"links":{"related":{"href":"https:\/\/develop-ecms-profile.lndo.site\/EcmsApi\/node\/notification\/704a7d7f-4f4e-414a-968e-535c501d5a3d\/uid?resourceVersion=id%3A608"},"self":{"href":"https:\/\/develop-ecms-profile.lndo.site\/EcmsApi\/node\/notification\/704a7d7f-4f4e-414a-968e-535c501d5a3d\/relationships\/uid?resourceVersion=id%3A608"}}}}},{"type":"node--notification","id":"3df55480-cc9c-42da-b98e-2e81bb7a15f1","links":{"self":{"href":"https:\/\/develop-ecms-profile.lndo.site\/EcmsApi\/node\/notification\/3df55480-cc9c-42da-b98e-2e81bb7a15f1?resourceVersion=id%3A611"}},"attributes":{"drupal_internal__nid":224,"drupal_internal__vid":611,"langcode":"en","revision_timestamp":"2020-10-23T15:23:06+00:00","revision_log":"Bulk operation publish revision ","status":true,"title":"Notification - Facilisis Suscipere (en)","created":"2020-10-18T07:39:52+00:00","changed":"2020-10-23T15:23:06+00:00","promote":false,"sticky":false,"default_langcode":false,"revision_translation_affected":true,"moderation_state":"published","path":{"alias":null,"pid":null,"langcode":"en"},"rh_action":null,"rh_redirect":null,"rh_redirect_response":null,"content_translation_source":"und","content_translation_outdated":false,"field_notification_expire_date":"2020-03-29T07:53:56+00:00","field_notification_global":false,"field_notification_text":""},"relationships":{"node_type":{"data":{"type":"node_type--node_type","id":"8dafd8ea-debc-4f84-91e8-78a781304d11"},"links":{"related":{"href":"https:\/\/develop-ecms-profile.lndo.site\/EcmsApi\/node\/notification\/3df55480-cc9c-42da-b98e-2e81bb7a15f1\/node_type?resourceVersion=id%3A611"},"self":{"href":"https:\/\/develop-ecms-profile.lndo.site\/EcmsApi\/node\/notification\/3df55480-cc9c-42da-b98e-2e81bb7a15f1\/relationships\/node_type?resourceVersion=id%3A611"}}},"revision_uid":{"data":{"type":"user--user","id":"8f102cd0-8202-4916-8641-f2da52ef7639"},"links":{"related":{"href":"https:\/\/develop-ecms-profile.lndo.site\/EcmsApi\/node\/notification\/3df55480-cc9c-42da-b98e-2e81bb7a15f1\/revision_uid?resourceVersion=id%3A611"},"self":{"href":"https:\/\/develop-ecms-profile.lndo.site\/EcmsApi\/node\/notification\/3df55480-cc9c-42da-b98e-2e81bb7a15f1\/relationships\/revision_uid?resourceVersion=id%3A611"}}},"uid":{"data":{"type":"user--user","id":"8f102cd0-8202-4916-8641-f2da52ef7639"},"links":{"related":{"href":"https:\/\/develop-ecms-profile.lndo.site\/EcmsApi\/node\/notification\/3df55480-cc9c-42da-b98e-2e81bb7a15f1\/uid?resourceVersion=id%3A611"},"self":{"href":"https:\/\/develop-ecms-profile.lndo.site\/EcmsApi\/node\/notification\/3df55480-cc9c-42da-b98e-2e81bb7a15f1\/relationships\/uid?resourceVersion=id%3A611"}}}}},{"type":"node--notification","id":"8f1bab1d-ad94-44bd-ae8f-b3ceb1f31d5f","links":{"self":{"href":"https:\/\/develop-ecms-profile.lndo.site\/EcmsApi\/node\/notification\/8f1bab1d-ad94-44bd-ae8f-b3ceb1f31d5f?resourceVersion=id%3A614"}},"attributes":{"drupal_internal__nid":225,"drupal_internal__vid":614,"langcode":"en","revision_timestamp":"2020-10-23T15:23:06+00:00","revision_log":"Bulk operation publish revision ","status":true,"title":"Notification - Cui Gravis Turpis","created":"2020-10-21T05:00:10+00:00","changed":"2020-10-23T15:23:06+00:00","promote":true,"sticky":false,"default_langcode":true,"revision_translation_affected":true,"moderation_state":"published","path":{"alias":null,"pid":null,"langcode":"en"},"rh_action":null,"rh_redirect":null,"rh_redirect_response":null,"content_translation_source":"und","content_translation_outdated":false,"field_notification_expire_date":"2020-06-24T16:39:52+00:00","field_notification_global":true,"field_notification_text":""},"relationships":{"node_type":{"data":{"type":"node_type--node_type","id":"8dafd8ea-debc-4f84-91e8-78a781304d11"},"links":{"related":{"href":"https:\/\/develop-ecms-profile.lndo.site\/EcmsApi\/node\/notification\/8f1bab1d-ad94-44bd-ae8f-b3ceb1f31d5f\/node_type?resourceVersion=id%3A614"},"self":{"href":"https:\/\/develop-ecms-profile.lndo.site\/EcmsApi\/node\/notification\/8f1bab1d-ad94-44bd-ae8f-b3ceb1f31d5f\/relationships\/node_type?resourceVersion=id%3A614"}}},"revision_uid":{"data":{"type":"user--user","id":"8f102cd0-8202-4916-8641-f2da52ef7639"},"links":{"related":{"href":"https:\/\/develop-ecms-profile.lndo.site\/EcmsApi\/node\/notification\/8f1bab1d-ad94-44bd-ae8f-b3ceb1f31d5f\/revision_uid?resourceVersion=id%3A614"},"self":{"href":"https:\/\/develop-ecms-profile.lndo.site\/EcmsApi\/node\/notification\/8f1bab1d-ad94-44bd-ae8f-b3ceb1f31d5f\/relationships\/revision_uid?resourceVersion=id%3A614"}}},"uid":{"data":{"type":"user--user","id":"94fa5b81-7114-495c-88a6-f9fbb69f3696"},"links":{"related":{"href":"https:\/\/develop-ecms-profile.lndo.site\/EcmsApi\/node\/notification\/8f1bab1d-ad94-44bd-ae8f-b3ceb1f31d5f\/uid?resourceVersion=id%3A614"},"self":{"href":"https:\/\/develop-ecms-profile.lndo.site\/EcmsApi\/node\/notification\/8f1bab1d-ad94-44bd-ae8f-b3ceb1f31d5f\/relationships\/uid?resourceVersion=id%3A614"}}}}},{"type":"node--notification","id":"c4ffea78-57da-45be-930c-2105898ccc7b","links":{"self":{"href":"https:\/\/develop-ecms-profile.lndo.site\/EcmsApi\/node\/notification\/c4ffea78-57da-45be-930c-2105898ccc7b?resourceVersion=id%3A617"}},"attributes":{"drupal_internal__nid":226,"drupal_internal__vid":617,"langcode":"en","revision_timestamp":"2020-10-23T15:23:06+00:00","revision_log":"Bulk operation publish revision ","status":true,"title":"Notification - Ratis","created":"2020-10-20T18:30:53+00:00","changed":"2020-10-23T15:23:06+00:00","promote":false,"sticky":false,"default_langcode":true,"revision_translation_affected":true,"moderation_state":"published","path":{"alias":null,"pid":null,"langcode":"en"},"rh_action":null,"rh_redirect":null,"rh_redirect_response":null,"content_translation_source":"und","content_translation_outdated":false,"field_notification_expire_date":"2019-11-01T15:53:28+00:00","field_notification_global":false,"field_notification_text":""},"relationships":{"node_type":{"data":{"type":"node_type--node_type","id":"8dafd8ea-debc-4f84-91e8-78a781304d11"},"links":{"related":{"href":"https:\/\/develop-ecms-profile.lndo.site\/EcmsApi\/node\/notification\/c4ffea78-57da-45be-930c-2105898ccc7b\/node_type?resourceVersion=id%3A617"},"self":{"href":"https:\/\/develop-ecms-profile.lndo.site\/EcmsApi\/node\/notification\/c4ffea78-57da-45be-930c-2105898ccc7b\/relationships\/node_type?resourceVersion=id%3A617"}}},"revision_uid":{"data":{"type":"user--user","id":"8f102cd0-8202-4916-8641-f2da52ef7639"},"links":{"related":{"href":"https:\/\/develop-ecms-profile.lndo.site\/EcmsApi\/node\/notification\/c4ffea78-57da-45be-930c-2105898ccc7b\/revision_uid?resourceVersion=id%3A617"},"self":{"href":"https:\/\/develop-ecms-profile.lndo.site\/EcmsApi\/node\/notification\/c4ffea78-57da-45be-930c-2105898ccc7b\/relationships\/revision_uid?resourceVersion=id%3A617"}}},"uid":{"data":{"type":"user--user","id":"94676826-cd8a-4edc-bb7b-f3f7d906fa65"},"links":{"related":{"href":"https:\/\/develop-ecms-profile.lndo.site\/EcmsApi\/node\/notification\/c4ffea78-57da-45be-930c-2105898ccc7b\/uid?resourceVersion=id%3A617"},"self":{"href":"https:\/\/develop-ecms-profile.lndo.site\/EcmsApi\/node\/notification\/c4ffea78-57da-45be-930c-2105898ccc7b\/relationships\/uid?resourceVersion=id%3A617"}}}}},{"type":"node--notification","id":"f362f948-8452-4c2d-aab7-6131f7dbd0b5","links":{"self":{"href":"https:\/\/develop-ecms-profile.lndo.site\/EcmsApi\/node\/notification\/f362f948-8452-4c2d-aab7-6131f7dbd0b5?resourceVersion=id%3A620"}},"attributes":{"drupal_internal__nid":227,"drupal_internal__vid":620,"langcode":"en","revision_timestamp":"2020-10-23T15:23:06+00:00","revision_log":"Bulk operation publish revision ","status":true,"title":"Notification - Augue Oppeto Torqueo","created":"2020-10-19T13:01:23+00:00","changed":"2020-10-23T15:23:06+00:00","promote":false,"sticky":false,"default_langcode":true,"revision_translation_affected":true,"moderation_state":"published","path":{"alias":null,"pid":null,"langcode":"en"},"rh_action":null,"rh_redirect":null,"rh_redirect_response":null,"content_translation_source":"und","content_translation_outdated":false,"field_notification_expire_date":"2020-01-17T08:08:18+00:00","field_notification_global":false,"field_notification_text":""},"relationships":{"node_type":{"data":{"type":"node_type--node_type","id":"8dafd8ea-debc-4f84-91e8-78a781304d11"},"links":{"related":{"href":"https:\/\/develop-ecms-profile.lndo.site\/EcmsApi\/node\/notification\/f362f948-8452-4c2d-aab7-6131f7dbd0b5\/node_type?resourceVersion=id%3A620"},"self":{"href":"https:\/\/develop-ecms-profile.lndo.site\/EcmsApi\/node\/notification\/f362f948-8452-4c2d-aab7-6131f7dbd0b5\/relationships\/node_type?resourceVersion=id%3A620"}}},"revision_uid":{"data":{"type":"user--user","id":"8f102cd0-8202-4916-8641-f2da52ef7639"},"links":{"related":{"href":"https:\/\/develop-ecms-profile.lndo.site\/EcmsApi\/node\/notification\/f362f948-8452-4c2d-aab7-6131f7dbd0b5\/revision_uid?resourceVersion=id%3A620"},"self":{"href":"https:\/\/develop-ecms-profile.lndo.site\/EcmsApi\/node\/notification\/f362f948-8452-4c2d-aab7-6131f7dbd0b5\/relationships\/revision_uid?resourceVersion=id%3A620"}}},"uid":{"data":{"type":"user--user","id":"94fa5b81-7114-495c-88a6-f9fbb69f3696"},"links":{"related":{"href":"https:\/\/develop-ecms-profile.lndo.site\/EcmsApi\/node\/notification\/f362f948-8452-4c2d-aab7-6131f7dbd0b5\/uid?resourceVersion=id%3A620"},"self":{"href":"https:\/\/develop-ecms-profile.lndo.site\/EcmsApi\/node\/notification\/f362f948-8452-4c2d-aab7-6131f7dbd0b5\/relationships\/uid?resourceVersion=id%3A620"}}}}}],"links":{"next":{"href":"https:\/\/develop-ecms-profile.lndo.site\/EcmsApi\/node\/notification?page%5Boffset%5D=10\u0026page%5Blimit%5D=10"},"self":{"href":"https:\/\/develop-ecms-profile.lndo.site\/EcmsApi\/node\/notification?page%5Blimit%5D=10"}}}';
  const LANGUAGE_CODES = [
    'en',
    'es',
    'pt-pt',
    'de',
  ];

  const HUB_URI = 'https://oomphinc.com';

  const QUERY_STRING = 'page%5Blimit%5D=10&filter%5Bglobal%5D%5Bcondition%5D%5Bpath%5D=field_notification_global&filter%5Bglobal%5D%5Bcondition%5D%5Boperator%5D=%3D&filter%5Bglobal%5D%5Bcondition%5D%5Bvalue%5D=1';

  /**
   * The ecms_api_recipient.settings configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  private $config;

  private $notificationQueue;
  private $pagerQueue;

  /**
   * The http_client service.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  private $httpClient;

  /**
   * The language_manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  private $languageManager;

  private $urlAssembler;

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->pagerQueue = $this->createMock(QueueInterface::class);
    $this->notificationQueue = $this->createMock(QueueInterface::class);
    $this->config = $this->createMock(ImmutableConfig::class);
    $this->languageManager = $this->createMock(LanguageManagerInterface::class);
    $this->httpClient = $this->createMock(ClientInterface::class);
    $this->urlAssembler = $this->createMock(UnroutedUrlAssemblerInterface::class);

  }

  /**
   * Get the test class.
   *
   * @return \Drupal\ecms_api_recipient\EcmsApiRecipientRetrieveNotifications
   *   The class to test with.
   */
  private function getTestClass(): EcmsApiRecipientRetrieveNotifications {
    $queueFactory = $this->createMock(QueueFactory::class);
    $configFactory = $this->createMock(ConfigFactoryInterface::class);

    $configFactory->expects($this->once())
      ->method('get')
      ->with('ecms_api_recipient.settings')
      ->willReturn($this->config);

    $queueFactory->expects($this->exactly(2))
      ->method('get')
      ->withConsecutive(
        ['ecms_api_recipient_notification_creation_queue'],
        ['ecms_api_recipient_notification_pager_queue']
      )
      ->willReturnOnConsecutiveCalls(
        $this->notificationQueue,
        $this->pagerQueue
      );

    $container = new ContainerBuilder();
    $container->set('unrouted_url_assembler', $this->urlAssembler);
    \Drupal::setContainer($container);

    return new EcmsApiRecipientRetrieveNotifications(
      $configFactory,
      $queueFactory,
      $this->httpClient,
      $this->languageManager,
    );
  }

  /**
   * Test the retrieveNotificationsFromHub method.
   *
   * @param int $testNumber
   *   The test number being run.
   *
   * @dataProvider dataProviderForTestRetrieveNotificationsFromHub
   */
  public function testRetrieveNotificationsFromHub(int $testNumber): void {

    $languageArray = [];
    $apiEndpoint = self::HUB_URI;

    switch ($testNumber) {
      case 1:
        $apiEndpoint = '';
        break;
      case 2:
        $apiEndpoint = 'invalid-url';
        break;
      case 3:
        $apiEndpoint = self::HUB_URI;

        $response = $this->createMock(ResponseInterface::class);

        $response->expects($this->exactly(count(self::LANGUAGE_CODES)))
          ->method('getStatusCode')
          ->willReturn(404);

        $this->httpClient->expects($this->exactly(count(self::LANGUAGE_CODES)))
          ->method('request')
          ->willReturn($response);

        break;

      case 4:
        $apiEndpoint = self::HUB_URI;

        $exception = $this->createMock(GuzzleException::class);

        $this->httpClient->expects($this->exactly(count(self::LANGUAGE_CODES)))
          ->method('request')
          ->willThrowException($exception);
        break;
      case 5:
        $apiEndpoint = self::HUB_URI;

        $stream = $this->createMock(StreamInterface::class);
        $stream->expects($this->exactly(count(self::LANGUAGE_CODES)))
          ->method('getContents')
          ->willReturn(self::JSON_OBJECT);

        $response = $this->createMock(ResponseInterface::class);

        $response->expects($this->exactly(count(self::LANGUAGE_CODES)))
          ->method('getStatusCode')
          ->willReturn(200);

        $response->expects($this->exactly(count(self::LANGUAGE_CODES)))
          ->method('getBody')
          ->willReturn($stream);

        $this->httpClient->expects($this->exactly(count(self::LANGUAGE_CODES)))
          ->method('request')
          ->willReturn($response);

        $this->notificationQueue->expects($this->exactly(count(self::LANGUAGE_CODES) * 10))
          ->method('createItem');

        $this->pagerQueue->expects($this->exactly(count(self::LANGUAGE_CODES)))
          ->method('createItem');

        break;
    }

    // Define our language mocks.
    foreach (self::LANGUAGE_CODES as $code) {
      $default = FALSE;

      if ($code === 'en') {
        $default = TRUE;
      }

      $languageMock = $this->createMock(LanguageInterface::class);
      $languageMock->expects($this->once())
        ->method('getId')
        ->willReturn($code);

      $languageMock->expects($this->once())
        ->method('isDefault')
        ->willReturn($default);

      $languageArray[$code] = $languageMock;
    }

    $this->languageManager->expects($this->once())
      ->method('getLanguages')
      ->willReturn($languageArray);

    $this->config->expects($this->exactly(count(self::LANGUAGE_CODES)))
      ->method('get')
      ->with('api_main_hub')
      ->willReturn($apiEndpoint);

    $this->urlAssembler->expects($this->any())
      ->method('assemble')
      ->willReturn($apiEndpoint);

    $testClass = $this->getTestClass();

    // Test the retrieveNotificationsFromHub method.
    $testClass->retrieveNotificationsFromHub();
  }

  /**
   * Data provider for testRetrieveNotificationsFromHub.
   *
   * @return array
   *   The parameters to pass to the testRetrieveNotificationsFromHub method.
   */
  public function dataProviderForTestRetrieveNotificationsFromHub() :array {
    return [
      'test1' => [1],
      'test2' => [2],
      'test3' => [3],
      'test4' => [4],
      'test5' => [5],
    ];
  }

}
