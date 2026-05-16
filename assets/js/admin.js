/*
 * @name:           Viscribe
 * @description     Uses AI to rename images during upload for SEO-friendly filenames.
 * @author          Kolja Nolte <kolja.nolte@gmail.com>
 * @copyright       2025-2026 (C) Kolja Nolte
 * @see             https://docs.kolja-nolte.com/viscribe/

 * Viscribe - Admin JavaScript
 *
 * Handles the Test Connection and Delete API Key functionalities.
 */

/* global viscribeAdmin */

(function ($) {
  "use strict";

  $(function () {
    const admin = window.viscribeAdmin;
    if (!admin || !admin.ajaxUrl) {
      // Fail fast to avoid runtime errors if localized script data is missing
      return;
    }

    const $doc = $(document);
    const $apiKeyInput = $("#viscribe_api_key");

    // Track if the user has entered a new key (not masked)
    let hasEnteredNewKey = false;
    let originalMaskedValue = $apiKeyInput.val();

    const setButtonState = ($btn, disabled, label) => {
      $btn.prop("disabled", disabled).attr("aria-disabled", disabled ? "true" : "false");

      if (typeof label === "string") {
        const $labelTarget = $btn.find(".viscribe-button-label, span[aria-hidden='true']").last();

        if ($labelTarget.length) {
          $labelTarget.text(label);
        } else {
          $btn.text(label);
        }
      }
    };

    const setResultState = (state, text) => {
      const $badge     = $("#viscribe_test_result");
      const $icon      = $badge.find(".dashicons");
      const $text      = $badge.find(".viscribe-status-badge-text");
      const allStates  = "viscribe-status-badge--idle viscribe-status-badge--testing viscribe-status-badge--success viscribe-status-badge--error";

      $badge.removeClass(allStates).addClass("viscribe-status-badge--" + state);
      $text.text(text);

      // Swap icon: spinner while testing, lightbulb otherwise
      if (state === "testing") {
        $icon.removeClass("dashicons-lightbulb").addClass("dashicons-update");
      } else {
        $icon.removeClass("dashicons-update").addClass("dashicons-lightbulb");
      }
    };

    const getResponseMessage = (response) =>
        response && response.data && typeof response.data.message === "string"
            ? response.data.message
            : "";

    // When user focuses on the input field, select all text for easy overwriting
    $apiKeyInput.on("focus", function () {
      originalMaskedValue = $(this).val();
      // Select all text for easy overwriting
      $(this).select();
    });

    // Hide inline error when user types
    const clearErrorInTab = () => {
      $("#viscribe-api-key-error-msg").hide().text("");
    };

    const showErrorInTab = (message) => {
      $("#viscribe-api-key-error-msg").text(message).show();
    };

    const updateApiKeyDescription = (message) => {
      $("#viscribe_api_key_desc").text(message);
    };

    // When user starts typing in the input field, clear the masked value
    $apiKeyInput.on("input", function () {
      clearErrorInTab();
      const currentValue = $(this).val();

      // If the value was masked and user started typing, clear it
      if (originalMaskedValue && originalMaskedValue.includes("•") &&
          currentValue !== originalMaskedValue &&
          !currentValue.includes("•")) {
        // User is typing a new key, clear the field completely
        $(this).val(currentValue);
        hasEnteredNewKey = true;
      } else if (currentValue.includes("•")) {
        // Still contains masked characters
        hasEnteredNewKey = false;
      } else {
        // New key being entered
        hasEnteredNewKey = true;
      }
    });

    // --- Pre-save Validation ---
    const $form = $("#viscribe-settings-form");
    const usingConstant = !!admin.usingApiKeyConstant;


    $form.on("submit", function (e) {
      clearErrorInTab();

      if (!usingConstant && hasEnteredNewKey) {
        const val = $apiKeyInput.val().trim();

        // Only validate format/length if user has entered something new
        if (val !== "") {
          if (!val.startsWith("gsk_")) {
            e.preventDefault();
            showErrorInTab(admin.strings.error_prefix || "Invalid API key format. Groq API keys start with gsk_");
            $apiKeyInput.focus();
            return;
          }
          if (val.length !== 56) {
            e.preventDefault();
            showErrorInTab(admin.strings.error_length || "The API key has an invalid length. It must be exactly 56 characters long.");
            $apiKeyInput.focus();
            return;
          }
        }
      }
    });

    // --- Test Connection Handler ---
    const $testBtn = $("#viscribe_test_connection");

    $testBtn.on("click", (e) => {
      e.preventDefault();

      // Disable button and start spinner on the status badge
      $testBtn.prop("disabled", true);
      setResultState("testing", admin.strings.testing || "Testing…");

      // Build AJAX data. When using constant, don't send API key from input.
      const data = {
        action: "viscribe_test_connection",
        nonce:  admin.nonces.test_connection,
      };

      if (!usingConstant) {
        data.api_key    = String($apiKeyInput.val() ?? "");
        data.is_new_key = hasEnteredNewKey ? 1 : 0;
      }

      // Enforce a minimum 1-second spinner display before showing result
      const startTime = Date.now();
      const MIN_SPIN_MS = 1000;

      $.ajax({
        url:    admin.ajaxUrl,
        method: "POST",
        data:   data,
      })
          .done((response) => {
            const msg   = getResponseMessage(response);
            const delay = Math.max(0, MIN_SPIN_MS - (Date.now() - startTime));
            setTimeout(() => {
              if (response && response.success) {
                setResultState("success", msg || admin.strings.success);
              } else {
                setResultState("error", `${admin.strings.error} ${msg}`.trim());
              }
              $testBtn.prop("disabled", false);
            }, delay);
          })
          .fail((_xhr, _status, errorThrown) => {
            const delay = Math.max(0, MIN_SPIN_MS - (Date.now() - startTime));
            setTimeout(() => {
              setResultState("error", `${admin.strings.error} ${errorThrown || ""}`.trim());
              $testBtn.prop("disabled", false);
            }, delay);
          });
    });

    // --- Delete API Key Handler ---
    $doc.on("click", "#viscribe_delete_api_key", function (e) {
      e.preventDefault();

      if (
          !window.confirm(
              admin.strings.delete_confirm
          )
      ) {
        return;
      }

      const $delBtn = $(this);
      setButtonState($delBtn, true, admin.strings.deleting);

      // Instantly clear the input field as requested
      $apiKeyInput.val("");
      hasEnteredNewKey = false;
      originalMaskedValue = "";

      $.ajax({
        url:    admin.ajaxUrl,
        method: "POST",
        data:   {
          action: "viscribe_delete_api_key",
          nonce:  admin.nonces.delete_api_key,
        },
      })
          .done((response) => {
            if (response && response.success) {
              clearErrorInTab();
              updateApiKeyDescription(admin.strings.enter_key);
              setResultState("idle", admin.strings.no_key || "Not tested");
            } else {
              window.alert(getResponseMessage(response));
            }
          })
          .fail((_xhr, _status, errorThrown) => {
            window.alert(`${admin.strings.request_failed} ${errorThrown || ""}`.trim());
          })
          .always(() => {
            setButtonState($delBtn, false, admin.strings.delete_key_button);
          });
    });

    // --- Encryption Notice Dismiss Handler ---
    $doc.on("click", ".viscribe-encryption-notice .notice-dismiss", function () {
      const $notice = $(this).closest(".viscribe-encryption-notice");
      const nonce = String($notice.data(".viscribe-dismiss-nonce") ?? "");

      if (!nonce) {
        return;
      }

      $.ajax({
        url: admin.ajaxUrl,
        method: "POST",
        data: {
          action: "viscribe_dismiss_encryption_notice",
          nonce,
        },
      });
    });

    // --- Model Selector Handler ---
    const updateModelCards = () => {
      const $cards = $(".viscribe-model-card");
      $cards.removeClass("selected");
      $cards.find("input:checked").closest(".viscribe-model-card").addClass("selected");
    };

    updateModelCards();

    $doc.on("change", ".viscribe-model-card input", updateModelCards);

  });
})(jQuery);
