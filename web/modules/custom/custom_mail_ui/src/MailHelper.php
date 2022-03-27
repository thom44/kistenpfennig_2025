<?php
namespace Drupal\custom_mail_ui;

use Drupal\commerce\MailHandler;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Utility\Token;

class MailHelper implements MailHelperInterface {

  /**
   * The config factory service.
   *
   * @var Drupal\Core\Config\ConfigFactoryInterface;
   */
  protected $configFactory;

  /**
   * The token service.
   *
   * @var Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * The commerce mailHandler service.
   *
   * @var Drupal\Commerce\MailHandler
   */
  protected $mailHandler;

  /**
   * MailHelper constructor.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   * @param \Drupal\Core\Utility\Token $token
   * @param \Drupal\commerce\MailHandler $mail_handler
   */
  public function __construct(ConfigFactoryInterface $config_factory, Token $token, MailHandler $mail_handler) {
    $this->configFactory = $config_factory;
    $this->token = $token;
    $this->mailHandler = $mail_handler;
  }

  /**
   * {@inheritDoc}
   */
  public function getEmailConfigTokenReplaced($config_key, $token_objects) {

    $data = [];

    $mail_config = $this->getMailConfig($config_key);

    $data['subject'] = $this->token->replace($mail_config['email_subject'], $token_objects, ['clear' => TRUE]);
    $data['body'] = $this->token->replace($mail_config['email_body'], $token_objects, ['clear' => TRUE]);

    $data['bcc'] = $mail_config['bcc_email'];
    $data['from'] = $mail_config['from_email'];

    return $data;
  }

  /**
   * {@inheritDoc}
   */
  public function getMailConfig($config_key) {

    $mail_config = [];

    // Retrieves email configuration.
    $config = $this->configFactory->get('custom_mail_ui.' . $config_key);
    $mail_config['email_subject'] = $config->get('email_subject');
    $mail_config['email_body'] = $config->get('email_body');
    $mail_config['bcc_email'] = $config->get('bcc_email');
    $mail_config['from_email'] = $config->get('from_email');

    return $mail_config;
  }

  /**
   * {@inheritdoc}
   */
  public function sendMail($module, $key, $to, $langcode, $params) {

    $body = [
      '#theme' => 'custom_mail_ui_email_wrapper',
      '#message' => $params['body'],
    ];

    $par = [
      'id' => $key,
      'from' => $params['from'],
      'subject' => $params['subject'],
      'langcode' => $langcode, // commerce mailmanager not using this langcode.
    ];

    if (isset($params['bcc'])) {
      $par['bcc'] = $params['bcc'];
    }

    if (isset($params['files'])) {
      $par['files'] = $params['files'];
    }

    // Send mail over commerce mail handler.
    // For the moment we use this mailHandler for all mails. Could be exchanged in the future.
    return $this->mailHandler->sendMail($to, $params['subject'], $body, $par);
  }
}
