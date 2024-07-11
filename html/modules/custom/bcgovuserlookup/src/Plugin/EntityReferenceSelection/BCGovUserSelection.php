<?php

namespace Drupal\bcgovuserlookup\Plugin\EntityReferenceSelection;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection;

/**
 * Entity Reference Selection for User Ref field using OpenIDConnect.
 *
 * Look up Users by their NAME loaded from Open ID Connect module
 * from their IDIR. Otherwise, the default is to look up
 * by their 'username', which in our case is a *hash*
 * representing their IDIR.
 *
 * We set this up by default on the 'Author' (uid) field on all Node
 * edit forms. If you set up your own User Entity Ref field, then you
 * can use the 'BC Gov User Lookup' Reference Method, when editing the
 * setting for your field.
 * e.g. under Structure... Content Types... YOUR_TYPE... YOUR_FIELD... Edit.
 *
 * @EntityReferenceSelection(
 *   id = "bcgovuserselector",
 *   label = @Translation("BC Gov User Lookup"),
 *   group = "bcgovuserselector",
 * )
 */
class BCGovUserSelection extends DefaultSelection {

  /**
   * {@inheritdoc}
   */
  public function getReferenceableEntities($match = NULL, $match_operator = 'CONTAINS', $limit = 0) {
    $target_type = $this->getConfiguration()['target_type'];

    $database = \Drupal::database();

    // First, set up a query to search the 'value' field in the users_data
    // table, of the openid_connect data, and get the uid of any matching users.
    //
    // Note that it is of type 'longblob', which means that case-insensitive
    // searches won't work -- this is what the CONVERT is for.
    $query = $database
      ->select('users_data', 'ud')
      ->fields('ud', ['uid'])
      ->condition('module', 'openid_connect')
      ->condition('name', 'oidc_name')
      ->where("CONVERT(ud.value USING latin1) LIKE LOWER(:match)", [':match' => "%$match%"]);

    // Now, for the users that don't have an entry from openid_connect,
    // search in their drupal username, getting the uid of any matching users.
    $query_2 = $database
      ->select('users_field_data', 'u');
    $query_2
      ->addJoin('LEFT OUTER', 'users_data', 'ud',
                '[u].[uid] = [ud].[uid] AND [ud].[module] = :modulename',
                [':modulename' => 'openid_connect']);
    $query_2
      ->isNull('ud.module');

    $query_2
      ->fields('u', ['uid'])
      ->where('u.name LIKE :match', [':match' => "%$match%"]);

    // Merge these two queries into one.
    $query->union($query_2);

    if ($limit > 0) {
      $query->range(0, $limit);
    }

    $result = $query->execute();

    $uid_list = [];
    foreach ($result as $record) {
      $uid_list[] = $record->uid;
    }
    $result = $uid_list;

    if (empty($result)) {
      return [];
    }

    $options = [];
    $entities = $this->entityTypeManager->getStorage($target_type)->loadMultiple($result);
    foreach ($entities as $entity_id => $entity) {
      $bundle = $entity->bundle();
      $options[$bundle][$entity_id] = Html::escape($this->entityRepository->getTranslationFromContext($entity)->label() ?? '');
    }

    // Sort them alphabetically, ignoring case.
    natcasesort($options['user']);

    return $options;
  }

}
