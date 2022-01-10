<?php

namespace Drupal\wlw_order_mail_log\Plugin\Block;


use Drupal\Core\Block\BlockBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\wlw_order_mail_log\MailLoggerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Provides the order mail logger block.
 *
 * @Block(
 *   id = "wlw_order_mail_log_block",
 *   admin_label = @Translation("WLW Order Mail Logger Block"),
 *   category = @Translation("WLW"),
 * )
 */
class WlwOrderMailLogBlock extends BlockBase implements ContainerFactoryPluginInterface {

  use StringTranslationTrait;


  /**
   * The mail_logger service.
   *
   * @var \Drupal\wlw_order_mail_log\MailLoggerInterface
   */
  protected $mailLogger;

  /**
   * The messenger service.
   *
   * @var Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   * @param \Drupal\Core\Session\AccountInterface $account
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MailLoggerInterface $mail_logger, Messenger $messenger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->mailLogger = $mail_logger;
    $this->messenger = $messenger;
  }

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   *
   * @return static
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('wlw_order_mail_log.mail_logger'),
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {

    $output = [];

    // @todo: Use dependency injection for routeMatch after it is available.
    if ($order = \Drupal::routeMatch()->getParameters()->get('commerce_order')) {
      $order_id = $order->id();

      $data = $this->mailLogger->getLogEntries($order_id);

      // Creates and adds a user link.
      foreach ($data as $key => $value) {
        $user = $order->getCustomer();
        $uid = $user->id();
        $username = $order->getCustomer()->get('name')->getValue()[0]['value'];

        $link = Link::fromTextAndUrl($username, Url::fromUserInput('/user/' . $uid))->toString();

        $data[$key]['user'] = $link;
      }

      if ($data) {

        $header = [
          $this->t('Nr.'),
          $this->t('Bestellnummer'),
          $this->t('Datum/Zeit'),
          $this->t('Email Typ'),
          $this->t('Betreff'),
          $this->t('Empfänger'),
          $this->t('Absender'),
          $this->t('BCC'),
          $this->t('Kunde'),
        ];

        $output = [
          '#type' => 'table',
          '#header' => $header,
          '#rows' => $data,
        ];

      } else {
        $output = [
          '#markup' => $this->t('No Emails was sent.'),
        ];
      }

    } else {
      $this->messenger->addError($this->t('The commerce_order ID is not available from the path.'));
    }

    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    // Prevents caching this block.
    return 0;
  }
}