<?php

namespace Drupal\custom_mail_ui;

interface MailHelperInterface {

  /**
   * Gets the mail config and replaces tokens.
   *
   * @param $config_key string The config key.
   * @param $token_objects array The token keys and objects.
   * @return array The mail data.
   */
  public function getEmailConfigTokenReplaced($config_key, $token_objects);

  /**
   * Retrieves mail configuration.
   *
   * @param $config_key string The config key.
   *
   * @return array The config data.
   */
  public function getMailConfig($config_key);

  /**
   * Sends an email with a the mailhandler we want to use.
   *
   * @param $module
   * @param $key
   * @param $to
   * @param $langcode
   * @param $params
   * @return mixed
   */
  public function sendMail($module, $key, $to, $langcode, $params);


}
