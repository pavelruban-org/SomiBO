<?php

/**
 * @file
 * CTools content type for In region, list external links, poll block
 */

$plugin = array(
  'title' => t('Somi Time Of Arrival'),
  'description' => t('Provides SOMI Time of Personal Arrival'),
  'content_types' => 'somi_time_of_arrival',
  'single' => TRUE,
  'render callback' => 'somi_time_of_arrival_content_type_render',
  'category' => t('Somi'),
);

/**
 * @return mixed
 */
function somi_time_of_arrival_content_type_form() {
  $form = [
    '#method' => 'GET',
    'date' => [
      '#type' => 'date_popup',
      '#id' => 'date-filter',
      '#date_format' => 'Y-m-d',
      '#date_year_range' => '-5:+5',
      '#default_value' => !empty($_GET['date']) ? $_GET['date']['date'] : date('Y-m-d'),
    ],
    'sort' => [
      '#title' => 'Сортировака',
      '#type' => 'radios',
      '#single' => TRUE,
      '#options' => [
        t('приход'), t('уход'), t('в офисе')
      ],
      '#default_value' => !empty($_GET['sort']) ? $_GET['sort'] : 0,
    ],
    'groupby' => [
      '#title' => 'Группировка',
      '#description' => 'По департаменту',
      '#single' => TRUE,
      '#type' => 'radios',
      '#options' => ['отсутствует', 'департамен', 'нода'],
      '#default_value' => !empty($_GET['groupby']) ? $_GET['groupby'] : 0,
    ],
    'sort_order' => [
      '#title' => 'Порядок',
      '#type' => 'select',
      '#options' => ['DESC', 'ASC'],
      '#default_value' => !empty($_GET['sort_order']) ? $_GET['sort_order'] : 0,
    ],
    'submit' => [
      '#type' => 'submit',
      '#value' => t('ok'),
    ]
  ];

  return $form;
}

/**
 * Output function for the content type.
 */
function somi_time_of_arrival_content_type_render($subtype, $conf, $panel_args, $context) {
  $block = new stdClass();
  $block->content = '';

  if (!empty($_GET['date']['date'])) {
    $title = "Время прихода сотрудников I20 на " . date('d.m.y', strtotime($_GET['date']['date']));
  }
  else {
    $title = "Время прихода сотрудников I20 на " . date('d.m.y', time()) . ", текущее время " . date('H:i:s', time());
  }

  drupal_add_css(SOMI_MODULE_PATH . '/theme/css/availability.css');

  $departaments_links = somi_get_departaments_filter('admin/somi/time-of-arrival');

  $form = drupal_get_form('somi_time_of_arrival_content_type_form');

  $block->content = theme('somi_board', array(
    'title' => $title,
    'class' => 'availability time-arrival',
    'data' => somi_get_time_of_arrival_data(somi_get_departament_tid_from_panel_args($panel_args)),
    'departament_links' => $departaments_links,
    'form' => $form
  ));
  return $block;
}