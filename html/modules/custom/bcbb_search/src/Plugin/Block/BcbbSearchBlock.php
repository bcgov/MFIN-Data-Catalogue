<?php

namespace Drupal\bcbb_search\Plugin\Block;

use Drupal\bcbb_search\Form\BcbbSearchApiForm;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a custom search block.
 *
 * @package Drupal\bcbb_search\Plugin\Block
 *
 * @Block(
 *  id = "bcbb_search_api_block",
 *  admin_label = @Translation("BCbb Search API Block"),
 *  category = @Translation("BC Base"),
 * )
 */
final class BcbbSearchBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Constructs a BcbbSearchBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity_type.manager service.
   * @param \Drupal\Core\Form\FormBuilderInterface $formBuilder
   *   The form_builder service.
   */
  public function __construct(
    array $configuration,
    string $plugin_id,
    mixed $plugin_definition,
    protected EntityTypeManagerInterface $entityTypeManager,
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
      $container->get('entity_type.manager'),
      $container->get('form_builder'),
    );
  }

  /**
   * {@inheritdoc}
   *
   * This method sets the block default configuration.
   */
  public function defaultConfiguration(): array {
    return [
      'search' => [
        'search_label' => 'Search',
        'search_url' => '/search/site/',
      ],
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   *
   * This method defines form elements for custom block configuration.
   */
  public function blockForm($form, FormStateInterface $form_state): array {
    $form = parent::blockForm($form, $form_state);
    $config = $this->getConfiguration();

    $facetsList = [];

    // Store Facets block list.
    $facets = $this->entityTypeManager->getStorage('facets_facet')->getQuery()->accessCheck(FALSE)->execute();
    foreach ($facets as $value) {
      $facetsLoad = $this->entityTypeManager->getStorage('facets_facet')->load($value);

      // Is taxonomy term.
      if (isset($facetsLoad->getDataDefinition()->getSettings()['target_type']) && $facetsLoad->getDataDefinition()->getSettings()['target_type'] == 'taxonomy_term') {
        $facetsList[$value] = $facetsLoad->getName();
      }
    }

    $form['search'] = [
      '#title' => $this->t('Search configuration'),
      '#type' => 'details',
    ];

    $form['search']['label_sr_only'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Label shown on screen reader only'),
      '#default_value' => $config['search']['label_sr_only'] ?? FALSE,
    ];

    $form['search']['search_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $config['search']['search_label'] ?? NULL,
    ];

    $form['search']['search_placeholder'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Placeholder'),
      '#maxlength' => 255,
      '#default_value' => $config['search']['search_placeholder'] ?? NULL,
    ];

    $form['search']['search_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('URL'),
      '#maxlength' => 255,
      '#default_value' => $config['search']['search_url'],
      '#required' => TRUE,
      '#element_validate' => [[static::class, 'validatePath']],
    ];

    $form['search']['search_btn_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Button label'),
      '#maxlength' => 255,
      '#default_value' => $config['search']['search_btn_label'] ?? NULL,
    ];
    $form['search']['search_input_size'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Set search input size'),
      '#default_value' => $config['search']['search_input_size'] ?? NULL,
    ];

    $form['search']['search_input_size_value'] = [
      '#type' => 'number',
      '#title' => $this->t('Enter the size value for search input field.'),
      '#default_value' => $config['search']['search_input_size_value'] ?? NULL,
      '#min' => 0,
      '#max' => 999,
      '#step' => 1,
      '#states' => [
        'visible' => [
          ':input[name="settings[search][search_input_size]"]' => ['checked' => TRUE],
        ],
        'required' => [
          ':input[name="settings[search][search_input_size]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['search']['show_advanced_link'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show advanced search link'),
      '#default_value' => $config['search']['show_advanced_link'] ?? FALSE,
    ];

    $form['search']['show_reset_button'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show reset button'),
      '#default_value' => $config['search']['show_reset_button'] ?? FALSE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state): void {
    parent::blockSubmit($form, $form_state);
    $this->configuration['search'] = $form_state->getValue('search');
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $config = $this->getConfiguration();
    return $this->formBuilder->getForm(BcbbSearchApiForm::class, $config);
  }

  /**
   * Callback for #element_validate for search_url.
   */
  public static function validatePath(array &$element, FormStateInterface $form_state, array &$complete_form): void {
    // Ensure the path has a leading slash.
    if ($value = trim($element['#value'], '/')) {
      $value = '/' . $value;
      $form_state->setValueForElement($element, $value);
    }
    // Check to make sure the path exists after stripping slashes.
    else {
      $form_state->setErrorByName('path', t('Path is required.'));
    }
  }

}
