<?php

declare(strict_types=1);

namespace Drupal\ecms_blocks\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the statewide search form that searches every RI.gov site.
 *
 * @package Drupal\ecms_blocks\Form
 */
class SearchStateBlockForm extends FormBase {

  /**
   * The route of the multisite search page provided by ecms_multisite_search.
   */
  public const MULTISITE_SEARCH_ROUTE = 'view.ecms_multisite_search.page_1';

  /**
   * The statewide search page, for sites that do not host multisite search.
   */
  public const STATE_SEARCH_URL = 'https://www.ri.gov/search-rhode-island';

  /**
   * The query parameter the multisite search view reads its keywords from.
   */
  public const SEARCH_PARAMETER = 'search_api_fulltext';

  /**
   * The route provider.
   *
   * @var \Drupal\Core\Routing\RouteProviderInterface
   */
  protected RouteProviderInterface $routeProvider;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    $instance = parent::create($container);
    $instance->routeProvider = $container->get('router.route_provider');
    return $instance;
  }

  /**
   * Builds the URL of the statewide search results for the given keywords.
   *
   * The site that hosts the multisite search view (RI.gov) links to its own
   * page, so searches stay on the site and work behind the reverse proxy.
   * Every other site links to the RI.gov page.
   *
   * @param string $keys
   *   The search keywords.
   * @param \Drupal\Core\Routing\RouteProviderInterface $route_provider
   *   The route provider.
   *
   * @return \Drupal\Core\Url
   *   The search results URL.
   */
  public static function buildSearchUrl(string $keys, RouteProviderInterface $route_provider): Url {
    $options = [
      'query' => [self::SEARCH_PARAMETER => $keys],
    ];

    if (!empty($route_provider->getRoutesByNames([self::MULTISITE_SEARCH_ROUTE]))) {
      return Url::fromRoute(self::MULTISITE_SEARCH_ROUTE, [], $options);
    }

    return Url::fromUri(self::STATE_SEARCH_URL, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'ecms_search_state_block_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {

    $form['search_input'] = [
      '#type' => 'textfield',
      "#placeholder" => $this->t('Search RI.gov'),
      '#required' => TRUE,
      "#attributes" => [
        'type' => "search",
        'aria-label' => $this->t("Search"),
      ],
      '#theme_wrappers' => [],
    ];

    $form['actions']['#type'] = 'actions';

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Search'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $url = static::buildSearchUrl((string) $form_state->getValue('search_input'), $this->routeProvider);

    $response = new TrustedRedirectResponse($url->setAbsolute()->toString());

    $metadata = $response->getCacheableMetadata();
    $metadata->setCacheMaxAge(0);

    $form_state->setResponse($response);
  }

}
