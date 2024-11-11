<?php

/**
 * @file
 * Post update hooks for the ecms_authentication module.
 */

declare(strict_types=1);

/**
 * Block user one for all sites.
 */
function ecms_authentication_post_update_block_user_one(&$sandbox): void {
  $userStorage = \Drupal::entityTypeManager()
    ->getStorage('user');

  /** @var \Drupal\user\UserInterface $user */
  $user = $userStorage->load(1);

  if ($user) {
    // Block user/1 for all sites.
    $user->block();
    $user->save();
  }
}
