<?php

namespace Drupal\bc_dc\Service;

use Drupal\bc_dc\Traits\ReviewReminderTrait;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Url;
use Drupal\message_gcnotify\Service\GcNotifyApiService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Service for sending reminders to review out-of-date data_set nodes.
 */
class ReviewReminder implements ContainerInjectionInterface {

  use LoggerChannelTrait;
  use ReviewReminderTrait;
  use StringTranslationTrait;

  /**
   * Constructs a new ReviewReminder object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config.factory service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity_type.manager service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   *   The logger.factory service.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $stringTranslation
   *   The string_translation service.
   */
  public function __construct(
    protected ConfigFactoryInterface $configFactory,
    protected EntityTypeManagerInterface $entityTypeManager,
    LoggerChannelFactoryInterface $loggerFactory,
    TranslationInterface $stringTranslation,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('logger.factory'),
      $container->get('string_translation'),
    );
  }

  /**
   * Get and send review reminders to all data_set authors.
   */
  public function sendRemindersToAllUsers(): void {
    $reminders = $this->getReminders();
    foreach ($reminders as $uid => $notifications) {
      $this->sendRemindersToOneUser($uid, $notifications);
    }
  }

  /**
   * Send an review reminder to a data_set author.
   *
   * @param int $uid
   *   The UID of the user to send the message to.
   * @param array $notifications
   *   An array of notifications as generated by ::getReminders().
   *
   * @return bool|null
   *   TRUE if sending succeed, FALSE if it failed, NULL if the user has no
   *   email address or if there are no updates to send to this user.
   */
  public function sendRemindersToOneUser(int $uid, array $notifications): ?bool {
    $account = $this->entityTypeManager->getStorage('user')->load($uid);
    $email = $account?->getEmail();
    $logger = $this->getLogger('bc_dc');

    if (!$email) {
      $logger->error('ReviewReminder: User @uid has no email address.', ['@uid' => $uid]);
      return NULL;
    }

    $body = $this->generateBody($notifications);
    if (!$body) {
      $logger->error('ReviewReminder: Empty message for user @uid.', ['@uid' => $uid]);
      return NULL;
    }

    $subject = $this->t('Metadata records you maintain need updates', [], ['langcode' => $account->getPreferredLangcode()]);

    $success = GcNotifyApiService::sendMessage([$email], $subject, $body);
    if ($success) {
      $logger->notice('Sent ReviewReminder message to user @uid.', ['@uid' => $uid]);
    }
    else {
      $logger->error('Failed to send ReviewReminder message to user @uid.', ['@uid' => $uid]);
    }

    return $success;
  }

  /**
   * Generate the body of a review reminder message to a data_set author.
   *
   * @param array $notifications
   *   An array of notifications for one user, one user's worth of what is
   *   generated by ::getReminders().
   *
   * @return string|null
   *   The body of the message or NULL if there is no message to send.
   */
  public function generateBody(array $notifications): ?string {
    $bc_dc_settings = $this->configFactory->get('bc_dc.settings');

    $body = [];
    $update_types = [
      static::REVIEW_OVERDUE => trim($bc_dc_settings->get('review_overdue_message'), '.'),
      static::REVIEW_NEEDED => trim($bc_dc_settings->get('review_needed_message'), '.'),
    ];
    foreach ($update_types as $update_type => $update_message) {
      if (!empty($notifications[$update_type])) {
        $body[] = $update_message . ':';
        foreach ($notifications[$update_type] as $update) {
          // If a user clicks one of these links to one of
          // their Metadata Recordsin the email they receive,
          // they will confusingly get a "Not Found" error if they are not logged in.
          // So we make the link to instead to the login page, with a REDIRECT
          // to the real URL we want to take them to.
          $url_via_login = Url::fromRoute('user.login', [], [
            'query' => ['destination' => $update['dataset_url']],
            'absolute' => TRUE,
          ]);
          // GC Notify requires Markdown formatting, not HTML.
          $body[] = sprintf('* [%s](%s)', $update['title'], $url_via_login->toString());
        }
      }
    }
    if ($body) {
      return implode("\n\n", $body);
    }
    return NULL;
  }

  /**
   * Generate an array of data_set node update notification data.
   *
   * The array includes the title and URL and is grouped by author and review
   * status (needed or overdue):
   * @code
   * return [
   *   UID => [
   *     static::REVIEW_OVERDUE => [
   *       ['title' => STRING, 'url' => STRING],
   *       ...
   *     ],
   *     static::REVIEW_NEEDED => [...],
   *   ],
   *   ...
   * ];
   * @endcode
   *
   * @return array
   *   The array of data_set node update notification data.
   */
  public function getReminders(): array {
    $node_storage = $this->entityTypeManager->getStorage('node');

    $query = $node_storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('type', 'data_set')
      ->exists('field_review_interval')
      ->exists('field_last_review_date');
    $data_set_nids = $query->execute();

    $reminders = [];

    foreach ($data_set_nids as $data_set_nid) {
      $data_set = $node_storage->load($data_set_nid);
      $review_status = static::dataSetReviewNeeded($data_set);
      if ($review_status) {
        $reminders[$data_set->getOwnerId()][$review_status][] = [
          'title' => $data_set->getTitle(),
          'dataset_url' => $data_set->toUrl('canonical', ['absolute' => FALSE])->toString(),
        ];
      }
    }

    return $reminders;
  }

}
