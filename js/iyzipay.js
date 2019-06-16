(function ($, Drupal, drupalSettings) {
  'use strict';
  Drupal.behaviors.iyzipayFancyCard = {
    attach: function (context, settings) {
      $(document).ready(function () {
        if ($('input#edit-payment-information-add-payment-method-payment-details-number').length) {
          Drupal.behaviors.iyzipayFancyCard.setupCard();
        }

        $('.payment-method--new').one('click', function () {
          if ($('input#edit-payment-information-add-payment-method-payment-details-number').length) {
            Drupal.behaviors.iyzipayFancyCard.setupCard();
          }
		  else {
            setTimeout(Drupal.behaviors.iyzipayFancyCard.setupCard(), 1000);
          }
        });
      });
    },
    setupCard: function () {
      if ($('.fancy_card_wrapper').length) {
        return;
      }

      $('.credit-card-form').prepend("<div class='fancy_card_wrapper'></div>");
      new Card({
        // a selector or DOM element for the form where users will
        // be entering their information
        form: '.credit-card-form', // *required*
        // a selector or DOM element for the container
        // where you want the card to appear
        container: '.fancy_card_wrapper', // *required*
        formSelectors: {
          numberInput: "input[data-drupal-selector='edit-payment-information-add-payment-method-payment-details-number']", // optional — default input[name="payment_information[add_payment_method][payment_details][number]"]
          expiryInput: "input[data-drupal-selector='edit-payment-information-add-payment-method-payment-details-expiration-month'], input[data-drupal-selector='edit-payment-information-add-payment-method-payment-details-expiration-year']", // optional — default input[name="expiry"]
          cvcInput: "input[data-drupal-selector='edit-payment-information-add-payment-method-payment-details-security-code']", // optional — default input[name="cvc"]
          nameInput: "input[data-drupal-selector='edit-payment-information-add-payment-method-payment-details-holder-name']" // optional - defaults input[name="name"],

        },

        width: 350, // optional — default 350px
        formatting: true, // optional - default true

        // Strings for translation - optional
        messages: {
          validDate: 'expire\ndate', // optional - default 'valid\nthru'
          monthYear: 'mm/yy' // optional - default 'month/year'
        },

        // Default placeholders for rendered fields - optional
        placeholders: {
          number: '•••• •••• •••• ••••',
          name: 'Full Name',
          expiry: '••/••',
          cvc: '•••'
        },

        masks: {
          cardNumber: '•' // optional - mask card number
        },

        // if true, will log helpful messages for setting up Card
        debug: false // optional - default false
      });

      $('.form-item-payment-information-add-payment-method-billing-information-address-0-address-country-code').before("<div class='billing_info'><h3>Billing information</h3></div>");

      $('form').find('input[type=text]').each(function (ev) {
        if (!$(this).val()) {
          $(this).parent().find('label').hide();
          $(this).attr('placeholder', $(this).parent().find('label').text());
        }
      });

      $('.credit-card-form').append("<div class='clear_both'></div>");

      $('#commerce-checkout-flow-multistep-default').submit(function () {
        // lets correct the credit card format
        var str = $('#edit-payment-information-add-payment-method-payment-details-number').val().replace(/\s/g, '');
        $('#edit-payment-information-add-payment-method-payment-details-number').val(str);
      });
    }
  };
})(jQuery, Drupal, drupalSettings);
