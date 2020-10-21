<?php

declare(strict_types = 1);

namespace Drupal\ecms_api_recipient;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\jsonapi\Normalizer\JsonApiDocumentTopLevelNormalizer;
use Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Class JsonapiHelper.
 *
 * @package Drupal\ecms_api_recipient\Service
 */
class JsonapiHelper {

  use StringTranslationTrait;

  /**
   * The JsonApiDocumentTopLevelNormalizer normalizer.
   *
   * @var \Drupal\jsonapi\Normalizer\JsonApiDocumentTopLevelNormalizer
   */
  protected $jsonapiDocumentTopLevelNormalizer;

  /**
   * The resource type repository.
   *
   * @var \Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface
   */
  protected $resourceTypeRepository;

  /**
   * JsonapiHelper constructor.
   *
   * @param \Symfony\Component\Serializer\SerializerInterface $serializer
   *   A serializer.
   * @param \Drupal\jsonapi\Normalizer\JsonApiDocumentTopLevelNormalizer $jsonapi_document_top_level_normalizer
   *   The JsonApiDocumentTopLevelNormalizer normalizer.
   * @param \Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface $resource_type_repository
   *   The resource type repository.
   */
  public function __construct(
    SerializerInterface $serializer,
    JsonApiDocumentTopLevelNormalizer $jsonapi_document_top_level_normalizer,
    ResourceTypeRepositoryInterface $resource_type_repository
  ) {
    $this->jsonapiDocumentTopLevelNormalizer = $jsonapi_document_top_level_normalizer;
    $this->jsonapiDocumentTopLevelNormalizer->setSerializer($serializer);
    $this->resourceTypeRepository = $resource_type_repository;
  }


  public function extractEntity(array $data) {
    // Format JSON as in
    // JsonApiDocumentTopLevelNormalizerTest::testDenormalize().
    $prepared_json = [
      'data' => [
        'type' => $data['type'],
        'attributes' => $data['attributes'],
      ],
    ];

    return $this->jsonapiDocumentTopLevelNormalizer->denormalize($prepared_json, NULL, 'api_json', [
      'resource_type' => $this->resourceTypeRepository->getByTypeName($data['type']),
    ]);
  }

  public function convertJsonDataToArray($data) {
    if (is_object($data)) {
      $data = (array) $data;
    }

    if (is_array($data)) {
      $new = [];
      foreach ($data as $key => $val) {
        $new[$key] = $this->convertJsonDataToArray($val);
      }
    }
    else {
      $new = $data;
    };

    return $new;
  }


}
