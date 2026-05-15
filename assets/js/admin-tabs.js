/*
 * @name:           Viscribe
 * @description     Uses AI to rename images during upload for SEO-friendly filenames.
 * @author          Kolja Nolte <kolja.nolte@gmail.com>
 * @copyright       2025-2026 (C) Kolja Nolte
 * @see             https://docs.kolja-nolte.com/viscribe/

 * Viscribe - Tab Navigation
 *
 * Handles tab switching for the modern settings page.
 *
 * @package Viscribe
 */

(function ($) {
  "use strict";

  const ViscribeTabs = {
    /**
     * Initialize tab functionality.
     */
    init: function () {
      this.bindEvents();
      this.handleHashNavigation();
    },

    /**
     * Bind click events to tabs.
     */
    bindEvents: function () {
      $(document).on("click", ".viscribe-tab", this.switchTab.bind(this));
    },

    /**
     * Switch to the clicked tab.
     *
     * @param {Event} e Click event.
     */
    switchTab: function (e) {
      const $tab = $(e.currentTarget);
      const tabId = $tab.data("tab");

      if (!tabId) {
        return;
      }

      e.preventDefault();

      // Update active tab
      $(".viscribe-tab").removeClass("active").attr("aria-selected", "false");
      $tab.addClass("active").attr("aria-selected", "true");

      // Update active panel
      $(".viscribe-panel").removeClass("active").attr("hidden", true);
      $("#viscribe-panel-" + tabId)
          .addClass("active")
          .removeAttr("hidden");

      // Update URL hash
      if (history.pushState) {
        history.pushState(null, null, "#" + tabId);
      } else {
        window.location.hash = tabId;
      }

      // Update _wp_http_referer to include the hash so redirects return to this tab
      const $referer = $('input[name="_wp_http_referer"]');
      if ($referer.length) {
        let refererVal = $referer.val();
        // Remove existing hash if present
        const hashIndex = refererVal.indexOf("#");
        if (hashIndex !== -1) {
          refererVal = refererVal.substring(0, hashIndex);
        }
        $referer.val(refererVal + "#" + tabId);
      }
    },

    /**
     * Handle hash navigation for direct links.
     */
    handleHashNavigation: function () {
      const hash = window.location.hash.substring(1);

      if (hash && $(`.viscribe-tab[data-tab="${hash}"]`).length) {
        $('.viscribe-tab[data-tab="' + hash + '"]').trigger("click");
      }
    },
  };

  // Initialize on DOM ready
  $(document).ready(function () {
    ViscribeTabs.init();
  });
})(jQuery);
