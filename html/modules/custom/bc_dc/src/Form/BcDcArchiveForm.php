<?php

namespace Drupal\bc_dc\Form;

use Drupal\bc_dc\Access\BuildEditAccess;
use Drupal\content_moderation\ModerationInformation;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatch;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Implements a form to archive a node.
 */
class BcDcArchiveForm extends ConfirmFormBase implements AccessInterface {

  /**
   * ID of the node to archive.
   *
   * @var int
   */
  protected int $id;

  /**
   * Constructor.
   *
   * @param \Drupal\content_moderation\ModerationInformation $contentModerationModerationInformation
   *   The content_moderation.moderation_information service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity_type.manager service.
   */
  public function __construct(
    protected ModerationInformation $contentModerationModerationInformation,
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('content_moderation.moderation_information'),
      $container->get('entity_type.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'node_archive_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, NodeInterface $node = NULL): array {
    // Store the form ID for later use.
    $this->id = (int) $node->id();

    // Get the default form.
    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl(): Url {
    return Url::fromRoute('bc_dc.data_set_build_page_tab', ['node' => $this->id]);
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion(): TranslatableMarkup {
    $node = $this->entityTypeManager->getStorage('node')->load($this->id);

    return $this->t('Do you want to unpublish %title?', ['%title' => $node->getTitle()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): TranslatableMarkup {
    return $this->t('You may re-publish it later.');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $node = $this->entityTypeManager->getStorage('node')->load($this->id);

    // Archive the node.
    $node->set('moderation_state', 'archived')->save();

    // Set status message.
    $message = $this->t('Unpublished %title.', ['%title' => $node->getTitle()]);
    $this->messenger()->addStatus($message);

    // If no destination param is provided, go to "Build" page.
    if (!$this->getRequest()->get('destination')) {
      $form_state->setRedirectUrl($this->getCancelUrl());
    }
  }

  /**
   * Custom access check for this form.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   * @param \Drupal\Core\Routing\RouteMatch $route_match
   *   The RouteMatch object.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(AccountInterface $account, RouteMatch $route_match): AccessResultInterface {
    $entity = $route_match->getParameter('node');
    $entity_owner = $entity->getOwnerId();

    // No access if the node is not published or if this is not a node that can
    // be moderated.
    if (!$entity->isPublished() || !$this->contentModerationModerationInformation->shouldModerateEntitiesOfBundle($this->entityTypeManager->getDefinition('node'), $entity->bundle())) {
      return AccessResult::forbidden();
    }

    // The user has access if they are able to edit the entity and either has
    // the permission or are the owner of the entity.
    if ($account->hasPermission('archive data_set nodes') || ($entity_owner && $entity_owner === $account->id())) {
      return BuildEditAccess::access($account, $route_match);
    }

    return AccessResult::neutral();
  }

}
