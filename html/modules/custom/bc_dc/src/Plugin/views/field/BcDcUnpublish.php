<?php

namespace Drupal\bc_dc\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Field handler to unpublish button .
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("bc_dc_unpublish")
 */
class BcDcUnpublish extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Leave empty to avoid a query on this field.
  }

  /**
   * Define the available options.
   *
   * @return array
   *   The options.
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    return $options;
  }

  /**
   * Provide the options form.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * Helper function.
   */
  public function array_in_array($arr_needle, $arr_from) {
    $inArray = FALSE;
    foreach ($arr_needle as $value) {
      if (in_array($value, $arr_from)) {
        $inArray = TRUE;
      }
    }
    return $inArray;
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $node = $values->_entity;
    $user = \Drupal::currentUser();
    /*
    $current_lang = \Drupal::languageManager()->getCurrentLanguage()->getId();
    $current_version_state = $this->getModerationState($node, $current_lang);

    if ($current_version_state == 'archived') {
    return '';
    }
     */

    $options = [
      'attributes' => [
        'class' => ['btn', 'btn-secondary'],
        'title' => $this->t('Unpublish @title', ['@title' => $node->getTitle()]),
      ],
      'query' => [
        'destination' => '/user/' . $user->id() . '/manage',
      ],
    ];

    $url = Url::fromRoute('bc_dc.data_set_archive_page', ['node' => $node->id()], $options);
    $text = $this->t('Unpublish');
    $link = Link::fromTextAndUrl($text, $url);
    $link_string = $link->toString();

    return $link_string;
  }

}
