<?php

namespace Drupal\bc_dc\Plugin\Block;

use Drupal\bc_dc\Form\BcDcWorkflowBlockForm;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a workflow block for the data_set build page.
 *
 * @Block(
 *   id = "bc_dc_workflow_block",
 *   admin_label = @Translation("Metadata record workflow block"),
 *   context_definitions = {
 *     "node" = @ContextDefinition("entity:node")
 *   }
 * )
 */
class BcDcWorkflowBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Form\FormBuilderInterface $formBuilder
   *   The form_builder service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected FormBuilderInterface $formBuilder,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('form_builder'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $node = $this->getContextValue('node');

    $build = [];

    $build['workflow'] = $this->formBuilder->getForm(BcDcWorkflowBlockForm::class, ['node' => $node]);

    return $build;
  }

}
