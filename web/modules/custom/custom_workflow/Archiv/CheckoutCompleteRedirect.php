<?php
namespace Drupal\custom_workflow\EventSubscriber;

use Drupal\commerce_checkout\CheckoutOrderManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Drupal\Core\Render\Renderer;
use Drupal\Core\Url;

class CheckoutCompleteRedirect implements EventSubscriberInterface {

  use StringTranslationTrait;

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
   * The checkout order manager.
   *
   * @var \Drupal\commerce_checkout\CheckoutOrderManagerInterface
   */
  protected $checkoutOrderManager;

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Renderer
   */
  protected $renderer;

  /**
   * CheckoutCompleteRedirect constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, Messenger $messenger, CheckoutOrderManagerInterface $checkout_order_manager, FormBuilderInterface $form_builder, Renderer $renderer) {
    $this->entityTypeManager = $entity_type_manager;
    $this->messenger = $messenger;
    $this->checkoutOrderManager = $checkout_order_manager;
    $this->formBuilder = $form_builder;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritDoc}
   */
  static function getSubscribedEvents() {
    $events[KernelEvents::RESPONSE][] = array('checkForRedirection');
    return $events;
  }

  /**
   * Checks if the customer has other carts and redirects directly
   *   to the checkout page of the first unprocessed order.
   *
   * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function checkForRedirection(FilterResponseEvent $event) {

    $request = $event->getRequest();
    $path = $request->getRequestUri();

    if (strpos($path, '/checkout') === 0 && strpos($path, '/complete') > 8) {

      if ($current_order = $request->get('commerce_order')) {

        $customer = $current_order->getCustomer();

        // Retrieves all carts from the customer.
        $carts = $this->entityTypeManager
          ->getStorage('commerce_order')
          ->loadByProperties(['uid' => $customer->id(), 'cart' => '1']);

        if ($carts) {
          // Gets the first cart to process the checkout.
          $cart = reset($carts);

          $order_id = $cart->id();

          if ($order_id && $cart->hasItems() === TRUE) {

            // Redirects to the checkout page of the unprocessed orders.
            $uri = Url::fromUserInput('/checkout/' . $order_id . '/order_information')->toString();

            $event->setResponse(new RedirectResponse($uri));

            // Show oder complete message instead of order complete page.
            $current_order_id = $current_order->id();
            $customer_id = $current_order->getCustomerId();

            $options = [
              '@order_id' => $current_order_id,
              '@customer_id' => $customer_id,
            ];

            // Retrieves date from order complete page.
            $checkout_flow = $this->checkoutOrderManager->getCheckoutFlow($current_order);
            $checkout_flow_plugin = $checkout_flow->getPlugin();

            $order_complete_form = $this->formBuilder->getForm($checkout_flow_plugin, 'complete');

            $order_complete_message = $this->renderer->renderPlain($order_complete_form);

            $order_complete_message .= '<p><a href="/user/@customer_id/orders" target="_blank">Bestellungen in Ihrem Benutzerkonto einsehen</a></p>';

            if ($current_order->bundle() != 'course') {
              $message = $this->t('<h2>Produktbestellung abgeschlossen</h2>' . $order_complete_message, $options);
            } else {
              $message = $this->t('<h2>Kursbuchung abgeschlossen</h2>' . $order_complete_message, $options);
            }

            $this->messenger->addStatus($message);
          }
        }
      }
    }
  }
}
