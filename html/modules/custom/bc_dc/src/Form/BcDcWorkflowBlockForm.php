<?php

namespace Drupal\bc_dc\Form;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the form used by bc_dc_workflow_block.
 */
class BcDcWorkflowBlockForm extends FormBase {

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity_type.manager service.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('entity_type.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'bc_dc_workflow_block_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $args = []): array {
    if (empty($args['node'])) {
      return $form;
    }

    // Message if revision is published.
    if ($args['node']->isPublished()) {
      $form['note'] = [
        '#markup' => $this->t('Latest revision is published.'),
      ];
      return $form;
    }

    // Prevent publishing when required fields are empty.
    $empty_required = [];
    foreach ($args['node']->getFields() as $field) {
      $fieldDefinition = $field->getFieldDefinition();
      if ($field->isEmpty() && $fieldDefinition->isRequired()) {
        $empty_required[] = $fieldDefinition->getLabel();
      }
    }
    if ($empty_required) {
      $form['empty_required'] = [
        '#theme' => 'item_list',
        '#items' => $empty_required,
        '#prefix' => '<p>' . $this->t('The following fields must be completed before publishing:') . '</p>',
      ];
      return $form;
    }

    // Publishing block.
    $form['revision_log_message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Revision note'),
    ];

    $form['major_edit'] = [
      '#type' => 'radios',
      '#required' => TRUE,
      '#title' => $this->t('Edit type'),
      '#options' => [
        $this->t('Minor edit'),
        $this->t('Major edit (notify subscribers)'),
      ],
    ];

    $form['full_review'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('This is a full review'),
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Publish'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $revision_log_message = $form_state->getValue('revision_log_message');
    $revision_log_message = ($revision_log_message === '') ? NULL : $revision_log_message;

    $full_review = (bool) $form_state->getValue('full_review');

    // Get the node.
    $buildInfo = $form_state->getBuildInfo();
    $node = $buildInfo['args'][0]['node'];

    // Set redirect to view page for node.
    $url = Url::fromRoute('entity.node.canonical', ['node' => $node->id()]);
    $form_state->setRedirectUrl($url);

    // Update the modified date when this is a full review or it is empty.
    if ($form_state->getValue('major_edit') || !$node->field_modified_date->value) {
      $node->field_modified_date->value = (new DrupalDateTime('now', 'UTC'))->format('Y-m-d\TH:i:s');
    }

    // Set field_is_complete_review when needed.
    $node->field_is_complete_review->value = $full_review;
    // Set revision log message if one was provided.
    if (is_string($revision_log_message)) {
      $node->setRevisionLogMessage($revision_log_message);
    }
    // Set revision author to current.
    $node->setRevisionUserId($this->currentUser()->id());
    // Set published.
    $node->set('moderation_state', 'published');
    $node->save();

    $this->messenger()->addMessage($this->t('Metadata record published.'));
  }

}
