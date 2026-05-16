//#region assets/js/admin.js
(function($) {
	"use strict";
	$(function() {
		const admin = window.viscribeAdmin;
		if (!admin || !admin.ajaxUrl) return;
		const $doc = $(document);
		const $apiKeyInput = $("#viscribe_api_key");
		let hasEnteredNewKey = false;
		let originalMaskedValue = $apiKeyInput.val();
		const setButtonState = ($btn, disabled, label) => {
			$btn.prop("disabled", disabled).attr("aria-disabled", disabled ? "true" : "false");
			if (typeof label === "string") {
				const $labelTarget = $btn.find(".viscribe-button-label, span[aria-hidden='true']").last();
				if ($labelTarget.length) $labelTarget.text(label);
				else $btn.text(label);
			}
		};
		const setResultState = (state, text) => {
			const $badge = $("#viscribe_test_result");
			const $icon = $badge.find(".dashicons");
			const $text = $badge.find(".viscribe-status-badge-text");
			$badge.removeClass("viscribe-status-badge--idle viscribe-status-badge--testing viscribe-status-badge--success viscribe-status-badge--error").addClass("viscribe-status-badge--" + state);
			$text.text(text);
			if (state === "testing") $icon.removeClass("dashicons-lightbulb").addClass("dashicons-update");
			else $icon.removeClass("dashicons-update").addClass("dashicons-lightbulb");
		};
		const getResponseMessage = (response) => response && response.data && typeof response.data.message === "string" ? response.data.message : "";
		$apiKeyInput.on("focus", function() {
			originalMaskedValue = $(this).val();
			$(this).select();
		});
		const clearErrorInTab = () => {
			$("#viscribe-api-key-error-msg").hide().text("");
		};
		const showErrorInTab = (message) => {
			$("#viscribe-api-key-error-msg").text(message).show();
		};
		const updateApiKeyDescription = (message) => {
			$("#viscribe_api_key_desc").text(message);
		};
		$apiKeyInput.on("input", function() {
			clearErrorInTab();
			const currentValue = $(this).val();
			if (originalMaskedValue && originalMaskedValue.includes("•") && currentValue !== originalMaskedValue && !currentValue.includes("•")) {
				$(this).val(currentValue);
				hasEnteredNewKey = true;
			} else if (currentValue.includes("•")) hasEnteredNewKey = false;
			else hasEnteredNewKey = true;
		});
		const $form = $("#viscribe-settings-form");
		const usingConstant = !!admin.usingApiKeyConstant;
		$form.on("submit", function(e) {
			clearErrorInTab();
			if (!usingConstant && hasEnteredNewKey) {
				const val = $apiKeyInput.val().trim();
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
		const $testBtn = $("#viscribe_test_connection");
		$testBtn.on("click", (e) => {
			e.preventDefault();
			$testBtn.prop("disabled", true);
			setResultState("testing", admin.strings.testing || "Testing…");
			const data = {
				action: "viscribe_test_connection",
				nonce: admin.nonces.test_connection
			};
			if (!usingConstant) {
				data.api_key = String($apiKeyInput.val() ?? "");
				data.is_new_key = hasEnteredNewKey ? 1 : 0;
			}
			const startTime = Date.now();
			const MIN_SPIN_MS = 1e3;
			$.ajax({
				url: admin.ajaxUrl,
				method: "POST",
				data
			}).done((response) => {
				const msg = getResponseMessage(response);
				const delay = Math.max(0, MIN_SPIN_MS - (Date.now() - startTime));
				setTimeout(() => {
					if (response && response.success) setResultState("success", msg || admin.strings.success);
					else setResultState("error", `${admin.strings.error} ${msg}`.trim());
					$testBtn.prop("disabled", false);
				}, delay);
			}).fail((_xhr, _status, errorThrown) => {
				const delay = Math.max(0, MIN_SPIN_MS - (Date.now() - startTime));
				setTimeout(() => {
					setResultState("error", `${admin.strings.error} ${errorThrown || ""}`.trim());
					$testBtn.prop("disabled", false);
				}, delay);
			});
		});
		$doc.on("click", "#viscribe_delete_api_key", function(e) {
			e.preventDefault();
			if (!window.confirm(admin.strings.delete_confirm)) return;
			const $delBtn = $(this);
			setButtonState($delBtn, true, admin.strings.deleting);
			$apiKeyInput.val("");
			hasEnteredNewKey = false;
			originalMaskedValue = "";
			$.ajax({
				url: admin.ajaxUrl,
				method: "POST",
				data: {
					action: "viscribe_delete_api_key",
					nonce: admin.nonces.delete_api_key
				}
			}).done((response) => {
				if (response && response.success) {
					clearErrorInTab();
					updateApiKeyDescription(admin.strings.enter_key);
					setResultState("idle", admin.strings.no_key || "Not tested");
				} else window.alert(getResponseMessage(response));
			}).fail((_xhr, _status, errorThrown) => {
				window.alert(`${admin.strings.request_failed} ${errorThrown || ""}`.trim());
			}).always(() => {
				setButtonState($delBtn, false, admin.strings.delete_key_button);
			});
		});
		$doc.on("click", ".viscribe-encryption-notice .notice-dismiss", function() {
			const $notice = $(this).closest(".viscribe-encryption-notice");
			const nonce = String($notice.data(".viscribe-dismiss-nonce") ?? "");
			if (!nonce) return;
			$.ajax({
				url: admin.ajaxUrl,
				method: "POST",
				data: {
					action: "viscribe_dismiss_encryption_notice",
					nonce
				}
			});
		});
		const updateModelCards = () => {
			const $cards = $(".viscribe-model-card");
			$cards.removeClass("selected");
			$cards.find("input:checked").closest(".viscribe-model-card").addClass("selected");
		};
		updateModelCards();
		$doc.on("change", ".viscribe-model-card input", updateModelCards);
	});
})(jQuery);
//#endregion
//#region assets/js/admin-tabs.js
(function($) {
	"use strict";
	const ViscribeTabs = {
		/**
		* Initialize tab functionality.
		*/
		init: function() {
			this.bindEvents();
			this.handleHashNavigation();
		},
		/**
		* Bind click events to tabs.
		*/
		bindEvents: function() {
			$(document).on("click", ".viscribe-tab", this.switchTab.bind(this));
		},
		/**
		* Switch to the clicked tab.
		*
		* @param {Event} e Click event.
		*/
		switchTab: function(e) {
			const $tab = $(e.currentTarget);
			const tabId = $tab.data("tab");
			if (!tabId) return;
			e.preventDefault();
			$(".viscribe-tab").removeClass("active").attr("aria-selected", "false");
			$tab.addClass("active").attr("aria-selected", "true");
			$(".viscribe-panel").removeClass("active").attr("hidden", true);
			$("#viscribe-panel-" + tabId).addClass("active").removeAttr("hidden");
			if (history.pushState) history.pushState(null, null, "#" + tabId);
			else window.location.hash = tabId;
			const $referer = $("input[name=\"_wp_http_referer\"]");
			if ($referer.length) {
				let refererVal = $referer.val();
				const hashIndex = refererVal.indexOf("#");
				if (hashIndex !== -1) refererVal = refererVal.substring(0, hashIndex);
				$referer.val(refererVal + "#" + tabId);
			}
		},
		/**
		* Handle hash navigation for direct links.
		*/
		handleHashNavigation: function() {
			const hash = window.location.hash.substring(1);
			if (hash && $(`.viscribe-tab[data-tab="${hash}"]`).length) $(".viscribe-tab[data-tab=\"" + hash + "\"]").trigger("click");
		}
	};
	$(document).ready(function() {
		ViscribeTabs.init();
	});
})(jQuery);
//#endregion

//# sourceMappingURL=scripts.js.map