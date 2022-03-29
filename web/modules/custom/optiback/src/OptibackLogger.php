<?php

namespace Drupal\optiback;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\custom_mail_ui\MailHelperInterface;

class OptibackLogger implements OptibackLoggerInterface {

  /**
   * The logger service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger;
   */
  protected $logger;

  /**
   * The log message.
   * @var string $message
   */
  public $message;

  /**
   * The log level.
   * @var string $level
   */
  public $level;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    MailHelperInterface $mail_helper,
    LoggerChannelFactoryInterface $logger
  ) {
    $this->mailHelper = $mail_helper;
    $this->logger = $logger;
  }

  /**
   * {@inheritDoc}
   */
  public function addLog($message, $level) {
    $this->message = $message;
    $this->level = $level;
  }

  /**
   * {@inheritDoc}
   */
  public function getMessage() {
    return $this->message;
  }

  /**
   * {@inheritDoc}
   */
  public function getLevel() {
    return $this->level;
  }

  /**
   * {@inheritDoc}
   */
  public function sendMail($params) {
    // Email with attachment
    // @see optiback_mail() and mailsystem|swiftmail UI.
    #$log_path = ObtibackConfigInterface::OPTIBACK_LOG . $logfile;

    // Gets the prepared file std class for attachement.
    #$file = new \stdClass;
    #$file->uri = $log_path;
    #$file->filename = $logfile;
    #$file->filemime = 'text/plain';

    $parameter = [
      'subject' => $params['subject'],
      'body' => $params['body'] . '<br>' . $this->getLevel() . '<br>' . $this->getMessage(),
      'from' => ObtibackConfigInterface::EMAIL_FROM,
      'bcc' => ObtibackConfigInterface::EMAIL_BCC,
    ];

    if ($params['files']) {
      $parameter['files'] = $params['files'];
    }

    $mail = $this->mailHelper->sendMail(
      'optiback',
      'optiback',
      ObtibackConfigInterface::EMAIL_TO,
      'de',
      $parameter
    );
    return $mail;
  }
}


