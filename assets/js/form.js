/* global addEventListener, Drupal */

import '../css/form.css'
import '../vendor/adressevaelger/adressevaelger.css'
import { adressevaelger } from '../vendor/adressevaelger/adressevaelger.esm.js'
import proj4 from 'proj4'

proj4.defs('EPSG:25832', '+proj=utm +zone=32 +ellps=GRS80 +towgs84=0,0,0,0,0,0,0 +units=m +no_defs')

const buildAddressAutocomplete = (context) => {
  const token = window.drupalSettings?.itk_pretix?.adressevaelger_token
  context
    .querySelectorAll('.field--type-pretix-date .js-adressevaelger-element')
    .forEach(address => {
      if (address.closest('.autocomplete-container')) {
        return
      }

      const wrapper = document.createElement('div')
      wrapper.setAttribute('class', 'autocomplete-container')
      address.parentNode.replaceChild(wrapper, address)
      wrapper.appendChild(address)

      const fieldWrapper = address.closest('.field--type-pretix-date')
      const latField = fieldWrapper.querySelector('.js-geo-lat')
      const lngField = fieldWrapper.querySelector('.js-geo-lng')

      adressevaelger(address, {
        select: function (selected) {
          const coords = selected?.adresse?.husnummer?.adgangspunkt?.koordinater
          if (coords && latField && lngField) {
            const [lng, lat] = proj4('EPSG:25832', 'WGS84', [coords.x, coords.y])
            latField.value = lat
            lngField.value = lng
          }
        },
        token
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
      buildAddressAutocomplete(context)
      buildDateControls(context)
    }
  }

  buildAddressAutocomplete(document)
  buildDateControls(document)
})
