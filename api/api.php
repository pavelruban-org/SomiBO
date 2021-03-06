<?php

/**
 * @file
 *
 * Description for all API callbacks.
 */

define('API_DEFAULT_USER_LANGUAGE', 'en');
define('API_DEFAULT_VERSION', '1.0');

require_once API_ROOT . '/includes/api.auth.inc';
require_once API_ROOT . '/includes/api.social-auth.inc';

function api_methods() {
  $base = array(
    'bootstrap' => DRUPAL_BOOTSTRAP_SESSION,
    'skip_hook_init' => TRUE,
    'init callback' => 'api_project_init',
    'session handler' => 'api/includes/api.session.inc',
    'dependencies' => [],
  );

  $callbacks = array(
    'auth/post' => array(
      'callback' => 'api_auth_post',
      'access callback' => 'api_user_is_anonymous',
    ) + $base,
    'node/access/get' => array(
      'callback' => 'api_node_access_get',
      'bootstrap' => DRUPAL_BOOTSTRAP_FULL,
    ) + $base,
    'node/time-sync/post' => array(
      'callback' => 'api_node_time_sync_post',
      'bootstrap' => DRUPAL_BOOTSTRAP_FULL,
    ) + $base,
    'node/event/post' => array(
      'callback' => 'api_node_event_post',
      'bootstrap' => DRUPAL_BOOTSTRAP_FULL,
    ) + $base,
    'node/call/post' => array(
      'callback' => 'api_node_call_post',
      'bootstrap' => DRUPAL_BOOTSTRAP_FULL,
    ) + $base,
    'auth/put' => array(
      'callback' => 'api_auth_put',
      'access callback' => 'api_user_is_anonymous',
    ) + $base,
    'device/post' => array(
      'callback' => 'api_device_post',
      'dependencies' => array('push_me'),
    ) + $base,
    'project/info/get' => array(
      'callback' => 'api_project_info_get',
      'etag_enabled' => TRUE,
    ) + $base,
    'project/info/%content/get' => array(
      'callback' => 'api_project_data_get',
      'etag_enabled' => TRUE,
    ) + $base,
    'content/%type/%id/comments/get' => array(
      'callback' => 'api_content_comments_get',
      'page arguments' => array(1, 2),
      'dependencies' => array('entity_comments'),
      'etag_enabled' => TRUE,
    ) + $base,
    'content/%type/%id/comments/post' => array(
      'callback' => 'api_content_comments_post',
      'page arguments' => array(1, 2),
      'dependencies' => array('entity_comments'),
    ) + $base,
    'discussion/post' => array(
      'callback' => 'api_discussion_post',
      'dependencies' => array(
        'campuz_export',
        'privatemsg',
        'campuz_privatemsg',
        'filter',
      ),
    ) + $base,
    'discussion/%id/message/post' => array(
      'callback' => 'api_message_post',
      'page arguments' => array(1),
      'dependencies' => array(
        'campuz_export',
        'privatemsg',
        'campuz_privatemsg',
        'filter',
      ),
    ) + $base,
    'users/get' => array(
      'callback' => 'api_users_get',
      'dependencies' => array(),
    ) + $base,
    'user/account/balance/post' => array(
      'callback' => 'api_user_account_balance_post',
      'dependencies' => array(),
    ) + $base,
    'account/op/validate/post' => array(
      'bootstrap' => DRUPAL_BOOTSTRAP_FULL,
      'callback' => 'api_account_op_validate_post',
    ) + $base,
    'somi/config/get' => array(
      'bootstrap' => DRUPAL_BOOTSTRAP_FULL,
      'callback' => 'api_somi_config_get',
    ) + $base,
    'user/account/balance/add/post' => array(
        'bootstrap' => DRUPAL_BOOTSTRAP_FULL,
        'callback' => 'api_user_account_balance_add_post',
    ) + $base,
    'discussion/%id/messages/get' => array(
      'callback' => 'api_discussion_messages_get',
      'page arguments' => array(1),
      'dependencies' => array(
        'campuz_export',
        'privatemsg',
        'campuz_privatemsg',
        'field',
      ),
    ) + $base,
    'create/history/post' => array(
        'callback' => 'api_create_history',
        'bootstrap' => DRUPAL_BOOTSTRAP_FULL,
      ) + $base,
    'users/info/get' => array(
        'callback' => 'api_users_info_get',
        'bootstrap' => DRUPAL_BOOTSTRAP_FULL,
      ) + $base,
    'account/%uid/%aid/get' => array(
        'callback' => 'api_user_account_get',
        'bootstrap' => DRUPAL_BOOTSTRAP_FULL,
    ) + $base,
    'accounts/%uid/get' => array(
        'callback' => 'api_user_accounts_get',
        'bootstrap' => DRUPAL_BOOTSTRAP_FULL,
    ) + $base,
    'accounts/get' => array(
        'callback' => 'api_accounts_get',
        'bootstrap' => DRUPAL_BOOTSTRAP_FULL,
    ) + $base,
    'device/info/get' => array(
        'callback' => 'api_device_info_get',
        'bootstrap' => DRUPAL_BOOTSTRAP_FULL,
    ) + $base,
    'users/time/get' => array(
        'callback' => 'api_users_time_get',
        'bootstrap' => DRUPAL_BOOTSTRAP_FULL,
    ) + $base,
    'users/activity/%id/get' => array(
        'callback' => 'api_users_activity_by_dep_get',
        'bootstrap' => DRUPAL_BOOTSTRAP_FULL,
    ) + $base,
    'users/activity/get' => array(
        'callback' => 'api_users_activity_get',
        'bootstrap' => DRUPAL_BOOTSTRAP_FULL,
    ) + $base,
  );

  // @TODO: Remove dependency from campuz_export.
  // @TODO: We need to support chain of dependencies and exclude user_device (it's in api_layer).
  $required_modules = [
    'api_layer',
    'field_sql_storage',
    'entity',
    'entityreference',
    'node',
    'file',
    'user',
    'field',
  ];

  foreach ($callbacks as $path => $info) {
    if ($info['bootstrap'] < DRUPAL_BOOTSTRAP_FULL) {
      foreach ($required_modules as $module) {
        if (!in_array($module, $info['dependencies'], TRUE)) {
          $callbacks[$path]['dependencies'][] = $module;
        }
      }
    }
  }

  return $callbacks;
}

/**
 * Return empty result in any case.
 *
 * @return array
 */
function api_empty_response() {
  return array('result' => 'Ok');
}

/**
 * @TODO: Add static storage.
 *
 * @return \stdClass|NULL
 */
function api_get_current_device() {
  return !empty($_SERVER['HTTP_X_API_FINGERPRINT'])
    ? user_device_load_by_fingerprint($_SERVER['HTTP_X_API_FINGERPRINT'])
    : NULL;
}

/**
 * User authorization callback.
 */
function api_auth_post() {
  $data = api_request_data();

  $account = NULL;
  // TODO: Update on iOS implementation.
  $user_language = !empty($data->language) ? $data->language : API_DEFAULT_USER_LANGUAGE;

  // Try to load user by social credentials.
  if (isset($data->user->social) && isset($data->user->social->provider)) {
    $extra_data = isset($data->user->social->extra) ? $data->user->social->extra : new stdClass();
    $extra_data->language = $user_language;
    if (!$account = api_user_load_by_social($data->user->social, $extra_data)) {
      throw new ApiAuthException('Can not login via social credentials', 35862);
    }
  }

  if (!$account) {
    // If no social credential presented email is required field.
    if (empty($data->user->email)
      // Let user be loaded by secret_key
      && !(!empty($data->user->secret_key) && ($account = api_user_load_by_secret_key($data->user->secret_key)))
    ) {
      throw new ApiAuthException('Email is missed for new account', 35863);
    }

    // Declare is registration only flag.
    // In registration mode the email which user specified should not been used.
    $is_registration = empty($_GET['mode']) || $_GET['mode'] != 'login';

    // Try to authorize user by email.
    if ($account) {
      // do nothing.
    }
    elseif ($account = api_user_load_by_email($data->user->email)) {
      if ($is_registration) {
        throw new ApiAuthException('Email is already in use', 35861);
      }
      if (empty($data->user->password)) {
        throw new ApiAuthException('Password is required', 35874);
      }
      require_once DRUPAL_ROOT . '/includes/password.inc';
      if (!user_check_password($data->user->password, $account)) {
        throw new ApiAuthException('Login or password does not match', 35860);
      }
    }
    // Register new user.
    elseif ($is_registration) {
      // Validate user name if it's manually filled.
      if (isset($data->user->name)) {
        require_once DRUPAL_ROOT . '/modules/user/user.module';
        if ($error = user_validate_name($data->user->name)) {
          $validation_errors = array(
            t('You must enter a username.') => 35868,
            t('The username cannot begin with a space.') => 35869,
            t('The username cannot end with a space.') => 35870,
            t('The username cannot contain multiple spaces in a row.') => 35871,
            t('The username contains an illegal character.') => 35872,
            t('The username %name is too long: it must be %max characters or less.', array(
              '%name' => $data->user->name,
              '%max' => USERNAME_MAX_LENGTH
            )) => 35873,
          );
          if (isset($validation_errors[$error])) {
            $error_code = $validation_errors[$error];
          }
          else {
            $error_code = 35867;
          }
          throw new ApiAuthException('Registration failed because of invalid name with message: ' . $error, $error_code);
        }
      }

      $name = api_user_name(
        NULL,
        NULL,
        isset($data->user->name) ? $data->user->name : NULL,
        $data->user->email
      );

      $account = api_user_create(
        $name,
        $data->user->email,
        $data->user->password,
        $user_language
      );
    }
    // If this is not registration and account by mail was not found, we tried to login with non-existed mail.
    else {
      throw new ApiAuthException('Login or password does not match', 35860);
    }
  }
  // A final check.
  if (!$account) {
    throw new ApiAuthException('Failed to load or create an account');
  }

  $response = api_auth_access_token($account);
  return $response;
}

/**
 * Refresh user access token API callback.
 */
function api_auth_put() {
  $data = api_request_data();

  $device = api_get_current_device();
  if (!$device) {
    throw new ApiAuthException('Device could not be found by fingerprint.');
  }

  if (empty($data->user) || empty($data->user->refresh_token)) {
    throw new ApiAuthException('Missed required refresh token data');
  }

  $account = api_user_load_by_refresh_token($data->user->refresh_token);
  if (!$account || $account->status != 1) {
    throw new ApiAuthException('Account could not be found or blocked.');
  }

  api_auth_update_device($device, $account);

  return api_auth_access_token($account, $device);
}

/**
 * @param array $project
 */
function api_set_current_project($project) {
  api_get_current_project($project);
}

/**
 * @param array $project
 *
 * @return array
 */
function api_get_current_project($project = NULL) {
  static $current_project;

  if ($project) {
    $current_project = $project;
  }

  return $current_project;
}

/**
 * Get runtime API version.
 *
 * @return string
 */
function api_get_version() {
  $project = api_get_current_project();
  return !empty($project['api_version'])
    ? $project['api_version']
    : API_DEFAULT_VERSION;
}

/**
 * Get filepath of the API response schema.
 *
 * @return string
 */
function api_get_schema_filepath() {
  $filepath = '';
  $api_version = api_get_version();
  // Replace all invalid file name chars.
  $api_version = preg_replace('/[^a-zA-Z0-9\.]/', '', $api_version);
  $schemas_dir = API_ROOT . '/schemas';
  $target_version_filepath = $schemas_dir . '/schema-' . $api_version . '.json';
  if (file_exists($target_version_filepath)) {
    $filepath = $target_version_filepath;
  }
  else {
    // @TODO: Search for highest prior version if API version has no direct match.
  }

  return $filepath;
}

/**
 * Prepare global state before each API callback execution.
 */
function api_project_init() {
  $authorization = !empty($_SERVER['HTTP_AUTHORIZATION'])
    ? explode(' ', $_SERVER['HTTP_AUTHORIZATION']) : NULL;
  list($token_type, $token_value) = count($authorization) == 2
    ? $authorization
    : array('', '');

  if ($token_type == 'Api-key' && !empty($token_value)) {
    $project = NULL;
    $project = array(
      'nid' => NULL,
      'uri' => '',
      'api_version' => '1.1'
    );

    if ($project) {
      api_set_current_project($project);
    }
    else {
      throw new ApiException('API key is incorrect');
    }
  }
  else {
    throw new ApiException('API key is missed');
  }
}

/**
 * Return all project info data.
 *
 * @return array
 */
function _api_project_info() {
  $project = api_get_current_project();
  $main_content = [];

  if (!empty($project['uri'])) {
    $path = drupal_realpath($project['uri']);
    if (file_exists($path)) {
      $main_content = drupal_json_decode(file_get_contents($path));
    }
  }

  return $main_content;
}

function api_project_info_get() {
  return _api_project_info();
}

/**
 * Serve part of project info.
 *
 * @return array
 */
function api_project_data_get() {
  $args = array_slice(func_get_args(), 2);
  $info = _api_project_info();

  $result = api_project_data_build($info, $args);
  $image_ids = api_project_get_image_ids($result);

  if (!empty($image_ids)) {
    $images = api_project_get_images($image_ids, $info['data']['image']);
    if (!empty($images)) {
      $result['data']['image'] = $images;
    }
  }

  return $result;
}

/**
 * Build data array.
 *
 * @param $info
 * @param $args
 *
 * @return array
 *
 * @throws \ApiException
 */
function api_project_data_build($info, $args) {
  $arg = array_shift($args);
  $response = array();
  $found_flag = FALSE;
  $have_data = FALSE;
  $have_type = $type = FALSE;

  if (!empty($info['type'])) {
    $type = $info['type'];
  }
  if (!empty($info['data'])) {
    $info = $info['data'];
    $have_data = TRUE;
  }

  if (!is_numeric($arg) && !empty($info[$arg])) {
    $found_flag = TRUE;
    if (!empty($args)) {
      $response = array($arg => api_project_data_build($info[$arg], $args));
    }
    else {
      $response = array($arg => $info[$arg]);
    }
  }
  elseif (is_array($info)) {
    if ($have_data && !empty($type)) {
      $have_type = TRUE;
    }
    $bundle = is_numeric($arg) ? 'id' : 'type';
    foreach ($info as $key => $value) {
      if (!empty($value[$bundle]) && $value[$bundle] == $arg) {
        $found_flag = TRUE;
        if (!empty($args)) {
          $response = array(api_project_data_build($info[$key], $args));
        }
        else {
          $response = array($info[$key]);
        }
        break;
      }
    }
  }

  if (!$found_flag) {
    throw new ApiException('Data not found', 404);
  }

  if ($have_data) {
    $response = array('data' => $response);
  }
  if ($have_type) {
    $response['type'] = $type;
  }

  return $response;
}

/**
 * Get images from json.
 *
 * @param $ids
 * @param $images
 *
 * @return array
 */
function api_project_get_images($ids, $images) {
  $result = array();
  foreach ($images as $image) {
    if (in_array($image['id'], $ids)) {
      $result[] = $image;
    }
  }

  return $result;
}

/**
 * Get image ids.
 *
 * @param $info
 *
 * @return array
 */
function api_project_get_image_ids($info) {
  $ids = array();

  if (!empty($info['image']) && is_numeric($info['image'])) {
    $ids[] = $info['image'];
  }
  foreach ($info as $element) {
    if (is_array($element)) {
      $ids = array_merge($ids, api_project_get_image_ids($element));
    }
  }

  return $ids;
}

/**
 * Get all comments for specified entity.
 *
 * @param $content_type
 * @param $id
 *
 * @return array
 *   Collection of comments in items key.
 */
function api_content_comments_get($content_type, $id) {
  $items = array();
  $comments = entity_comments_get($content_type, $id);
  foreach ($comments as $comment) {
    $items[] = array(
      "id" => $comment->cid,
      "authorName" => $comment->name,
      "message" => $comment->message,
      "created" => $comment->created,
    );
  }

  return array('items' => $items);
}

/**
 * Add comment to specified entity and return new comment id.
 *
 * @param $content_type
 * @param $id
 *
 * @return array
 *   Operation status and new comment id.
 *
 * @throws ApiException
 */
function api_content_comments_post($content_type, $id) {
  if (empty($content_type) || empty($id) || !is_numeric($id)) {
    throw new ApiException('Missed required argument');
  }

  $data = api_request_data();
  if (empty($data->comment->message) || empty($data->comment->authorName)) {
    throw new ApiException('Missed required argument');
  }

  $comment = new stdClass();
  $comment->uid = 0;
  $comment->created = isset($data->comment->created) ? $data->comment->created : REQUEST_TIME;
  $comment->entity_type = $content_type;
  $comment->entity_id = $id;
  $comment->name = $data->comment->authorName;
  $comment->message = $data->comment->message;

  $comment = entity_comments_create($comment);

  return array('result' => 'Ok', 'comment' => array('id' => $comment->cid));
}

/**
 * Register device and attach to a current user.
 */
function api_device_post() {
  $data = api_request_data();

  if (empty($data->device)) {
    throw new ApiException('Missed device data');
  }

  $device = $data->device;
  $info = [];

  if (!empty($device->fingerprint)) {
    $info['fingerprint'] = $device->fingerprint;
  }
  if (!empty($device->data->os->name)) {
    $info['os'] = strtolower($device->data->os->name);
  }
  if (!empty($device->data->push_token)) {
    $info['push_token'] = $device->data->push_token;
  }
  if (!empty($device->data->os->version)) {
    $info['client_version'] = $device->data->os->version;
  }
  if (!empty($device->application->version)) {
    $info['app_version'] = intval($device->application->version);
  }
  if (!empty($device->data)) {
    $info['data'] = (array) $device->data;
  }

  push_me_register_device($info);

  return api_empty_response();
}

/**
 * Create new discussion and post a new message there.
 */
function api_discussion_post() {
  $author = api_get_user_or_device();

  $data = api_request_data();

  switch (TRUE) {
    case empty($data->discussion):
      throw new ApiException('Missed discussion data');
    case empty($author->did) && empty($author->uid):
      throw new ApiException('User is not authorized');
    case empty($data->discussion->uid):
      throw new ApiException('Missed discussion uid data');
    case empty($data->discussion->thread):
      throw new ApiException('Missed discussion thread data');
    case empty($data->discussion->companionName):
      throw new ApiException('Missed discussion companionName data');
    case empty($data->discussion->message):
      throw new ApiException('Missed discussion message data');
  }

  // Update anonym device name.
  if (!empty($author->did)) {
    user_device_update(
      campuz_privatemsg_user_device_update_prepare($author),
      array(
        'data' => $author->data + ['name' => $data->discussion->companionName],
      )
    );
  }

  $discussion = $data->discussion;

  if ($companion = user_load($discussion->uid)) {
    require_once DRUPAL_ROOT . '/includes/token.inc';

    $res = privatemsg_new_thread(
      [$companion, $author],
      $discussion->thread,
      $discussion->message,
      array(
        'author' => $author,
        'timestamp' => time(),
      )
    );
  }
  else {
    throw new ApiException(sprintf('User with uid %s could not be found', $discussion->uid));
  }

  if (!empty($res['success'])) {
    $response = array(
      'result' => 'Ok',
      'discussion' => array(
        'id' => $res['message']->thread_id,
        'created' => $res['message']->timestamp,
        'message' => array(
          'id' => $res['message']->mid,
        ),
      ),
    );
  }
  else {
    throw new ApiException('Discussion could not be created');
  }

  return $response;
}

/**
 * Retrieve user or device.
 * @return null|\stdClass
 */
function api_get_user_or_device() {
  global $user;

  if (empty($user->uid)) {
    if ($device = api_get_current_device()) {
      // After discussion with PM was decided always load user object
      // of device that has such reference, even if such device has no session
      // and user is not authorized. Because in case of client problems (session
      // lost problem) all functional of this api would be not available for users.
      // It can have minor drawbacks and in case of stable version of mobile client
      // could be discarded after release.
      if (!empty($device->uid) && ($account = user_load($device->uid))) {
        return $account;
      }
      else {
        campuz_privatemsg_user_device_prepare($device);
        return $device;
      }
    }
  }
  else {
    return $user;
  }

  return NULL;
}

/**
 * Post message to discussion.
 */
function api_message_post($nid) {
  $data = api_request_data();
  $author = api_get_user_or_device();

  $thread = privatemsg_thread_load($nid, $author);

  switch (TRUE) {
    case empty($data->message):
      throw new ApiException('Missed message data');
    case empty($author):
      throw new ApiException('User is not authorized');
    case empty($data->message->text):
      throw new ApiException('Missed message text data');
    case empty($nid):
      throw new ApiException('Missed discussion id GET parameter %discussion/id/message%');
    case empty($thread['thread_id']):
      throw new ApiException(sprintf('Discussion id %s could not be loaded', $nid));
  }

  $message = $data->message;

  require_once DRUPAL_ROOT . '/includes/token.inc';

  $res = privatemsg_new_thread(
    $thread['participants'] + [privatemsg_recipient_key($author) => $author],
    $thread['subject'],
    $message->text,
    array(
      'thread_id' => $thread['thread_id'],
      'author' => $author,
      'timestamp' => time(),
    )
  );

  if (!empty($res['success'])) {
    $response = array(
      'result' => 'Ok',
      'message' => array(
        'id' => $res['message']->mid,
        'created' => $res['message']->timestamp,
      ),
    );
  }
  else {
    throw new ApiException('Message could not be created');
  }

  return $response;
}

/**
 * Get discussions of user or device.
 */
function api_users_get() {
  global $user;

  if (!empty($user->uid)) {
    $response = array('items' => array());

    $query = db_select('users', 'u');
    $query->addField('u', 'uid');
    $query->condition('status', 1);
    $uids = $query->execute()->fetchCol();

    if (($accounts = user_load_multiple($uids))) {
      foreach ($accounts as $account) {
        $roles = $account->roles;

        switch (TRUE) {
          case array_search('top', $roles) !== FALSE:
            $role = 'top';
            break;

          case array_search('core', $roles) !== FALSE:
            $role = 'core';
            break;

          case array_search('active', $roles) !== FALSE:
            $role = 'active';
            break;

          default:
            $role = 'authenticated user';
        }
        
        $response['items'][] = array(
          'uid' => $account->uid,
          'name' => $account->name,
          'email' => $account->mail,
          'role' => $role,
          'slackName' => $account->mail,
        );
      }
    }
  }
  else {
    throw new ApiException("User is not authorized.");
  }

  return $response;
}

/**
 * Get Drupal config.
 */
function api_somi_config_get() {
  $data = api_request_data();

  if (empty($data->config_timestamp)) {
    return somi_api_get_somi_config(TRUE);
  }
  else {
    if ($config = somi_api_get_somi_config(FALSE, $data->config_timestamp)) {
      return $config;
    }
    else {
      return ['status' => 'config is up to date'];
    }
  }
}

/**
 * Get discussions of user or device.
 */
function api_account_op_validate_post() {
  global $user;

  if (!empty($user->uid)) {
    $data = api_request_data();
    $response = ['initiator' => [], 'recipients' => []];

    if (!empty($data->user->email)) {
      if (empty($data->user->currency) || !array_key_exists($data->user->currency, somi_api_account_currencies_mapping())) {
        throw new ApiException(
          sprintf(
            "Drupal API: валюта %s не привязана ни к одному из счетов.",
            !empty($data->user->currency) ? $data->user->currency : '')
        );
      }

      $currency = $data->user->currency;
      $account_tid = somi_api_account_currencies_mapping()[$data->user->currency]['active'];
      $passive_account_tid = somi_api_account_currencies_mapping()[$data->user->currency]['passive'];

      if (!($initiator = user_load_by_mail($data->user->email))) {
        throw new ApiException(
          t(
            'Пользователь с почтой @mail не может быть найден в Drupal API',
            ['@mail' => $data->user->email]
          )
        );
      }

      $transaction_amount = somi_get_user_account_balance($initiator->uid, $passive_account_tid);
      // Checking beetles quantity for one transaction depending on user`s role.
      $restrictions = somi_get_transaction_limit_settings($passive_account_tid);
      if (!empty($restrictions)) {
        $max_quantity = 1;
        $initiator_roles = array_flip($initiator->roles);
        foreach ($restrictions['roles_mapping'] as $role => $max_can_be_given) {
          if (isset($initiator_roles[$role])) {
            $max_quantity = $max_can_be_given;
            break;
          }
        }
        if ($data->user->transaction_amount > $max_quantity) {
          throw new ApiException(t($restrictions['message'], ['!qty' => $max_quantity]));
        }
      }
      $response['initiator']['balance'] = $transaction_amount;
      $response['initiator']['uid'] = $initiator->uid;

      if ($data->user->transaction_amount > $transaction_amount) {
        $error['code'] = 33;

        $goods = plural_str(
          $transaction_amount,
          somi_currencies_translations()[$currency][1],
          somi_currencies_translations()[$currency][2],
          somi_currencies_translations()[$currency][3]
        );

        $error['message'] = "Недостаточно " . somi_currencies_translations()[$currency][3] . " для совершения сделки, на вашем счёте $transaction_amount $goods.";
        $response['error'] = $error;
      }

      if (empty($data->recipients) || !is_array($data->recipients)) {
        throw new ApiException("Drupal API: получатель сделки указан не верно или отсутствует.");
      }

      foreach ($data->recipients as $recipient_email) {
        if (($account = user_load_by_mail($recipient_email))) {
          if ($initiator->uid == $account->uid) {
            throw new ApiException("Drupal API: Пользователь не может указывать получателем самого себя.");

          }

          $response['recipients'][] = [
            'balance' => somi_get_user_account_balance($account->uid, $account_tid),
            'uid' => $account->uid,
            'email' => $recipient_email,
          ];
        }
        else {
          throw new ApiException("Пользователь с почтой $recipient_email не может быть найден в Drupal API.");
        }
      }
    }
    else {
      throw new ApiException("Электронная почта пользователя который проводит сделку не доступна.");
    }
  }
  else {
    throw new ApiException("Пользователь не авторизован.");
  }

  // Add item to the queue. It will be passed to RabbitMQ later, when user will be enough crystals for operations,
  // and nodejs bot will check the queue and remind user that he is able to perform operation.
  if (!empty($response['error'])) {
    // Store attempt to give crystals to remind it later.
    $queue = DrupalQueue::get(SOMI_TRANSACTION_FAILED_OPS_QUEUE_NAME);
    $queue->createQueue();

    $op = new StdClass();
    $op->time = time();
    $op->currency = $data->user->currency;
    $op->uid = $initiator->uid;
    $op->slack_id = $data->user->id;
    $op->attempt_transaction_amount = $data->user->transaction_amount;
    $op->transaction_old_amount = $transaction_amount;
    $op->transaction_recipient_quantity = $data->user->amount_per_recipient;
    $op->raw_message = $data->message;

    $queue->createItem($op);
  }

  return $response;
}

/**
 * Get discussions of user or device.
 */
function api_user_account_balance_add_post() {
  global $user;

  $prefix = 'Slack Event: ';
  if (empty($user->uid)) {
    throw new ApiException("Пользователь не авторизован.");
  }
  $data = api_request_data();
  $response = ['initiator' => [], 'recipients' => []];

  if (empty($data->user->email)) {
    throw new ApiException("Электронная почта пользователя который проводит сделку не доступна.");
  }

  if (empty($data->user->currency) || !array_key_exists($data->user->currency, somi_api_account_currencies_mapping())) {
    throw new ApiException(
      sprintf(
        "Drupal API: валюта %s не привязана ни к одному из счетов.",
        !empty($data->user->currency) ? $data->user->currency : '')
    );
  }

  // Avoid markup filter stripping text wrapped with triangle braces.
  $data->message = preg_replace('/<|>/', '', $data->message);

  $currency = $data->user->currency;
  $account_tid = somi_api_account_currencies_mapping()[$currency]['active'];
  $passive_account_tid = somi_api_account_currencies_mapping()[$currency]['passive'];

  $initiator = user_load_by_mail($data->user->email);

  // Check users are ok.
  foreach ($data->recipients as $recipient) {
    if (!($account = user_load_by_mail($recipient->email))) {
      throw new ApiException("Пользователь с адресом {$recipient->email} не может быть найден в Drupal API.");
    }
  }

  // Credit crystals from user account as he gave them.
  somi_add_user_account_balance(
    $initiator->uid,
    -1 * $data->user->transaction_amount,
    $passive_account_tid,
    $prefix . $data->message,
    $initiator->uid
  );

  $transaction_amount = somi_get_user_account_balance($initiator->uid, $passive_account_tid);

  $response['initiator']['balance'] = $transaction_amount;
  $response['initiator']['uid'] = $initiator->uid;

  foreach ($data->recipients as $recipient) {
    $account = user_load_by_mail($recipient->email);

    // Debet crystals.
    somi_add_user_account_balance(
      $account->uid,
      $recipient->amount,
      $account_tid,
      $prefix . $data->message,
      $initiator->uid
    );

    $response['recipients'][] = [
      'balance' => somi_get_user_account_balance($account->uid, $account_tid),
      'uid' => $account->uid,
      'email' => $recipient->email,
    ];
  }

  return $response;
}

/**
 * Get discussions of user or device.
 */
function api_users_info_get() {
  global $user;

  if (!empty($user->uid)) {
    $response = array('items' => array());

    $query = db_select('users', 'u');
    $query->addField('u', 'uid');
    $query->condition('status', 1);
    $uids = $query->execute()->fetchCol();

    if (($accounts = user_load_multiple($uids))) {
      foreach ($accounts as $account) {
        if ($account->uid == 1) continue;

        $roles = $account->roles;

        switch (TRUE) {
          case array_search('top', $roles) !== FALSE:
            $role = 'top';
            break;

          case array_search('core', $roles) !== FALSE:
            $role = 'core';
            break;

          case array_search('active', $roles) !== FALSE:
            $role = 'active';
            break;

          default:
            $role = 'authenticated user';
        }

        $user_data = array(
          'uid' => $account->uid,
          'name' => $account->name,
          'email' => $account->mail,
          'role' => $role,
          'image' => somi_get_gravatar_image_url_by_mail($account->mail),
          'departament' => [],
          'accounts' => [],
          'devices' => [],
        );

        if (!empty($account->field_department['und'][0]['target_id'])) {
          $dep = taxonomy_term_load($account->field_department['und'][0]['target_id']);
          $user_data['departament'] = [
            'id' => $account->field_department['und'][0]['target_id'],
            'name' => $dep->name,
          ];
        }

        $currencies = somi_get_currencies();

        foreach ($currencies as $currency) {
          $currency_tid = somi_get_currency_tid($currency);
          $account_value = somi_get_user_account_balance($account->uid, $currency_tid);
          $user_data['accounts'][] = [
            'id' => $currency_tid,
            'balance' => $account_value,
          ];
        }

        $w = entity_metadata_wrapper('user', $account);
        $rfids = $w->field_rfid_tags->value();
        foreach ($rfids as $rfid) {
          $user_data['devices'][] = [
            'id' => $rfid->title,
          ];
        }

        $response['items'][] = $user_data;
      }
    }
  }
  else {
    throw new ApiException("User is not authorized.");
  }

  return $response;
}

/**
 * Get discussions of user or device.
 */
function api_users_time_get() {
  global $user;

  if (!empty($user->uid)) {
    $data = somi_get_time_of_arrival_data();

    switch (TRUE) {
      case empty($_GET['sort']):
        $sort = 'access';
        break;

      case $_GET['sort'] == 1:
        $sort = 'last_access';
        break;

      case $_GET['sort'] == 2:
        $sort = 'average';
        break;

      default:
        $sort = 'access';
        break;
    }

    $groupby = '';
    if (!empty($_GET['groupby'])) {
      switch ($_GET['groupby']) {
        case 1:
          $groupby = 'departament';
          break;

        case 2;
          $groupby = 'node';
          break;

        default:
          $groupby = '';
          break;
      }
    }

    $response = array(
      'sort' => $sort,
      'groupby' => $groupby,
      'items' => array(),
    );

    foreach ($data as $item) {
      $response['items'][] = [
        'id' => $item['uid'],
        'first_access' => $item['sub_info'],
        'last_access' => $item['info'],
        'work_time' => $item['top_info'],
        'class' => $item['class'],
        'group' => !empty($item['group']) ? $item['group'] : '',
      ];
    }
  }
  else {
    throw new ApiException("User is not authorized.");
  }

  return $response;
}

/**
 * Get users activity.
 */
function api_users_activity_by_dep_get() {
  global $user;

  if (!empty($user->uid)) {
    if (!preg_match('/^v1\/users\/activity\/([0-9]+)\b/', $_GET['q'], $matches)) {
      throw new ApiException("Departament id is wrong");
    }

    $data = somi_get_availability_data($matches[1]);

    if (!empty($_GET['sort_order'])) {
      $sort = 'reverse';
    } else {
      $sort = 'default';
    }

    $groupby = '';
    if (!empty($_GET['groupby'])) {
      switch ($_GET['groupby']) {
        case 1:
          $groupby = 'departament';
          break;

        case 2;
          $groupby = 'node';
          break;

        default:
          $groupby = '';
          break;
      }
    }

    $response = array(
      'sort' => $sort,
      'groupby' => $groupby,
      'items' => array(),
    );

    foreach ($data as $item) {
      $response['items'][] = [
        'id' => $item['uid'],
        'last_access' => $item['info'],
        'class' => $item['class'],
        'group' => !empty($item['group']) ? $item['group'] : '',
      ];
    }
  }
  else {
    throw new ApiException("User is not authorized.");
  }

  return $response;
}

/**
 * Get users activity.
 */
function api_users_activity_get() {
  global $user;

  if (!empty($user->uid)) {
    $data = somi_get_availability_data();

    if (!empty($_GET['sort_order'])) {
      $sort = 'reverse';
    } else {
      $sort = 'default';
    }

    $groupby = '';
    if (!empty($_GET['groupby'])) {
      switch ($_GET['groupby']) {
        case 1:
          $groupby = 'departament';
          break;

        case 2;
          $groupby = 'node';
          break;

        default:
          $groupby = '';
          break;
      }
    }

    $response = array(
      'sort' => $sort,
      'groupby' => $groupby,
      'items' => array(),
    );

    foreach ($data as $item) {
      $response['items'][] = [
        'id' => $item['uid'],
        'last_access' => $item['info'],
        'class' => $item['class'],
        'group' => !empty($item['group']) ? $item['group'] : '',
      ];
    }
  }
  else {
    throw new ApiException("User is not authorized.");
  }

  return $response;
}

/**
 * Get discussions of user or device.
 */
function api_user_account_get($type, $uid, $account_id) {
  global $user;

  if (!empty($user->uid)) {

    switch (TRUE) {
      case !($account = user_load($uid)):
        throw new ApiException("Wrong user id.");

      case !($currency_term = taxonomy_term_load($account_id)):
        throw new ApiException("Wrong account id.");
    }

    return array(
      'id' => $currency_term->tid,
      'currency' => $currency_term->name,
      'image' => preg_replace('/\bapi\/\b/', '', image_style_url('thumbnail', $currency_term->field_image['und'][0]['uri'])),
      'balance' => somi_get_user_account_balance($account->uid, $currency_term->tid),
    );
  }
  else {
    throw new ApiException("User is not authorized.");
  }
}

/**
 * Get discussions of user or device.
 */
function api_user_accounts_get($type, $uid) {
  global $user;

  if (!empty($user->uid)) {

    switch (TRUE) {
      case !($account = user_load($uid)):
        throw new ApiException("Wrong user id.");
    }

    $accounts = [];

    foreach (somi_get_currencies() as $currency) {
      $currency_tid = somi_get_currency_tid($currency);
      $currency_term = taxonomy_term_load($currency_tid);

      $accounts[] = array(
        'id' => $currency_tid,
        'currency' => $currency,
        'image' => preg_replace('/\bapi\/\b/', '', image_style_url('thumbnail', $currency_term->field_image['und'][0]['uri'])),
        'balance' => somi_get_user_account_balance($account->uid, $currency_tid),
      );
    }

    return ['items' => $accounts];
  }
  else {
    throw new ApiException("User is not authorized.");
  }
}

/**
 * Get discussions of user or device.
 */
function api_accounts_get() {
  global $user;

  if (!empty($user->uid)) {
    $accounts = [];

    foreach (somi_get_currencies() as $currency) {
      $currency_tid = somi_get_currency_tid($currency);
      $currency_term = taxonomy_term_load($currency_tid);

      $accounts[] = array(
        'id' => $currency_tid,
        'currency' => $currency,
        'image' => preg_replace('/\bapi\/\b/', '', image_style_url('thumbnail', $currency_term->field_image['und'][0]['uri'])),
      );
    }

    return ['items' => $accounts];
  }
  else {
    throw new ApiException("User is not authorized.");
  }
}

/**
 * Get discussions of user or device.
 */
function api_device_info_get() {
  global $user;

  if (!empty($user->uid)) {

    switch (TRUE) {
      case empty($_GET['id']) || !($rfid = somi_get_rfid_by_id($_GET['id'])):
        throw new ApiException("Wrong device id.");
    }

    return array(
      'id' => $rfid->title,
      'type' => somi_get_device_type_by_nid($rfid->nid, TRUE),
      'color' => somi_get_device_color_by_nid($rfid->nid, TRUE),
      'image' => preg_replace('/\bapi\/\b/', '', somi_get_device_image_by_id($rfid->nid)),
      'icon' => preg_replace('/\bapi\/\b/', '', somi_get_device_icon_by_id($rfid->nid)),
      'status' => $rfid->status,
    );
  }
  else {
    throw new ApiException("User is not authorized.");
  }
}

/**
 * Get discussions of user or device.
 */
function api_user_account_balance_post() {
  global $user;

  $data = api_request_data();

  $account = user_load_by_mail($data->user->email);

  if (!empty($user->uid)) {
    $response = array('items' => array());

    $query = db_select('users', 'u');
    $query->addField('u', 'uid');
    $query->condition('status', 1);
    $uids = $query->execute()->fetchCol();

    if (($accounts = user_load_multiple($uids))) {
      foreach ($accounts as $account) {
        $roles = $account->roles;

        switch (TRUE) {
          case array_search('top', $roles) !== FALSE:
            $role = 'top';
            break;

          case array_search('core', $roles) !== FALSE:
            $role = 'core';
            break;

          case array_search('active', $roles) !== FALSE:
            $role = 'active';
            break;

          default:
            $role = 'authenticated user';
        }

        $response['items'][] = array(
          'uid' => $account->uid,
          'name' => $account->name,
          'email' => $account->mail,
          'role' => $role,
          'slackName' => $account->mail,
        );
      }
    }
  }
  else {
    throw new ApiException("User is not authorized.");
  }

  return $response;
}

/**
 * Create history for message and send via webhooks.
 */
function api_create_history() {
  $data = api_request_data();
  $user = user_load_by_name($data->author_name);
  $query_data = [
    'tid' => $data->tid,
    'uid' => $user->uid,
    'message' => $data->message,
    'date' => REQUEST_TIME
  ];
  drupal_write_record('notifications', $query_data);

  // PHP apache doesn't able deal with pcntl functions, only CDG or CLI modes.
  // so do it in the same process for web request access loging :'C.
  if (function_exists('pcntl_fork')) {
    $pid = pcntl_fork();

    // Parent process should immediately response to hardware.
    if ($pid === -1 || $pid > 0) {
      return;
    }

    // Allow parent process (udpsrv to response avr MCU immediately without waiting curl delay).
    fclose(STDIN);
    fclose(STDOUT);
    fclose(STDERR);
  }

  // Child process continues HTTP Slack API invocation.
  $slack_webhook_url = $data->webhooks;


  $slack_payload = json_encode($data->options);

  $params = http_build_query(['payload' => $slack_payload]);

  // Send event to all listeners.
  foreach ($slack_webhook_url as $webhook_url) {
    $defaults = array(
      CURLOPT_URL => $webhook_url,
      CURLOPT_POST => TRUE,
      CURLOPT_POSTFIELDS => $params,
    );

    $ch = curl_init();

    curl_setopt_array($ch, $defaults);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
      'Content-type: application/x-www-form-urlencoded',
    ));

    curl_exec($ch);
    curl_close($ch);
  }

  if (function_exists('pcntl_fork')) {
    exit;
  }
}

/**
 * Manages headers requirements between server & PCD nodes.
 */
function api_node_prepare_headers() {
  $headers = [
    'HTTP_DESTINATION',
    'HTTP_ACTION',
    'HTTP_NODE_ID',
    'HTTP_TIME',
    'HTTP_UID',
    'HTTP_PCD_NUMBER',
    'HTTP_CACHED',
  ];

  foreach ($headers as $header) {
    if (!empty($_SERVER[$header])) {
      switch ($header) {
        case 'HTTP_DESTINATION':
          if (empty($_SERVER['HTTP_NODE_ID'])) continue 2;
          $value = $_SERVER['HTTP_NODE_ID'];
          break;

        case 'HTTP_NODE_ID':
          if (empty($_SERVER['HTTP_DESTINATION'])) continue 2;
          $value = $_SERVER['HTTP_DESTINATION'];
          break;

        default:
          $value = $_SERVER[$header];
          break;
      }

      if (!empty($value)) {
        $header = strtolower(preg_replace('/HTTP_/', '', $header));
        $header = preg_replace('/_/', '-', $header);

        header(
          $header . ': ' . $value
        );
      }
    }
  }

  header('access:  denied');
}

/**
 * Checks needed headers.
 *
 * @param $endpoint
 * @throws \ApiAuthException
 */
function api_node_header_validation($endpoint) {
  // If no path is provided by the user, set our default path.
  $rank = '[0-9ABCDEF]{2,2}';
  $validate_pattern = "/$rank:$rank:$rank:$rank/";

  switch ($endpoint) {
    case 'api_node_time_sync_post':
      switch (TRUE) {
        case !isset($_SERVER['HTTP_DESTINATION']):
        case !isset($_SERVER['HTTP_ACTION']):
        case !isset($_SERVER['HTTP_NODE_ID']):
        case !isset($_SERVER['HTTP_TIME']):
          throw new ApiAuthException(
            'Request has no needed headers to perform operation (please check APIARY for required headers)',
            24
          );
          break;
      }
      break;

    case 'api_node_access_get':
      switch (TRUE) {
        case !isset($_SERVER['HTTP_DESTINATION']):
        case !isset($_SERVER['HTTP_ACTION']):
        case !isset($_SERVER['HTTP_NODE_ID']):
        case !isset($_SERVER['HTTP_TIME']):
        case !isset($_SERVER['HTTP_UID']):
        case !isset($_SERVER['HTTP_PCD_NUMBER']):
          throw new ApiAuthException(
            'Request has no needed headers to perform operation (please check APIARY for required headers)',
            24
          );
          break;

        case !preg_match($validate_pattern, $_SERVER['HTTP_UID']):
          throw new ApiAuthException(
            'UID has invalid format it should be like "FF:FF:FF:FF"',
            25
          );
          break;
      }
      break;

    case 'api_node_event_post':
      switch (TRUE) {
        case !isset($_SERVER['HTTP_DESTINATION']):
        case !isset($_SERVER['HTTP_ACTION']):
        case !isset($_SERVER['HTTP_NODE_ID']):
        case !isset($_SERVER['HTTP_TIME']):
        case !isset($_SERVER['HTTP_UID']):
        case !isset($_SERVER['HTTP_PCD_NUMBER']):
        case !isset($_SERVER['HTTP_CACHED']):
        case !isset($_SERVER['HTTP_ACCESS']):
          throw new ApiAuthException(
            'Request has no needed headers to perform operation (please check APIARY for required headers)',
            24
          );
          break;

        case !preg_match($validate_pattern, $_SERVER['HTTP_UID']):
          throw new ApiAuthException(
            'UID has invalid format it should be like "FF:FF:FF:FF"',
            25
          );
          break;
      }
      break;
  }
}

/**
 * Keep track with difference of time in seconds with PCD node.
 */
function api_node_time_sync_post() {
  api_node_prepare_headers();

  // Check that request has all needed input data.
  api_node_header_validation(__FUNCTION__);

  $synced_times = (array) variable_get('somi_api_nodes_time_sync', []);

  // Time that comes from PCD (hardware) is time in milliseconds sice device boot (ticks).
  // so we keep track of the time of the events that device sends by
  // calculating the time of the device boot & using it during event log ops.
  $synced_times[$_SERVER['HTTP_NODE_ID']] = time() - round($_SERVER['HTTP_TIME'] / 1000);
  variable_set('somi_api_nodes_time_sync', $synced_times);

  header('time: ' . $synced_times[$_SERVER['HTTP_NODE_ID']]);

  return ['status' => 'time is synced'];
}

/**
 * Checks whether requested card has access to PCD node.
 */
function api_node_access_get() {
  api_node_prepare_headers();

  // Check that request has all needed input data.
  api_node_header_validation(__FUNCTION__);

  $time = time();
  $id = $_SERVER['HTTP_UID'];

  module_load_include('inc', 'somi', 'somi.pages');
  module_load_include('inc', 'somi', 'somi.drush');

  if (!($rfid = somi_get_rfid_by_id($id))) {
    throw new ApiAuthException('No device with such UID can be found.', 28);
  }

  if (!($account = somi_get_user_by_rfid($id))) {
    throw new ApiAuthException('RFID does not belong to any user .', 29);
  }

  $access = somi_access_handler($id);

  $pcd_node_hash = $_SERVER['HTTP_NODE_ID'];

  if (!($pcd_node = somi_get_pcd_node_by_hash($pcd_node_hash))) {
    throw new ApiAuthException(
      'Node with requested hash could no be found.',
      32
    );
  }

  if (variable_get('node requests debug', FALSE)) {
    watchdog(WATCHDOG_DEBUG, "card:$id node:{$pcd_node->nid}");
  }

  if ($account && $rfid) {
    $context = array(
      'uid' => $account->uid,
      'rfid' => $rfid->nid,
      'rfid_node' => $pcd_node->nid,
      'name' => $account->name,
      'time' => $time,
      'mail' => $account->mail,
      'card' => $id,
    );

    somi_log_event($context, $access);
  }

  header('access: ' . (!empty($access) ? 'granted' : 'denied'));

  return ['status' => 'check performed'];
}

/**
 * Syncs events from PCD nodes.
 */
function api_node_event_post() {
  api_node_prepare_headers();

  // Check that request has all needed input data.
  api_node_header_validation(__FUNCTION__);

  $synced_times = variable_get('somi_api_nodes_time_sync', []);
  if (empty($synced_times[$_SERVER['HTTP_NODE_ID']])) {
    throw new ApiAuthException(
      sprintf(
        "Node's time with ID %s is not synced",
        $_SERVER['HTTP_NODE_ID']
      ),
      27
    );
  }

  // Synced times stores timestamp of the moment when device was boot. Device
  // has ticks (milliseconds). So device knows how much ticks have passed since
  // boot but don't know the real time. The server keep tracks of the boot time
  // & adds seconds since device boot to the event.
  $time = $synced_times[$_SERVER['HTTP_NODE_ID']]
    + round($_SERVER['HTTP_TIME'] / 1000);

  $id = $_SERVER['HTTP_UID'];

  module_load_include('inc', 'somi', 'somi.pages');
  module_load_include('inc', 'somi', 'somi.drush');

  if (!($rfid = somi_get_rfid_by_id($id))) {
    throw new ApiAuthException('No device with such UID can be found.', 28);
  }

  if (!($account = somi_get_user_by_rfid($id))) {
    throw new ApiAuthException('RFID does not belong to any user .', 29);
  }

  $access = somi_access_handler($id);

  // PCD access has currently these cases: 0 (denied), 1 (granted), 4 (unknown).
  $pcd_access = (int) $_SERVER['HTTP_ACCESS'];

  // If PCD sends event which has state that differs from access granted
  // or denied  state we throws the error.
  if ($pcd_access > 1) {
    throw new ApiAuthException(
      'Syncing event has not known access value'
      , 30
    );
  }

  $pcd_node_hash = $_SERVER['HTTP_NODE_ID'];

  if (!($pcd_node = somi_get_pcd_node_by_hash($pcd_node_hash))) {
    throw new ApiAuthException(
      'Node with requested hash could no be found.',
      32
    );
  }

  if (variable_get('node requests debug', FALSE)) {
    watchdog(WATCHDOG_DEBUG, "card:$id node:{$pcd_node->nid}");
  }

  $context = array(
    'uid' => $account->uid,
    'rfid' => $rfid->nid,
    'rfid_node' => $pcd_node->nid,
    'name' => $account->name,
    'time' => $time,
    'mail' => $account->mail,
    'card' => $id,
  );

  somi_log_event($context, $pcd_access);

  header('access: ' . (!empty($access) ? 'granted' : 'denied'));

  return [
    'status' => 'event synced',
    'invalidate' => (int) $access !== $pcd_access,
    'access' => (int) $access,
  ];
}
