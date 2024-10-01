<?php

namespace Drupal\custom_mail_ui\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\token\TreeBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure email subject and text.
 *
 * @internal
 */
class MailUiForm extends ConfigFormBase {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The role storage used when changing the admin role.
   *
   * @var \Drupal\user\RoleStorageInterface
   */
  protected $roleStorage;

  /**
   * The token tree builder service.
   *
   * @var \Drupal\token\TreeBuilderInterface
   */
  protected $tokenTreeBuilder;

  /**
   * The available email keys
   *
   * @var array
   */
  protected $emailKeys;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, TreeBuilderInterface $token_tree_builder) {
    parent::__construct($config_factory);
    $this->tokenTreeBuilder = $token_tree_builder;
    $this->setEmailKeys();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('token.tree_builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'custom_mail_ui';
  }

  /**
   * Sets the emailKeys.
   */
  protected function setEmailKeys() {
    $this->emailKeys = [
      'custom_mail_ui.commerce_order_recipient' => [
        '#title' => $this->t('Bestellbestätigung'),
      ],
      'custom_mail_ui.commerce_order_shipping' => [
        '#title' => $this->t('Versandbestätigung'),
      ],
      'custom_mail_ui.commerce_order_invoice' => [
        '#title' => $this->t('Rechnung'),
      ],
      'custom_mail_ui.commerce_order_credit' => [
        '#title' => $this->t('Gutschrift'),
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    $return = [];

    foreach ($this->emailKeys as $key => $value) {
      $return[] = $key;
    }
    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    foreach ($this->emailKeys as $key => $value) {
      $config = $this->config($key);

      $form['information'] = array(
        '#type' => 'vertical_tabs',
      );

      $form[$key] = array(
        '#type' => 'details',
        '#title' => $value['#title'],
        '#group' => 'information',
      );

      $form[$key][$key . 'title'] = [
        '#type' => 'markup',
        '#markup' => '<h2>' . $value['#title'] . '</h2>',
      ];

      // Default notifications address.
      $form[$key][$key . '_from_email'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Absender email address'),
        '#default_value' => $config->get('from_email'),
        '#description' => $this->t("The senders email address. Overrides the original system email."),
        '#maxlength' => 60,
      ];

      // Default notifications address.
      $form[$key][$key . '_bcc_email'] = [
        '#type' => 'textfield',
        '#title' => $this->t('BCC email address'),
        '#default_value' => $config->get('bcc_email'),
        '#description' => $this->t("The bcc email address. Overrides Order Type bcc. Use comma separated list for multiple emails. No spaces allowed."),
        '#maxlength' => 60,
      ];

      $form[$key][$key . '_email_subject'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Subject'),
        '#default_value' => $config->get('email_subject'),
        '#description' => $this->t('The email subject.'),
        '#required' => TRUE,
      ];

      $form[$key][$key . '_email_body'] = [
        '#type' => 'text_format',
        '#format' => 'full_html',
        '#title' => $this->t('Body'),
        '#default_value' => $config->get('email_body'),
        '#rows' => 8,
      ];
    }
    // Displays the available tokens to the form.
    $token_types = [
      'user',
      'node',
      'comment',
      'recipient_comment',
      'commerce_order',
      'profile',
      'custom_order_billing_address',
      'custom_order_shipping_address',
      'custom_order_items',
      'custom_order_payment',
    ];
    $form['tokens'] = $this->tokenTreeBuilder->buildRenderable($token_types);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    foreach ($this->emailKeys as $key => $value) {
      // Prepares right input form key.
      $form_key = str_replace('.','_',$key);
      $input = $form_state->getUserInput();

      $this->config($key)
        ->set('from_email', $input[$form_key . '_from_email'])
        ->set('bcc_email', $input[$form_key . '_bcc_email'])
        ->set('email_subject', $input[$form_key . '_email_subject'])
        ->set('email_body', $input[$form_key . '_email_body']['value'])
        ->save();
    }
  }

}
