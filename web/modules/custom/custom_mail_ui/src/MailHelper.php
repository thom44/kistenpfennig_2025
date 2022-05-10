<?php
namespace Drupal\custom_mail_ui;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\File\FileSystem;
use Drupal\Core\Utility\Token;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mime\Email;;

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
   * The file manager service.
   *
   * @var \Drupal\Core\File\FileSystem;
   */
  protected $fileSystem;

  /**
   * MailHelper constructor.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   * @param \Drupal\Core\Utility\Token $token
   * @param \Drupal\commerce\MailHandler $mail_handler
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    Token $token,
    FileSystem $file_system
  ) {
    $this->configFactory = $config_factory;
    $this->token = $token;
    $this->fileSystem = $file_system;
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

    // Send mail over commerce mail handler.
    // $body = ['#theme' => 'custom_mail_ui_email_wrapper','#message' => $params['body'],];
    // $par = ['id' => $key,'from' => $params['from'],'subject' => $params['subject'],'langcode' => $langcode, ];
    // if (isset($params['bcc'])) { $par['bcc'] = $params['bcc']; }
    // if (isset($params['files'])) { $par['files'] = $params['files'];  }
    // For the moment we use this mailHandler for all mails. Could be exchanged in the future.
    // return $this->mailHandler->sendMail($to, $params['subject'], $body, $par);

    // We use direct symfony mailer until drupal/sympfony_mailer is stable.
    // @see: symfony/mailer/README.md
    // @see: https://symfony.com/doc/current/mailer.html
    // native://default|sendmail://default|smtp://user:pass@smtp.example.com:25
    // SMTP:
    // Host: kistenpfennig-net.mail.protection.outlook.com
    #$transport = Transport::fromDsn('native://default');
    $transport = Transport::fromDsn('sendmail://default');
    #$transport = Transport::fromDsn('smtp://info@thomas-schuh.com:L!1DsS3A41sE@smtp.1und1.de:587');

    $mailer = new Mailer($transport);

    $email = (new Email())
      ->to($to)
      //->text($params['body'])
      ->html($params['body']);

    if (isset($params['subject']) && $params['subject']) {
      $email->subject($params['subject']);
    }
    if (isset($params['from']) && $params['from']) {
      $email->from($params['from']);
    }
    if (isset($params['cc']) && $params['cc']) {
      $email->cc($params['cc']);
    }

    if (isset($params['bcc']) && $params['bcc']) {
      $email->bcc($params['bcc']);
    }

    if (isset($params['reply_to']) && $params['reply_to']) {
      $email->replyTo($params['reply_to']);
    }

    if (isset($params['priority']) && $params['priority']) {
      $email->priority($params['priority']);
    }

    if (isset($params['files'])) {
      foreach ($params['files'] as $file) {
        if ($file instanceof \Drupal\file\Entity\File) {
          $uri = $file->getFileUri();
          $file_path = $this->fileSystem->realpath($uri);
        } else {
          $file_path = $file->uri;
        }
        // MimeType will be guessed.
        $email->attachFromPath($file_path);
      }
    }

    $mailer->send($email);

    // send() has no return value. If no Error we return true.
    return TRUE;
  }

}
