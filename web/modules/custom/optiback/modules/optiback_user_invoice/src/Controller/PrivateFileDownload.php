<?php

namespace Drupal\optiback_user_invoice\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Drupal\Core\Session\AccountProxyInterface;

/**
 * Controller class PrivateFileDownload.
 *
 * Returns a Download for the Pdf-invoice, if access check is passed.
 */
class PrivateFileDownload extends ControllerBase {

  use StringTranslationTrait;

  /**
   * @var AccountProxy
   */
  protected $currentUser;

  /**
   * @var MessengerInterface
   */
  protected $messenger;

  /**
   * {@inheritdoc}
   */
  public function __construct(AccountProxyInterface $currentUser, MessengerInterface $messenger) {
    $this->currentUser = $currentUser;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user'),
      $container->get('messenger')
    );
  }

  public function download($uid, $order_id) {

    $currentUserId = $this->currentUser->id();
    $usersOrder = FALSE;

    // Checks if the uid form url is the current user.
    if ($currentUserId != $uid) {
      $this->messenger->addWarning($this->t('Sie sind nicht als der Benutzer :uid angemeldet.', [":uid" => $uid]));
      return [
        '#markup' => $this->t('Melden Sie sich bitte zuerst als Benutzer :uid an.', [":uid" => $uid])
      ];
    }

    $orders = \Drupal::entityTypeManager()
      ->getStorage('commerce_order')
      ->loadByProperties(['uid' => $currentUserId]);

    foreach ($orders as $order) {
      // Checks if the order belongs to the current user.
      if ($order->id() == $order_id) {
        $usersOrder = TRUE;
      }
    }

    if ($usersOrder !== TRUE) {
      $this->messenger->addWarning($this->t('Die Bestellung gehört nicht Ihnen.'));
      return [
        '#markup' => 'Eventuell ist ein Fehler aufgetreten.'
      ];
    }

    $uri = 'private://user-invoices/' . $order_id . '.pdf';

    // Checks if file exists.
    if (!file_exists($uri)) {
      $this->messenger->addWarning($this->t('Die Rechnung ist noch nicht verfügbar.'));

      return [
        '#markup' => 'Bitte versuchen Sie es später erneut.',
      ];
    }

    $headers = [
      'Content-Type'     => 'application/pdf',
      'Content-Disposition' => 'attachment;filename="download"'
    ];

    return new BinaryFileResponse($uri, 200, $headers, true);
  }
}
