<?php

namespace Drupal\Tests\prisoner_hub_entity_access\ExistingSite;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Url;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\jsonapi\Functional\JsonApiRequestTestTrait;
use GuzzleHttp\RequestOptions;
use weitzman\DrupalTestTraits\Entity\NodeCreationTrait;
use weitzman\DrupalTestTraits\Entity\TaxonomyCreationTrait;
use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * Test that the jsonapi responses with prison context return the correct results.
 *
 * @group prisoner_hub_entity_access
 */
class PrisonerHubQueryAccessTest extends ExistingSiteBase {

  use TaxonomyCreationTrait;
  use NodeCreationTrait;
  use JsonApiRequestTestTrait;

  /**
   * The "current" prison taxonomy term.
   *
   * @var \Drupal\taxonomy\Entity\Term
   */
  protected $prisonTerm;

  /**
   * Another prison taxonomy term, that is _not_ the "current".
   *
   * @var \Drupal\taxonomy\Entity\Term
   */
  protected $anotherPrisonTerm;

  /**
   * A prison category term, that is associated with the "current" prison.
   *
   * @var \Drupal\taxonomy\Entity\Term
   */
  protected $prisonCategoryTerm;

  /**
   * Another prison category term, that is _not_ associated with the "current" prison.
   *
   * @var \Drupal\taxonomy\Entity\Term
   */
  protected $anotherPrisonCategoryTerm;

  /**
   * The prison reference field name.
   *
   * @var String
   */
  protected $prisonFieldName;

  /**
   * The prison category reference field name.
   *
   * @var String
   */
  protected $prisonCategoryFieldName;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->prisonFieldName = $this->container->getParameter('prisoner_hub_entity_access.prison_field_name');
    $this->prisonCategoryFieldName = $this->container->getParameter('prisoner_hub_entity_access.category_field_name');

    $vocab_prison_categories = Vocabulary::load('prison_category');
    $this->prisonCategoryTerm = $this->createTerm($vocab_prison_categories);

    $vocab_prisons = Vocabulary::load('prisons');
    $values = [
      $this->prisonCategoryFieldName => [
        ['target_id' => $this->prisonCategoryTerm->id()],
      ],
    ];
    $this->prisonTerm = $this->createTerm($vocab_prisons, $values);
    $this->prisonTermMachineName = $this->prisonTerm->get('machine_name')->getValue()[0]['value'];

    // Create alternative prison and prison category taxonomy terms.
    // We will tag some content with this, to ensure it does not appear.
    $this->anotherPrisonCategoryTerm = $this->createTerm($vocab_prison_categories);
    $values = [
      $this->prisonCategoryFieldName => [
        ['target_id' => $this->anotherPrisonCategoryTerm->id()],
      ],
    ];
    $this->anotherPrisonTerm = $this->createTerm($vocab_prisons, $values);
  }

  /**
   * Test that no content appears when nothing is tagged with a prison or a prison
   * category.
   */
  public function testNoContentTaggedWithPrisonOrCategory() {
    // Create some nodes that have no values.
    for ($i = 0; $i < 5; $i++) {
      $values = [
        'type' => 'page',
      ];
      $this->createNode($values)->uuid();
    }
    $this->assertJsonResponseNodes([]);
  }

  /**
   * Test that content appears when tagged with a prison (but no category).
   */
  public function testContentTaggedWithPrisonButNoCategory() {
    $nodes_to_check = [];
    for ($i = 0; $i < 5; $i++) {
        $values = [
          'type' => 'page',
          $this->prisonFieldName => [
            ['target_id' => $this->prisonTerm->id()]
          ],
        ];
      $nodes_to_check[] = $this->createNode($values)->uuid();
    }

    // Also create some content tagged with a different prison.
    for ($i = 0; $i < 5; $i++) {
      $values = [
        'type' => 'page',
        $this->prisonFieldName => [
          ['target_id' => $this->anotherPrisonTerm->id()],
        ],
      ];
      $this->createNode($values);
    }

    $this->assertJsonResponseNodes($nodes_to_check);
  }

  /**
   * Test that content appears when tagged with a category (but no prison).
   */
  public function testContentTaggedWithCategoryButNoPrison() {
    $nodes_to_check = [];
    for ($i = 0; $i < 5; $i++) {
      $values = [
        'type' => 'page',
        $this->prisonCategoryFieldName => [
          ['target_id' => $this->prisonCategoryTerm->id()],
        ],
      ];
      $nodes_to_check[] = $this->createNode($values)->uuid();
    }

    // Also create some content tagged with a different prison category.
    for ($i = 0; $i < 5; $i++) {
      $values = [
        'type' => 'page',
        $this->prisonCategoryFieldName => [
          ['target_id' => $this->anotherPrisonCategoryTerm->id()],
        ],
      ];
      $this->createNode($values)->uuid();
    }

    $this->assertJsonResponseNodes($nodes_to_check);
  }

  /**
   * Test that content appears when tagged with a category and a prison.
   */
  public function testContentTaggedWithPrisonAndCategory() {
    $nodes_to_check = [];
    for ($i = 0; $i < 5; $i++) {
      $values = [
        'type' => 'page',
        $this->prisonFieldName => [
          ['target_id' => $this->prisonTerm->id()],
        ],
        $this->prisonCategoryFieldName => [
          ['target_id' => $this->prisonTerm->id()],
        ],
      ];
      $nodes_to_check[] = $this->createNode($values)->uuid();
    }

    // Also create some content tagged with a different category and prison.
    for ($i = 0; $i < 5; $i++) {
      $values = [
        'type' => 'page',
        $this->prisonFieldName => [
          ['target_id' => $this->anotherPrisonTerm->id()]
        ],
        $this->prisonCategoryFieldName => [
          ['target_id' => $this->anotherPrisonCategoryTerm->id()],
        ],
      ];
      $this->createNode($values)->uuid();
    }

    $this->assertJsonResponseNodes($nodes_to_check);
  }

  /**
   * Helper function to assert that a jsonapi response returns the expected nodes.
   *
   * @param $nodes
   */
  protected function assertJsonResponseNodes($nodes) {
    $request_options = [];
    $request_options[RequestOptions::HEADERS]['Accept'] = 'application/vnd.api+json';
    $url = Url::fromUri('internal:/jsonapi/prison/' . $this->prisonTermMachineName . '/node/page');
    $response = $this->request('GET', $url, $request_options);
    $this->assertSame(200, $response->getStatusCode(), var_export(Json::decode((string) $response->getBody()), TRUE));
    $response_document = Json::decode((string) $response->getBody());
    if (empty($nodes)) {
      $this->assertEmpty($response_document['data']);
    }
    else {
      $this->assertSame($nodes, array_map(static function (array $data) {
        return $data['id'];
      }, $response_document['data']));
    }
  }
}
