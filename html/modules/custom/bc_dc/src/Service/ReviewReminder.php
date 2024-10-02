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
    $assets_needing_review = $this->getAssetsNeedingReview();
    foreach ($assets_needing_review as $uid => $assets_needing_review_for_user) {
      $this->sendRemindersToOneUser($uid, $assets_needing_review_for_user);
    }
  }

  /**
   * Send an review reminder to a data_set author.
   *
   * @param int $uid
   *   The UID of the user to send the message to.
   * @param array $assets_needing_review_for_user
   *   An array of this user's assets that need review, as generated by ::getAssetsNeedingReview().
   *
   * @return bool|null
   *   TRUE if sending succeed, FALSE if it failed, NULL if the user has no
   *   email address or if there are no updates to send to this user.
   */
  public function sendRemindersToOneUser(int $uid, array $assets_needing_review_for_user): ?bool {
    $account = $this->entityTypeManager->getStorage('user')->load($uid);
    $email = $account?->getEmail();
    $logger = $this->getLogger('bc_dc');

    if (!$email) {
      $logger->error('ReviewReminder: User @uid has no email address.', ['@uid' => $uid]);
      return NULL;
    }

    $body = $this->generateBody($assets_needing_review_for_user, $uid);
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
   * @param array $assets_needing_review_for_user
   *   An array of assets that need review, one user's worth of what is
   *   generated by ::getAssetsNeedingReview().
   * @param int $uid
   *   User ID of user that these assets belong to.
   *
   * @return string|null
   *   The body of the message or NULL if there is no message to send.
   */
  public function generateBody(array $assets_needing_review_for_user, $uid): ?string {

    $user = $this->entityTypeManager->getStorage('user')->load($uid);

    $update_types = [
      static::REVIEW_OVERDUE,
      static::REVIEW_NEEDED,
    ];
    $body_records_to_review = '';
    $count_records_to_review = 0;

    // First, loop through the records needing review, and
    // render the text for each one of them, to go in the email.
    foreach ($update_types as $update_type) {
      if (!empty($assets_needing_review_for_user[$update_type])) {
        foreach ($assets_needing_review_for_user[$update_type] as $asset_needing_review) {
          ++$count_records_to_review;

          // If a user clicks one of these links to one of
          // their Metadata Records in the email they receive,
          // they will confusingly get a "Not Found" error
          // if they are not logged in.
          // So we make the link to instead to the login page, with a REDIRECT
          // to the real URL we want to take them to.
          $url_via_login = Url::fromRoute('user.login', [], [
            'query' => ['destination' => '/node/' . $asset_needing_review->id()],
            'absolute' => TRUE,
          ]);
          $prev_review_date = strtotime($asset_needing_review->field_last_review_date->value);
          $review_freq_months = $asset_needing_review->field_review_interval->value;

          // Calculate $next_review_date.
          [$y, $m, $d] = explode(' ', date('Y m d', $prev_review_date));
          $m += $review_freq_months;
          $next_review_date = (new \DateTime())->setDate($y, $m, $d)->format('U');

          $date_formatter = \Drupal::service('date.formatter');

          // GC Notify requires Markdown formatting, not HTML.
          $body_records_to_review .= t(<<<END_RECORD_TO_REVIEW
[@asset_title](@url_via_login) @review_is_overdue
* Last Reviewed: @prev_review_date
* Review Frequency: @review_freq
* Next Review Due: @next_review_date (@next_review_ago_or_fromnow)


END_RECORD_TO_REVIEW,
            [
              '@asset_title'       => $asset_needing_review->getTitle(),
              '@url_via_login'     => $url_via_login->toString(),
              '@review_is_overdue' => $update_type == static::REVIEW_OVERDUE
                ? '(review of this record is **overdue**)'
                : '',
              '@prev_review_date' => date('F j, Y', $prev_review_date),
              '@review_freq'      => $review_freq_months == 12
                ? 'Annually'
                : $this->formatPlural($review_freq_months, 'Every month', 'Every @count months'),
              '@next_review_ago_or_fromnow' => time() > $next_review_date
                ? $date_formatter->formatTimeDiffSince($next_review_date) . ' overdue'
                : $date_formatter->formatTimeDiffUntil($next_review_date) . ' from now',
              '@next_review_date' => date('F j, Y', $next_review_date),
            ]
          );
        }
      }
    }

    // Now add on the header and footer.
    $body = t(<<<END_BODY
Dear @first_name,

To uphold the trust of our users in the Data Catalogue, it is important that the records are reviewed periodically. Your attention to this ensures the accuracy and reliability of the data we maintain.

Please review the following @count_records:


@records_to_review

___

@email_footer

END_BODY,
      [
        '@first_name' => $user->field_first_name->value,
        '@count_records' => $this->formatPlural($count_records_to_review, 'record', '@count records'),
        '@records_to_review' => $body_records_to_review,
        '@email_footer' => _bc_dc_get_email_footer(),
      ]
    );

    return $body;
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
  public function getAssetsNeedingReview(): array {
    $node_storage = $this->entityTypeManager->getStorage('node');

    $query = $node_storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('type', 'data_set')
      ->exists('field_review_interval')
      ->exists('field_last_review_date');
    $data_set_nids = $query->execute();

    $assets_needing_review = [];

    foreach ($data_set_nids as $data_set_nid) {
      $data_set = $node_storage->load($data_set_nid);
      $review_status = static::dataSetReviewNeeded($data_set);
      if ($review_status) {
        $assets_needing_review[$data_set->getOwnerId()][$review_status][] = $data_set;
      }
    }

    return $assets_needing_review;
  }

}
