<?php

namespace Drupal\bcbb_search\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\search_api\ParseMode\ParseModePluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Builds the search form for the search block.
 *
 * This is used by BcbbSearchBlock::build().
 */
class BcbbSearchApiForm extends FormBase {

  /**
   * Constructs a BcbbSearchApiForm object.
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
    return 'bcbb_search_api_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, array $config = NULL): array {

    $form['search_url'] = [
      '#type' => 'hidden',
      '#default_value' => $config['search']['search_url'],
    ];

    $form['search_keyword'] = [
      '#type' => 'textfield',
      '#title' => !empty($config['search']['search_label']) ? $config['search']['search_label'] : $this->t('Search'),
      '#maxlength' => 255,
      '#placeholder' => !empty($config['search']['search_placeholder']) ? $config['search']['search_placeholder'] : '',
    ];

    if (!empty($config['search']['show_advanced_link']) && $config['search']['search_url']) {
      $url = Url::fromUserInput($config['search']['search_url']);
      $form['search_keyword']['#description'] = Link::fromTextAndUrl($this->t('Advanced search'), $url);
    }

    if (!empty($config['search']['label_sr_only'])) {
      $form['search_keyword']['#attributes']['aria-label'] = !empty($config['search']['search_label']) ? $config['search']['search_label'] : $this->t('Search terms');
      $form['search_keyword']['#title_display'] = 'hidden';
    }

    if (isset($config['search']['search_input_size'])) {
      $form['search_keyword']['#size'] = $config['search']['search_input_size'] ? $config['search']['search_input_size_value'] : NULL;
    }
    // Add a submit button that handles the submission of the form.
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Search'),
      '#attributes' => [
        'class' => ['bcbb-search-submit-icon'],
      ],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $query = NULL;

    // Get search terms.
    $formKeyword = $form_state->getValue('search_keyword');

    if (!empty($formKeyword)) {
      $query = ['search_api_fulltext' => $formKeyword];
    }

    $formAction = $form_state->getValue('search_url');

    // Build search URL.
    $url = Url::fromUserInput($formAction, ['query' => $query]);
    $form_state->setRedirectUrl($url);
  }

}
