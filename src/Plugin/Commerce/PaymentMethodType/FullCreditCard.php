<?php

namespace Drupal\iyzipay\Plugin\Commerce\PaymentMethodType;

use Drupal\entity\BundleFieldDefinition;
use Drupal\commerce_payment\CreditCard as CreditCardHelper;
use Drupal\commerce_payment\Entity\PaymentMethodInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentMethodType\PaymentMethodTypeBase;

/**
 * Provides the credit card payment method type.
 *
 * @CommercePaymentMethodType(
 *   id = "full_credit_card",
 *   label = @Translation("Full Credit card"),
 *   create_label = @Translation("New credit card"),
 * )
 */
class FullCreditCard extends PaymentMethodTypeBase {

  /**
   * {@inheritdoc}
   */
  public function buildLabel(PaymentMethodInterface $payment_method) {

    $encryption_service = \Drupal::service('encryption');

    $decrypted_card_type = $encryption_service->decrypt($payment_method->encrypted_full_card_type->value);
    $decrypted_card_number = $encryption_service->decrypt($payment_method->encrypted_full_card_number->value);

    $card_type = CreditCardHelper::getType($decrypted_card_type);

    $args = [
      '@card_type' => $card_type->getLabel(),
      '@card_number' => substr($decrypted_card_number, -4),
    ];
    return $this->t('@card_type ending in @card_number', $args);

  }

  /**
   * {@inheritdoc}
   */
  public function buildFieldDefinitions() {
    $fields = parent::buildFieldDefinitions();

    $fields['encrypted_full_holder_name'] = BundleFieldDefinition::create('string_long')
      ->setLabel($this->t('Holder name'))
      ->setDescription($this->t('Holder name on the card.'))
      ->setRequired(TRUE);

    $fields['encrypted_full_card_type'] = BundleFieldDefinition::create('string_long')
      ->setLabel($this->t('Card type'))
      ->setDescription($this->t('The credit card type.'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values_function', ['\Drupal\commerce_payment\CreditCard', 'getTypeLabels']);

    $fields['encrypted_full_card_number'] = BundleFieldDefinition::create('string_long')
      ->setLabel($this->t('Card number'))
      ->setDescription($this->t('The credit card number.'))
      ->setRequired(TRUE);

    $fields['encrypted_full_card_exp_month'] = BundleFieldDefinition::create('string_long')
      ->setLabel($this->t('Card expiration month'))
      ->setDescription($this->t('The credit card expiration month.'));

    $fields['encrypted_full_card_exp_year'] = BundleFieldDefinition::create('string_long')
      ->setLabel($this->t('Card expiration year'))
      ->setDescription($this->t('The credit card expiration year.'));

    $fields['encrypted_full_card_cvv'] = BundleFieldDefinition::create('string_long')
      ->setLabel($this->t('Card verification value'))
      ->setDescription($this->t('The credit card verification value.'));

    return $fields;
  }

}
