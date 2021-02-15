<?php

namespace Drupal\PFSHubContentAccess;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeAccessControlHandler;

class PFSHubNodeAccessControlHandler extends NodeAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  public function access(EntityInterface $entity, $operation, AccountInterface $account = NULL, $return_as_object = FALSE) {
    if ($entity->hasField('field_related_prisons')) {
      $result = $this->checkAccessByPrison($entity);
      if ($result->isNeutral()) {
        $result = $this->checkAccessByPrisonCategory($entity);
      }
    }
    return parent::access($entity, $operation, $account, $return_as_object); // TODO: Change the autogenerated stub
  }

  private function checkAccessByPrison(ContentEntityInterface $entity, ContentEntityInterface $prison_entity) {
    $related_prison_tids = $entity->get('field_related_prisons')->getValue();

    // Entity has at least one prison.
    if (!empty($related_prison_tids)) {
      foreach ($related_prison_tids as $value) {
        if ($value['target_id'] == $prison_entity->id()) {
          return AccessResult::allowed();
        }
      }
      return AccessResult::forbidden();
    }
    return AccessResult::neutral();
  }

  private function checkAccessByPrisonCategory(ContentEntityInterface $entity, ContentEntityInterface $prison_entity) {
    if ($entity->hasField('field_prison_categories')) {
      $prison_category_values = $prison_entity->get('field_prison_categories')->getValue();
      $prison_category_tids = array_column($prison_category_values, 'target_id');
      $entity_category_values = $entity->get('field_prison_categories')->getValue();
      $entity_category_tids = array_column($entity_category_values, 'target_id');
      if (empty(array_intersect($prison_category_tids,  $entity_category_tids))) {
        return AccessResult::forbidden();
      }
      else {
        return AccessResult::neutral();
      }
    }
  }
}
