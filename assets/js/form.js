/* global fetch, Drupal */

import '../css/form.scss'
import 'dawa-autocomplete2/css/dawa-autocomplete2.css'

const $ = require('jquery')
require('jquery-validation')

// IE 11 does not support Object.assign
require('es6-object-assign/auto')
const dawaAutocomplete = require('dawa-autocomplete2')

const buildDawaAutocompleteElements = (context) => {
  const addresses = Array.from(context.querySelectorAll('.field--type-pretix-date .js-dawa-element'))
  addresses.forEach(address => {
    // Check if dawa autocomplete has already been initialized.
    if ($(address).closest('.dawa-autocomplete-container').length > 0) {
      return
    }

    // Address autocomplete using https://dawa.aws.dk/.
    const addressWrapper = document.createElement('div')
    addressWrapper.setAttribute('class', 'dawa-autocomplete-container')
    address.parentNode.replaceChild(addressWrapper, address)
    addressWrapper.appendChild(address)

    dawaAutocomplete.dawaAutocomplete(address, {
      select: function (selected) {
        fetch(selected.data.href)
          .then(function (response) {
            return response.json()
          })
      }
    })
  })
}

const buildDateControls = (context) => {
  $('.pretix-date-widget.hide-end-date').each(function () {
    const $this = $(this)
    const $startDate = $(this).find('input[name*="[time_from_value][date]"]')
    const $endDate = $(this).find('input[name*="[time_to_value][date]"]')
    $startDate.on('change', function () {
      if ($this.hasClass('end-date-hidden')) {
        $endDate.val($(this).val())
      }
    })
    $this.addClass('end-date-hidden')
  })
}

$(() => {
  Drupal.behaviors.itk_pretix = {
    attach: (context, settings) => {
      buildDawaAutocompleteElements(context)
      buildDateControls(context)
    }
  }

  buildDawaAutocompleteElements(document)
  buildDateControls(document)
})
