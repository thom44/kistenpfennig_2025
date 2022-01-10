<?php

namespace Drupal\wlw_order_email\Form;

use Drupal\commerce\MailHandlerInterface;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\Messenger;
use Drupal\wlw_invoice\Mail\OrderInvoiceMailInterface;
use Drupal\wlw_order_mail_log\MailLoggerInterface;
use Drupal\wlw_order_token\OrderTokenProvider;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\wlw_invoice\InvoiceFileAttachmentInterface;

/**
 * Provides an UI for sending reminder emails to the customer.
 */
class OrderEmailForm extends FormBase {

  /**
   * The order token provider service.
   *
   * @var \Drupal\wlw_order_token\OrderTokenProvider $orderTokenProvider
   */
  protected $orderTokenProvider;

  /**
   * The mail handler.
   *
   * @var \Drupal\commerce\MailHandlerInterface
   */
  protected $mailHandler;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The messenger service.
   *
   * @var Drupal\Core\Messenger $messenger;
   */
  protected $messenger;

  /**
   * The order invoice mail service.
   *
   * @var \Drupal\wlw_invoice\Mail\OrderInvoiceMailInterface $orderInvoiceMail
   */
  protected $orderInvoiceMail;

  /**
   * The invoice file attachment service.
   *
   * @var \Drupal\wlw_invoice\InvoiceFileAttachmentInterface $invoiceFileAttachment
   */
  protected $invoiceFileAttachment;

  /**
   * The MailLogger service.
   *
   * @var \Drupal\wlw_order_mail_log\MailLoggerInterface
   */
  protected $mailLogger;

  /**
   * The Constructor
   */
  public function __construct(
    OrderTokenProvider $order_token_provider,
    MailHandlerInterface $mail_handler,
    EntityTypeManagerInterface $entity_type_manager,
    Messenger $messenger,
    OrderInvoiceMailInterface $order_invoice_mail,
    InvoiceFileAttachmentInterface $invoice_file_attachment,
    MailLoggerInterface $mail_logger
  ) {
    $this->orderTokenProvider = $order_token_provider;
    $this->mailHandler = $mail_handler;
    $this->entityTypeManager = $entity_type_manager;
    $this->messenger = $messenger;
    $this->orderInvoiceMail = $order_invoice_mail;
    $this->invoiceFileAttachment = $invoice_file_attachment;
    $this->mailLogger = $mail_logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('wlw_order_token.order_token_provider'),
      $container->get('commerce.mail_handler'),
      $container->get('entity_type.manager'),
      $container->get('messenger'),
      $container->get('wlw_invoice.order_invoice_mail'),
      $container->get('wlw_invoice.invoice_file_attachment'),
      $container->get('wlw_order_mail_log.mail_logger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'wlw_order_email_reminder';
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, OrderInterface $commerce_order = NULL) {

    $markup = '<h2>' . $this->t('Email an Kunden generieren und senden') . '</h2>';
    $markup .= '<p>' . $this->t('<a href="/admin/commerce/email" target="_blank">Zur Email Konfiguration</a>');

    $form['header'] = [
      '#type' => 'markup',
      '#markup' => $markup,
    ];

    $options = [
      'reminder' => $this->t('Email Zahlungserinnerung'),
      'credit' => $this->t('Email Gutschrift mit PDF-Gutschrift Anhang'),
      'invoice' => $this->t('Email Rechnung mit PDF-Rechnung Anhang'),
    ];

    $form['selected_email'] = [
      '#type' => 'radios',
      '#options' => $options,
      '#title' => $this->t('Select Email'),
    ];

    $form['order_id'] = [
      '#type' => 'hidden',
      '#value' => $commerce_order->id(),
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Send Email'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $values = $form_state->getValues();

    $order_id = $values['order_id'];

    $order = $this->entityTypeManager->getStorage('commerce_order')->load($order_id);
    $customer = $order->getCustomer();

    switch ($values['selected_email']) {
      case 'reminder':
        // Retrieves email configuration.
        $config = $this->orderTokenProvider->getEmailConfigTokenReplaced('wlw_mail_ui.order_reminder', $order, $customer);

        $body = [
          '#theme' => 'wlw_mail_ui_email_wrapper',
          '#message' => $config['body'],
        ];

        $params = [
          'id' => 'wlw_order_reminder',
          'from' => $config['from'],
          'bcc' => $config['bcc'],
        ];

        // Send the email.
        $to = $order->getEmail();
        if ($to) {
          $mail = $this->mailHandler->sendMail($to, $config['subject'], $body, $params);
          if ($mail) {
            $this->messenger->addStatus($this->t('Die E-Mail mit der Zahlungserinnerung wurde erfolgreich an den Kunden gesendet.'));

            // Saves order mail log.
            $this->mailLogger->saveLogEntry([
              'order_id' => $order_id,
              'email_to' => $to,
              'subject' => $config['subject'],
              'email_key' => $params['id'],
              'email_from' => $config['from'],
              'email_bcc' => $config['bcc'],
            ]);
          } else {
            $this->messenger->addError($this->t('Die E-Mail konnte nicht gesendet werden.'));
          }
        }

        break;
      case 'credit':
        // Retrieves email configuration.
        $config = $this->orderTokenProvider->getEmailConfigTokenReplaced('wlw_mail_ui.order_credit', $order, $customer);

        $body = [
          '#theme' => 'wlw_mail_ui_email_wrapper',
          '#message' => $config['body'],
        ];

        // Saves the credit pdf file and returns file object.
        $file = $this->invoiceFileAttachment->getCreditFile($order);

        $params = [
          'id' => 'wlw_order_credit',
          'from' => $config['from'],
          'bcc' => $config['bcc'],
          'files' => [$file]
        ];

        // Send the email.
        $to = $order->getEmail();
        if ($to) {
          $mail = $this->mailHandler->sendMail($to, $config['subject'], $body, $params);
          if ($mail) {
            $this->messenger->addStatus($this->t('Die E-Mail mit der Gutschrift wurde erfolgreich an den Kunden gesendet.'));

            // Saves order mail log.
             $this->mailLogger->saveLogEntry([
              'order_id' => $order_id,
              'email_to' => $to,
              'subject' => $config['subject'],
              'email_key' => $params['id'],
              'email_from' => $config['from'],
              'email_bcc' => $config['bcc'],
            ]);

          } else {
            $this->messenger->addError($this->t('Die E-Mail mit der Gutschrift konnte nicht gesendet werden.'));
          }
        }

        break;
      case 'invoice':
        // Sends order
        $this->orderInvoiceMail->send($order);
        break;
    }
  }
}