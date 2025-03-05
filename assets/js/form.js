/* global addEventListener, Drupal, fetch */

import '../css/form.css'
import 'dawa-autocomplete2/css/dawa-autocomplete2.css'

const dawaAutocomplete = require('dawa-autocomplete2')

const buildDawaAutocompleteElements = (context) => {
  context
    .querySelectorAll('.field--type-pretix-date .js-dawa-element')
    .forEach(address => {
      // Check if dawa autocomplete has already been initialized.
      if (address.closest('.dawa-autocomplete-container')) {
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
  document
    .querySelectorAll('.pretix-date-widget.hide-end-date')
    .forEach(el => {
      const startDate = el.querySelector('input[name*="[time_from_value][date]"]')
      const endDate = el.querySelector('input[name*="[time_to_value][date]"]')

      startDate.addEventListener('change', () => {
        if (el.classList.contains('end-date-hidden')) {
          endDate.value = startDate.value
        }
      })

      el.classList.add('end-date-hidden')
    })
}

addEventListener('load', () => {
  Drupal.behaviors.itk_pretix = {
    attach: (context, settings) => {
      buildDawaAutocompleteElements(context)
      buildDateControls(context)
    }
  }

  buildDawaAutocompleteElements(document)
  buildDateControls(document)
})
