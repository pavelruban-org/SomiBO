<?php

/**
 * @file
 * Provides theme preprocess logic.
 */

/**
 * Theme preprocess.
 */
function somi_preprocess_somi_board(&$vars) {
  drupal_add_css(SOMI_MODULE_PATH . '/theme/css/board.css');
  $vars['row_limit'] = somi_is_mobile_agent() ? 4 : 8;
}