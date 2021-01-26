<?php

namespace Drupal\moj_jsonapi_prison_categories\Resource;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\jsonapi\ResourceResponse;
use Drupal\jsonapi_resources\Resource\EntityQueryResourceBase;
use Drupal\node\NodeInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

/**
 * WIP, this is just a start of a custom jsonapi response for tags.
 *
 * @internal
 */
class Tags extends EntityQueryResourceBase {

  /**
   * Process the resource request.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return \Drupal\jsonapi\ResourceResponse
   *   The response.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function process(Request $request): ResourceResponse {

    // First create a query of nodes to exclude.  Note it is not possible to
    // do this all in one query. See https://drupal.stackexchange.com/a/188989/4831
    // TODO: Add caching to this query, as it doest need to be run on every
    // pagination request.
    $query = $this->getEntityQuery('taxonomy_term')
      ->condition('vid', 'tags');
    //$group = $query->orConditionGroup();
    $query->condition('field_prison_categories', 'IS NULL');
    $query->condition('field_related_prisons', 'IS NULL');
    //$query->condition($group);

    $cacheability = new CacheableMetadata();

    $paginator = $this->getPaginatorForRequest($request);
    $paginator->applyToQuery($query, $cacheability);

    $data = $this->loadResourceObjectDataFromEntityQuery($query, $cacheability);

    $pagination_links = $paginator->getPaginationLinks($query, $cacheability);

    return $this->createJsonapiResponse($data, $request, 200, [], $pagination_links);
  }

  /**
   * {@inheritdoc}
   */
  public function getRouteResourceTypes(Route $route, string $route_name): array {
    return $this->getResourceTypesByEntityTypeId('taxonomy_term');
  }

}
