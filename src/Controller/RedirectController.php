<?php

namespace Drupal\iyzipay\Controller;

use Iyzipay\Model\ThreedsPayment;
use Iyzipay\Options;
use Iyzipay\Model\Locale;
use Iyzipay\Request\CreateThreedsPaymentRequest;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * This is a controller for 3D secure payment.
 */
class RedirectController extends ControllerBase {

  /**
   * Get proper return status from the 3D secure page.
   */
  public function getStatus(Request $request) {

    // Get parameters returning back from the 3D Secure interface.
    $status = $request->request->get('status');
    $paymentId = $request->request->get('paymentId');
    $conversationData = $request->request->get('conversationData');
    $conversationId = $request->request->get('conversationId');
    $mdStatus = $request->request->get('mdStatus');

    $query = \Drupal::entityQuery('commerce_payment');
    $query->condition('order_id', $conversationId);
    $query->condition('payment_gateway', 'iyzipay');
    $payment_ids = $query->execute();

    $return_product = 0;

    $message = "Your transaction has failed";
    $order_status = "failed";
    $message_status = "error";

    if (!empty($query) && $status == "success") {
      $payments = \Drupal::entityTypeManager()
        ->getStorage('commerce_payment')
        ->loadMultiple($payment_ids);

      foreach ($payments as $payment) {
        if ($payment->state->value == 'pending') {
          if ($mdStatus == 1) {
            $request = new CreateThreedsPaymentRequest();
            $request->setLocale(Locale::EN);
            $request->setConversationId($conversationId);
            $request->setPaymentId($paymentId);
            $request->setConversationData($conversationData);

            $payment_gateway_plugin = $payment->getPaymentGateway()->getPlugin();
            $configs = $payment_gateway_plugin->getConfiguration();

            $options = new Options();
            $options->setApiKey($configs['api_key']);
            $options->setSecretKey($configs['secret_key']);
            $options->setBaseUrl($configs['api_url']);

            $threedsPayment = ThreedsPayment::create($request, $options);

            // By sending the latest request to the gateway,
            // we should be making the payment.
            $status_3d = $threedsPayment->getStatus();

            if ($status_3d == "success") {
              $order_status = "completed";
              $message = "Your transaction is successful!";
              $message_status = "success";
              $query = \Drupal::entityQuery('commerce_order');
              $query->condition('order_id', $conversationId);
              $order_ids = $query->execute();

              $orders = \Drupal::entityTypeManager()
                ->getStorage('commerce_order')
                ->loadMultiple($order_ids);

              foreach ($orders as $order) {
                $order->setOrderNumber($conversationId);
                // $order->setState('completed');.
                $order->unlock();
                $order->state = "completed";
                $order->cart = 0;
                $order->setPlacedTime(time());
                $order->setCompletedTime(time());
                $order->save();
              }
            }
          }

          $payment->setRemoteId($paymentId);
          $payment->setState($order_status);
          $payment->save();

          \Drupal::messenger()->addMessage($this->t($message), $message_status);

          // Lets find the product id so we can redirect user to the product
          // that they just bought.
          $query = \Drupal::entityQuery('commerce_order_item');
          $query->condition('order_id', $conversationId);
          $order_ids = $query->execute();

          $order_items = \Drupal::entityTypeManager()
            ->getStorage('commerce_order_item')
            ->loadMultiple($order_ids);

          foreach ($order_items as $order_item) {
            $product = $order_item->getPurchasedEntity();
            $return_product = $product->getProductId();
          }

        }
      }
    }

    if (!$return_product) {
      $message = "Something went wrong";
      \Drupal::messenger()->addMessage($this->t($message), 'error');
      return new TrustedRedirectResponse('/');
    }
    else {
      return new TrustedRedirectResponse('/product/' . $return_product);
    }
  }

}
