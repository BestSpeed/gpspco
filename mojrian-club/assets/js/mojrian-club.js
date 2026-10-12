/**
 * اسکریپت‌های عمومی باشگاه مجریان
 */
(function () {
	'use strict';

	// دکمه‌ی «آیا مفید بود؟» در راهنما.
	function initFeedback() {
		var buttons = document.querySelectorAll('.mjc-feedback');
		buttons.forEach(function (btn) {
			btn.addEventListener('click', function () {
				var wrap = btn.closest('.mjc-guide-helpful');
				if (wrap) {
					wrap.innerHTML = '<p class="mjc-text-sm">از بازخورد شما سپاسگزاریم 🌱</p>';
				}
			});
		});
	}

	// جلوگیری از ارسال فرم‌های خالی.
	function initForms() {
		document.querySelectorAll('.mjc-form').forEach(function (form) {
			form.addEventListener('submit', function (e) {
				var ok = true;
				form.querySelectorAll('[required]').forEach(function (field) {
					if (!field.value || (field.type === 'checkbox' && !field.checked)) {
						ok = false;
						field.style.borderColor = '#dc2626';
						setTimeout(function () {
							field.style.borderColor = '';
						}, 2500);
					}
				});
				if (!ok) {
					e.preventDefault();
					alert('لطفاً فیلدهای الزامی را تکمیل کنید.');
				}
			});
		});
	}

	document.addEventListener('DOMContentLoaded', function () {
		initFeedback();
		initForms();
	});
})();
