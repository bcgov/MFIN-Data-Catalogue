<?php

/**
 * @file
 * Code related to Views.
 */

/**
 * Implements hook_views_data_alter().
 */
function bc_dc_views_data_alter(array &$data) {
  $data['node']['bc_dc_build'] = [
    'title' => t('Edit link'),
    'field' => [
      'title' => t('Edit'),
      'help' => t('Links to Edit page (previously known as "Build page").'),
      'id' => 'bc_dc_build',
    ],
  ];

  $data['node']['bc_dc_delete'] = [
    'title' => t('Delete link'),
    'field' => [
      'title' => t('Delete'),
      'help' => t('Deletes content.'),
      'id' => 'bc_dc_delete',
    ],
  ];

  $data['node']['bc_dc_unpublish'] = [
    'title' => t('Unpublish link'),
    'field' => [
      'title' => t('Unpublish'),
      'help' => t('Unpublishes content.'),
      'id' => 'bc_dc_unpublish',
    ],
  ];
}
