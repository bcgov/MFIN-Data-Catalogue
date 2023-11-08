<?php

namespace Drupal\bc_dc\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the edit section button.
 *
 * @Block(
 *   id = "bc_dc_edit_button",
 *   admin_label = @Translation("Edit section button"),
 *   context_definitions = {
 *     "node" = @ContextDefinition("entity:node")
 *   }
 * )
 */
class EditSectionBtn extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Constructs a EditSectionBtn object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity_type.manager service.
   * @param \Drupal\Core\Routing\RedirectDestinationInterface $redirectDestination
   *   The redirect.destination service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected RedirectDestinationInterface $redirectDestination,
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
      $container->get('entity_type.manager'),
      $container->get('redirect.destination'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state): array {
    $form['query_param'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Display mode key'),
      '#description' => $this->t('This will appear in the query string as the <code>display</code> parameter.'),
      '#default_value' => $this->configuration['query_param'] ?? NULL,
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state): void {
    $this->configuration['query_param'] = $form_state->getValue('query_param');
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $node = $this->getContextValue('node');

    $form_mode = $this->configuration['query_param'];

    $links = [];

    $route_parameters = [
      // When editing the layout, there is no node context. But there must be an
      // NID for Link::createFromRoute(). Default to 1 so the link is generated.
      // The link does not work anyway, so it does not matter if it points to
      // the wrong node.
      'node' => $node->id() ?? 1,
    ];

    // Import link.
    if ($form_mode === 'data_set_columns') {
      $link_options = [
        'attributes' => [
          'class' => [
            'btn',
            'btn-primary',
          ],
        ],
        'query' => [
          'destination' => $this->redirectDestination->get(),
        ],
      ];
      $links[] = Link::createFromRoute($this->t('Import data columns'), 'bc_dc.data_set_edit_add_columns', $route_parameters, $link_options);
    }

    // Edit links.
    $form_mode_label = $this->entityTypeManager
      ->getStorage('entity_form_mode')
      ->load('node.' . $form_mode)
      ->label();
    $link_options = [
      'attributes' => [
        'class' => [
          'btn',
          'btn-primary',
        ],
        'aria-label' => $this->t('Edit @form_mode_label', ['@form_mode_label' => $form_mode_label]),
      ],
      'query' => [
        'display' => $form_mode,
        'destination' => $this->redirectDestination->get(),
      ],
    ];
    $links[] = Link::createFromRoute($this->t('Edit'), 'entity.node.edit_form', $route_parameters, $link_options);

    // Remove links with no access.
    foreach ($links as $key => $link) {
      if ($link->getUrl()->access()) {
        $links[$key] = $link->toRenderable();
      }
      else {
        unset($links[$key]);
      }
    }

    // Assemble block.
    $build = [
      'block' => [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['edit-section-btn'],
        ],
      ],
    ];
    $build['block'] += $links;
    return $build;
  }

}
