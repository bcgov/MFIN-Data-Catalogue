<?php

namespace Drupal\bc_dc\Plugin\Condition;

use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;

/**
 * Provides a condition for whether the user may edit a node.
 *
 * @Condition(
 *   id = "bc_dc_build_edit_access",
 *   label = @Translation("Build page access"),
 *   context_definitions = {
 *     "node" = @ContextDefinition(
 *       "entity:node",
 *       required = TRUE,
 *       label = @Translation("Node")
 *     )
 *   }
 * )
 */
class BuildEditAccess extends ConditionPluginBase {

  /**
   * {@inheritdoc}
   */
  public function evaluate(): bool {
    $entity = $this->getContextValue('node');

    if (!$entity) {
      return FALSE;
    }

    // Give access based on whether the user has access to the edit path for
    // section_1.
    $route_parameters = [
      'node' => $entity->id(),
    ];
    $options = [
      'query' => [
        'display' => 'section_1',
      ],
    ];
    $access = Url::fromRoute('entity.node.edit_form', $route_parameters, $options)->access();

    // Support negation.
    return $access xor $this->isNegated();
  }

  /**
   * {@inheritdoc}
   */
  public function summary(): TranslatableMarkup {
    return $this->t('User has access to edit the metadata record description.');
  }

}
