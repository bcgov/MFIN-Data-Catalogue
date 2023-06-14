<?php

namespace Drupal\op_ext_search\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Url;
use Drupal\search_api\ParseMode\ParseModePluginManager;
use Drupal\taxonomy\Entity\Term;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Builds the search form for the search block.
 *
 * This is used by CustomSearchBlock::build().
 */
class CustomSearchApiForm extends FormBase {

  /**
   * Constructs a CustomSearchApiForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity_type.manager service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   The language_manager service.
   * @param \Drupal\search_api\ParseMode\ParseModePluginManager $pluginManagerSearchApiParseMode
   *   The plugin.manager.search_api.parse_mode service.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LanguageManagerInterface $languageManager,
    protected ParseModePluginManager $pluginManagerSearchApiParseMode,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('language_manager'),
      $container->get('plugin.manager.search_api.parse_mode'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'custom_search_api_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, array $config = NULL): array {
    $lang = $this->languageManager->getCurrentLanguage()->getId();

    $form['search_url'] = [
      '#type' => 'hidden',
      '#value' => $config['search']['search_url'],
    ];

    $form['search_keyword'] = [
      '#type' => 'textfield',
      '#title' => !empty($config['search']['search_label']) ? $config['search']['search_label'] : $this->t('Search'),
      '#maxlength' => 255,
      '#placeholder' => !empty($config['search']['search_placeholder']) ? $config['search']['search_placeholder'] : '',
    ];

    if (!empty($config['search']['label_sr_only'])) {
      $form['search_keyword']['#title_display'] = 'invisible';
    }

    if (isset($config['search']['search_input_size'])) {
      $form['search_keyword']['#size'] = $config['search']['search_input_size'] ? $config['search']['search_input_size_value'] : NULL;
    }

    if (!empty($config['facets'])) {
      foreach ($config['facets'] as $facet_key => $settings) {
        // If the facet is enabled.
        if ($settings['enabled']) {
          // Get field name from facet.
          $facet = $this->entityTypeManager->getStorage('facets_facet')->load($facet_key);
          $facet_field = $facet->getFieldAlias();

          $facet_items = $this->getFacetResults($facet_field);

          if (!empty($facet_items)) {
            $options = ['_none' => $this->t('All')];

            foreach ($facet_items as $tid) {
              // Get term name.
              $term = $this->entityTypeManager->getStorage('taxonomy_term')->load($tid);
              if ($term instanceof Term) {
                if ($term->hasTranslation($lang)) {
                  $term = $term->getTranslation($lang);
                }
                $name = $term->getName();
                $options[$facet_key . ':' . $tid] = $name;
              }
            }

            $form['facets_' . $facet_key] = [
              '#type' => 'select',
              '#title' => $settings['override_title'] ? $settings['title'] : $facet->getName(),
              '#options' => $options,
            ];
          }
        }
      }
    }

    // Add a submit button that handles the submission of the form.
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => !empty($config['search']['search_btn_label']) ? $config['search']['search_btn_label'] : $this->t('Submit'),
      '#attributes' => [
        'class' => ['btn', 'btn-default'],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $formState = $form_state->getValues();
    $query = NULL;

    // Get search terms.
    $formKeyword = $form_state->getValue('search_keyword');

    if (!empty($formKeyword)) {
      $query = ['search_api_fulltext' => $formKeyword];
    }

    // Get existing query params from block config.
    $formAction = $form_state->getValue('search_url');

    $url_components = parse_url($formAction);

    if (!empty($url_components['query'])) {
      parse_str($url_components['query'], $params);
      if (!empty($params['f'])) {
        foreach ($params['f'] as $f) {
          $query['f'][] = $f;
        }
      }
    }

    // Get query param from user selection.
    foreach ($formState as $key => $value) {
      if (strpos($key, 'facets_') !== FALSE && $value != '_none') {
        $query['f'][] = $value;
      }
    }

    // Build search URL.
    $url = Url::fromUserInput($formAction, ['query' => $query]);
    $form_state->setRedirectUrl($url);
  }

  /**
   * Get the facet results.
   *
   * @param string $field
   *   The facet field.
   *
   * @return array
   *   Array of facet options.
   */
  public function getFacetResults(string $field): array {
    $index = $this->entityTypeManager->getStorage('search_api_index')->load('default_index');
    $query = $index->query();

    // Change the parse mode for the search.
    $parse_mode = $this->pluginManagerSearchApiParseMode
      ->createInstance('direct');
    $parse_mode->setConjunction('OR');
    $query->setParseMode($parse_mode);

    // Set fields.
    $query->setFulltextFields([$field]);

    // Set additional conditions.
    $query->addCondition('status', 1);

    // Restrict the search to specific languages.
    $query->setLanguages(['en', 'fr']);

    // Set additional options.
    // (In this case, retrieve facets, if supported by the backend.)
    $server = $index->getServerInstance();
    if ($server->supportsFeature('search_api_facets')) {
      $query->setOption('search_api_facets', [
        'type' => [
          'field' => 'type',
          'limit' => 20,
          'operator' => 'and',
          'min_count' => 1,
          'missing' => TRUE,
        ],
      ]);
    }

    // Execute the search.
    $results = $query->execute();
    $facet_options = [];

    foreach ($results as $result) {
      $value = $result->getField($field)->getValues();
      if (!empty($value[0]) && !in_array($value[0], $facet_options)) {
        $facet_options[] = $value[0];
      }
    }

    return $facet_options;
  }

}
