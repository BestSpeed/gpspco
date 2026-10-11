<?php
/**
 * ویجت المنتور: حساب کاربری باشگاه مجریان.
 *
 * @package mojrian-club
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ویجت حساب کاربری باشگاه.
 *
 * رفتار: کاربر مهمان → فرم ورود/ثبت‌نام؛ کاربر واردشده → حساب کاربری باشگاه (لینک بخش‌ها + امتیاز).
 */
class MJC_Elementor_Widget_Account extends \Elementor\Widget_Base {

	/**
	 * نام فنی ویجت.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'mojrian-club-account';
	}

	/**
	 * عنوان در پنل المنتور.
	 *
	 * @return string
	 */
	public function get_title() {
		return 'باشگاه مجریان — حساب کاربری';
	}

	/**
	 * آیکن.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-user-circle';
	}

	/**
	 * دسته‌ی ویجت.
	 *
	 * @return array
	 */
	public function get_categories() {
		return array( 'mojrian-club', 'general' );
	}

	/**
	 * کلمات کلیدی جست‌وجو در پنل.
	 *
	 * @return array
	 */
	public function get_keywords() {
		return array( 'باشگاه', 'مجریان', 'عضویت', 'ورود', 'ثبت نام', 'امتیاز', 'mojrian', 'club', 'account' );
	}

	/**
	 * کنترل‌های پنل.
	 */
	protected function register_controls() {
		$this->start_controls_section(
			'section_content',
			array( 'label' => 'تنظیمات حساب کاربری' )
		);

		$this->add_control(
			'title',
			array(
				'label'       => 'عنوان بالای ویجت',
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => 'حساب کاربری باشگاه مجریان',
				'placeholder' => 'حساب کاربری باشگاه مجریان',
			)
		);

		$this->add_control(
			'show_title',
			array(
				'label'   => 'نمایش عنوان',
				'type'    => \Elementor\Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);

		$this->add_control(
			'guest_note',
			array(
				'label'       => 'متن راهنمای کاربر مهمان',
				'type'        => \Elementor\Controls_Manager::TEXTAREA,
				'default'     => 'برای ورود به حساب کاربری باشگاه، وارد شوید یا ثبت‌نام کنید.',
				'rows'        => 3,
			)
		);

		$this->add_control(
			'show_guide_link',
			array(
				'label'   => 'نمایش لینک «راهنمای باشگاه»',
				'type'    => \Elementor\Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);

		$this->end_controls_section();
	}

	/**
	 * خروجی در فرانت‌اند.
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();

		echo '<div class="mjc-elementor-account">'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		if ( 'yes' === $settings['show_title'] && ! empty( $settings['title'] ) ) {
			echo '<h2 class="mjc-elementor-title">' . esc_html( $settings['title'] ) . '</h2>';
		}
		if ( ! empty( $settings['guest_note'] ) && ! is_user_logged_in() ) {
			echo '<p class="mjc-elementor-note">' . esc_html( $settings['guest_note'] ) . '</p>';
		}

		echo MJC_Shortcodes::render_account(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML داخلی شورت‌کد.

		if ( 'yes' === $settings['show_guide_link'] ) {
			echo '<p class="mjc-center"><a class="mjc-guide-cta" href="' . esc_url( mjc_page_url( 'guide' ) ) . '">📖 راهنمای باشگاه — آموزش کار با باشگاه مجریان</a></p>';
		}
		echo '</div>';
	}

	/**
	 * خروجی در ویرایشگر (پیش‌نمایش).
	 */
	protected function content_template() {
		?>
		<div class="mjc-elementor-account">
			<# if ( 'yes' === settings.show_title && settings.title ) { #>
				<h2 class="mjc-elementor-title">{{ settings.title }}</h2>
			<# } #>
			<div class="mjc-empty">حساب کاربری باشگاه مجریان — پس از انتشار صفحه، اینجا فرم ورود/ثبت‌نام (برای مهمان) یا حساب کاربری (برای اعضا) نمایش داده می‌شود.</div>
			<# if ( 'yes' === settings.show_guide_link ) { #>
				<p class="mjc-center"><span class="mjc-guide-cta">📖 راهنمای باشگاه</span></p>
			<# } #>
		</div>
		<?php
	}
}
