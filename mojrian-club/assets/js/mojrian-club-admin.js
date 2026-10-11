/**
 * اسکریپت‌های پنل مدیریت باشگاه
 */
(function () {
	'use strict';

	document.addEventListener('DOMContentLoaded', function () {
		// بستن خودکار نوتیس‌ها پس از چند ثانیه (در صورت وجود).
		var notices = document.querySelectorAll('.notice.is-dismissible');
		notices.forEach(function (notice) {
			setTimeout(function () {
				notice.style.opacity = '0';
				setTimeout(function () {
					notice.remove();
				}, 600);
			}, 6000);
		});
	});
})();
