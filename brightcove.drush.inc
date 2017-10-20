<?php

use Drupal\brightcove\BrightcoveUtil;
use Drupal\Core\Database\Database;

/**
 * @file Contains custom Brightcove drush commands
 */

/**
 * Implements hook_drush_command().
 */
function brightcove_drush_command() {
  $items = [];

  $items['brightcove-sync-all'] = [
    'description' => 'Initiates a Brightcove-to-Drupal sync by adding API clients to the queue.',
    'drupal dependencies' => ['brightcove'],
    'callback' => 'drush_brightcove_sync_all',
    'aliases' => [
      'brightcove:sync-all',
      'bcsa'
    ],
  ];

  return $items;
}

/**
 * Brightcove sync all callback function.
 * drush_[MODULE_NAME]_[COMMAND_NAME]().
 */
function drush_brightcove_sync_all() {
  drush_print('Initiating Brightcove-to-Drupal sync...');
  _drush_brightcove_sync_all_batch_operations();
  drush_print('Sync complete.');
}

function _drush_brightcove_sync_all_batch_operations() {
  $batch_operations = [];

  $util_class = BrightcoveUtil::class;

  $batch_operations[] = ['_brightcove_initiate_sync', []];
  $batch_operations[] = [[$util_class, 'runQueue'], ['brightcove_client_queue_worker']];
  $batch_operations[] = [[$util_class, 'runQueue'], ['brightcove_player_queue_worker']];
  $batch_operations[] = [[$util_class, 'runQueue'], ['brightcove_player_delete_queue_worker']];
  $batch_operations[] = [[$util_class, 'runQueue'], ['brightcove_custom_field_queue_worker']];
  $batch_operations[] = [[$util_class, 'runQueue'], ['brightcove_custom_field_delete_queue_worker']];
  $batch_operations[] = [[$util_class, 'runQueue'], ['brightcove_video_page_queue_worker']];
  $batch_operations[] = [[$util_class, 'runQueue'], ['brightcove_video_queue_worker']];
  $batch_operations[] = [[$util_class, 'runQueue'], ['brightcove_text_track_queue_worker']];
  $batch_operations[] = [[$util_class, 'runQueue'], ['brightcove_text_track_delete_queue_worker']];
  $batch_operations[] = [[$util_class, 'runQueue'], ['brightcove_playlist_page_queue_worker']];
  $batch_operations[] = [[$util_class, 'runQueue'], ['brightcove_playlist_queue_worker']];
  $batch_operations[] = [[$util_class, 'runQueue'], ['brightcove_video_delete_queue_worker']];
  $batch_operations[] = [[$util_class, 'runQueue'], ['brightcove_playlist_delete_queue_worker']];
  $batch_operations[] = [[$util_class, 'runQueue'], ['brightcove_subscriptions_queue_worker']];
  $batch_operations[] = [[$util_class, 'runQueue'], ['brightcove_subscription_queue_worker']];
  $batch_operations[] = [[$util_class, 'runQueue'], ['brightcove_subscription_delete_queue_worker']];

  if ($batch_operations) {
    // Reset expired items in the default queue implementation table. If
    // that's not used, this will simply be a no-op.
    // @see system_cron()
    Database::getConnection()->update('queue')
      ->fields([
        'expire' => 0,
      ])
      ->condition('expire', 0, '<>')
      ->condition('expire', REQUEST_TIME, '<')
      ->condition('name', 'brightcove_%', 'LIKE')
      ->execute();

    batch_set([
      'operations' => $batch_operations,
    ]);

    drush_backend_batch_process();
  }
}
