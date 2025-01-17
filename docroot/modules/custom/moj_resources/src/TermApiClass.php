<?php

namespace Drupal\moj_resources;

use Drupal\node\NodeInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Entity\Query\QueryFactory;
use Symfony\Component\Serializer\Serializer;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * PromotedContentApiClass
 */

class TermApiClass
{
  /**
   * Term
   *
   * @var array
   */
  protected $term;
  /**
   * Language Tag
   *
   * @var string
   */
  protected $lang;
  /**
   * Node_storage object
   *
   * @var Drupal\Core\Entity\EntityTypeManager
   */
  protected $node_storage;
  /**
   * Entitity Query object
   *
   * @var Drupal\Core\Entity\Query\QueryFactory
   *
   * Instance of querfactory
   */
  protected $entity_query;

  /**
   * Class Constructor
   *
   * @param EntityTypeManager $entityTypeManager
   * @param QueryFactory $entityQuery
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    QueryFactory $entityQuery
  ) {
    $this->term_storage = $entityTypeManager->getStorage('taxonomy_term');
    $this->entity_query = $entityQuery;
  }
  /**
   * API resource function
   *
   * @param [string] $lang
   * @param [string] $category
   * @return array
   */
  public function TermApiEndpoint($lang, $term_id)
  {
    $this->lang = $lang;
    $terms = $this->term_storage->load($term_id);
    return $this->decorateResponse($terms);
  }
  /**
   * Decorate term response
   *
   * @param Node $term
   * @return array
   */
  private function decorateResponse($term)
  {
    $result = [];
    $result['id'] = $term->tid->value;
    $result['content_type'] = $term->vid[0]->target_id;
    $result['title'] = $term->name->value;
    $result['description'] = $term->description[0];
    $result['summary'] = $term->field_content_summary ? $term->field_content_summary->value : '';
    $result['image'] = $term->field_featured_image[0];
    $result['video'] = $term->field_featured_video[0];
    $result['audio'] = $term->field_featured_audio[0];
    $result['programme_code'] = $term->field_feature_programme_code ? $term->field_feature_programme_code->value : '';

    return $result;
  }

  /**
   * TranslateNode function
   *
   * @param NodeInterface $term
   *
   * @return $term
   */
  protected function translateNode($term)
  {
    return $term->hasTranslation($this->lang) ? $term->getTranslation($this->lang) : $term;
  }
}
