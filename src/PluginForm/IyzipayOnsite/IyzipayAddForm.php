<?php

namespace Drupal\iyzipay\PluginForm\IyzipayOnsite;

use Drupal\commerce_payment\CreditCard;
use Drupal\commerce_payment\PluginForm\PaymentGatewayFormBase;
use Drupal\commerce_payment\Exception\DeclineException;
use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\profile\Entity\Profile;

/**
 * IyzipayAddForm is a payment method for the Iyzipay gateway.
 */
class IyzipayAddForm extends PaymentGatewayFormBase {

  use StringTranslationTrait;

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Constructs a new PaymentMethodAddForm.
   */
  public function __construct() {
    $this->routeMatch = \Drupal::service('current_route_match');
  }

  /**
   * {@inheritdoc}
   */
  public function getErrorElement(array $form, FormStateInterface $form_state) {
    return $form['payment_details'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\commerce_payment\Entity\PaymentMethodInterface $payment_method */
    $payment_method = $this->entity;

    $form['#attached']['library'][] = 'commerce_payment/payment_method_form';
    $form['#tree'] = TRUE;
    $form['payment_details'] = [
      '#parents' => array_merge($form['#parents'], ['payment_details']),
      '#type' => 'container',
      '#payment_method_type' => $payment_method->bundle(),
    ];

    $form['payment_details'] = $this->buildCreditCardForm($form['payment_details'], $form_state);

    /** @var \Drupal\commerce_payment\Entity\PaymentMethodInterface $payment_method */
    $payment_method = $this->entity;
    /** @var \Drupal\profile\Entity\ProfileInterface $billing_profile */
    $billing_profile = Profile::create([
      'type' => 'customer',
      'uid' => $payment_method->getOwnerId(),
    ]);
    if ($order = $this->routeMatch->getParameter('commerce_order')) {
      $store = $order->getStore();
    }
    else {
      /** @var \Drupal\commerce_store\StoreStorageInterface $store_storage */
      $store_storage = \Drupal::entityTypeManager()->getStorage('commerce_store');
      $store = $store_storage->loadDefault();
    }

    $form['billing_information'] = [
      '#parents' => array_merge($form['#parents'], ['billing_information']),
      '#type' => 'commerce_profile_select',
      '#default_value' => $billing_profile,
      '#default_country' => $store ? $store->getAddress()->getCountryCode() : NULL,
      '#available_countries' => $store ? $store->getBillingCountries() : [],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->validateCreditCardForm($form['payment_details'], $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\commerce_payment\Entity\PaymentMethodInterface $payment_method */
    $payment_method = $this->entity;

    $this->submitCreditCardForm($form['payment_details'], $form_state);

    /** @var \Drupal\commerce_payment\Entity\PaymentMethodInterface $payment_method */
    $payment_method = $this->entity;
    $payment_method->setBillingProfile($form['billing_information']['#profile']);

    $values = $form_state->getValue($form['#parents']);
    /** @var \Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsStoredPaymentMethodsInterface $payment_gateway_plugin */
    $payment_gateway_plugin = $this->plugin;
    // The payment method form is customer facing. For security reasons
    // the returned errors need to be more generic.
    try {
      $payment_gateway_plugin->createPaymentMethod($payment_method, $values['payment_details']);
    }
    catch (DeclineException $e) {
      \Drupal::logger('commerce_payment')->warning($e->getMessage());
      throw new DeclineException('We encountered an error processing your payment method. Please verify your details and try again.');
    }
    catch (PaymentGatewayException $e) {
      \Drupal::logger('commerce_payment')->error($e->getMessage());
      throw new PaymentGatewayException('We encountered an unexpected error processing your payment method. Please try again later.');
    }
  }

  /**
   * Builds the credit card form.
   *
   * @param array $element
   *   The target element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the complete form.
   *
   * @return array
   *   The built credit card form.
   */
  protected function buildCreditCardForm(array $element, FormStateInterface $form_state) {
    // Get whether expiration and cvv are required from the payment gateway's
    // configuration.
    $payment_gateway = $this->entity->getPaymentGateway();
    $payment_gateway_config = $payment_gateway->getPluginConfiguration();

    $element['#attributes']['class'][] = 'credit-card-form';
    // Placeholder for the detected card type. Set by validateCreditCardForm().
    $element['#attached']['library'][] = 'iyzipay/card';
    $element['#attached']['library'][] = 'iyzipay/iyzipay';
    $element['holder_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name on Card'),
      '#attributes' => ['autocomplete' => 'off'],
      '#required' => TRUE,
      '#maxlength' => 60,
      '#size' => 20,
    ];

    $element['type'] = [
      '#type' => 'hidden',
      '#value' => '',
    ];
    $element['number'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Card number'),
      '#attributes' => ['autocomplete' => 'off'],
      '#required' => TRUE,
      '#maxlength' => 19,
      '#size' => 20,
    ];
    $element['expiration'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['credit-card-form__expiration'],
      ],
    ];
    $element['expiration']['month'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Month'),
      '#maxlength' => 2,
      '#required' => TRUE,
    ];

    $element['expiration']['year'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Year'),
      '#maxlength' => 2,
      '#required' => TRUE,
    ];
    $element['security_code'] = [
      '#type' => 'textfield',
      '#title' => $this->t('CVV'),
      '#attributes' => ['autocomplete' => 'off'],
      '#required' => TRUE,
      '#maxlength' => 4,
      '#size' => 4,
    ];

    return $element;
  }

  /**
   * Validates the credit card form.
   *
   * @param array $element
   *   The credit card form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the complete form.
   */
  protected function validateCreditCardForm(array &$element, FormStateInterface $form_state) {
    $values = $form_state->getValue($element['#parents']);
    $values['number'] = str_replace(" ", "", $values['number']);
    $card_type = CreditCard::detectType($values['number']);
    if (!$card_type) {
      $form_state->setError($element['number'], $this->t('You have entered a credit card number of an unsupported card type.'));
      return;
    }

    // Validate that the card type is one of the accepted by the payment
    // gateway.
    $payment_gateway = $this->entity->getPaymentGateway();
    $payment_gateway_config = $payment_gateway->getPluginConfiguration();
    $accepted_card_types = array(
      "amex", "mastercard", "visa", "troy", "visa electron",
    );

    if ($accepted_card_types && !in_array($card_type->getId(), $accepted_card_types)) {
      $form_state->setError(
        $element['number'],
        $this->t(
          'The %card_type card type is not currently accepted.',
          ['%card_type' => $card_type->getLabel()]
        )
      );
    }

    // Card number always required and should be validated.
    if (!CreditCard::validateNumber($values['number'], $card_type)) {
      $form_state->setError($element['number'], $this->t('You have entered an invalid credit card number.'));
    }

    if (!CreditCard::validateExpirationDate($values['expiration']['month'], '20' . $values['expiration']['year'])) {
      $form_state->setError($element['expiration'], $this->t('You have entered an expired credit card.'));
    }
    if (!CreditCard::validateSecurityCode($values['security_code'], $card_type)) {
      $form_state->setError($element['security_code'], $this->t('You have entered an invalid CVV.'));
    }

    // Persist the detected card type.
    $form_state->setValueForElement($element['type'], $card_type->getId());
  }

  /**
   * Handles the submission of the credit card form.
   *
   * @param array $element
   *   The credit card form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the complete form.
   */
  protected function submitCreditCardForm(array $element, FormStateInterface $form_state) {
    $values = $form_state->getValue($element['#parents']);
    $this->entity->encrypted_full_holder_name = $values['holder_name'];
    $this->entity->encrypted_full_card_type = $values['type'];
    $this->entity->encrypted_full_card_number = $values['number'];
    $this->entity->encrypted_full_card_exp_month = $values['expiration']['month'];
    $this->entity->encrypted_full_card_exp_year = $values['expiration']['year'];
    $this->entity->encrypted_full_card_cvv = $values['security_code'];
  }

}
