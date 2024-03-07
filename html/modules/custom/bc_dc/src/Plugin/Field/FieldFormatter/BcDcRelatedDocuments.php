<?php

namespace Drupal\bc_dc\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Field Formatter that displays related documents as a list.
 *
 * @FieldFormatter(
 *   id = "bc_dc_related_documents",
 *   label = @Translation("Related documents list"),
 *   field_types = {
 *     "entity_reference_revisions"
 *   }
 * )
 */
class BcDcRelatedDocuments extends FormatterBase {

  /**
   * Constructor.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity_type.manager.
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    $label,
    $view_mode,
    array $third_party_settings,
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static($plugin_id, $plugin_definition, $configuration['field_definition'], $configuration['settings'], $configuration['label'], $configuration['view_mode'], $configuration['third_party_settings'], $container->get('entity_type.manager'));
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary[] = $this->t('Displays list of links to related documents.');
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode): array {
    $termStorage = $this->entityTypeManager->getStorage('taxonomy_term');

    // Make an array of column names.
    $links = [];
    foreach ($items as $item) {
      $tid = $item->entity?->field_paragraph_document_type->target_id;
      $term = $termStorage->load($tid);

      $uri = $item->entity?->field_paragraph_document_link;

      if ($term && $uri) {
        $links[] = Link::fromTextAndUrl($term->label(), Url::fromUri($uri->uri));
      }
    }

    if (!$links) {
      return [];
    }

    // Theme as unordered list.
    $list = [
      '#theme' => 'item_list',
      '#list_type' => 'ul',
      '#items' => $links,
    ];

    return [$list];
  }

}
