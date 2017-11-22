<?php

/**
 * @file
 * Contains custom Brightcove drush commands.
 */

use Drupal\brightcove\BrightcoveUtil;
use Drupal\Core\Database\Database;

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
      'bcsa',
    ],
  ];

  return $items;
}

/**
 * Implements drush_hook_COMMAND().
 */
function drush_brightcove_sync_all() {
  drush_print('Initiating Brightcove-to-Drupal sync...');
  BrightcoveUtil::runStatusQueues('sync', Database::getConnection(), \Drupal::time());
  drush_backend_batch_process();
  drush_print('Sync complete.');
}
