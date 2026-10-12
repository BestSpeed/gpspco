<?php
/**
 * قالب اختصاصی صفحه‌ی کمپین (تک‌کمپین).
 *
 * @package mojrian-club
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>
<div class="mjc-wrap">
	<?php
	while ( have_posts() ) :
		the_post();
		$campaign_id = get_the_ID();
		$active      = MJC_Campaigns::is_active( $campaign_id );
		$winners     = MJC_Campaigns::winners( $campaign_id );
		?>
		<article <?php post_class( 'mjc-card mjc-campaign' . ( $active ? '' : ' mjc-campaign-closed' ) ); ?>>
			<nav class="mjc-breadcrumb">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>">خانه</a>
				<span>/</span>
				<a href="<?php echo esc_url( mjc_page_url( 'campaigns' ) ); ?>">کمپین‌ها و قرعه‌کشی</a>
				<span>/</span>
				<span><?php the_title(); ?></span>
			</nav>
			<?php
			if ( has_post_thumbnail() ) {
				echo get_the_post_thumbnail( get_the_ID(), 'large', array( 'class' => 'mjc-reward-img' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
			?>
			<h1><?php the_title(); ?></h1>
			<div class="mjc-guide-content">
				<?php the_content(); ?>
			</div>
			<?php
			$end = get_post_meta( $campaign_id, 'mjc_end_date', true );
			if ( $end ) {
				echo '<p class="mjc-text-sm">مهلت: ' . esc_html( mjc_date( $end . ' 00:00:00', false ) ) . '</p>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
			if ( $winners ) {
				echo '<div class="mjc-winners"><strong>برندگان:</strong> '; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				foreach ( $winners as $uid ) {
					$u = get_userdata( $uid );
					echo '<span class="mjc-badge mjc-badge-approved">' . esc_html( $u ? $u->display_name : '—' ) . '</span> ';
				}
				echo '</div>';
			}
			if ( ! is_user_logged_in() ) {
				echo '<p><a class="mjc-btn mjc-btn-primary" href="' . esc_url( mjc_page_url( 'login' ) ) . '">برای شرکت در کمپین وارد شوید</a></p>';
			} elseif ( $active ) {
				echo '<form method="post" class="mjc-form">';
				wp_nonce_field( 'mjc_campaign', 'mjc_nonce', true, false );
				echo '<input type="hidden" name="mjc_form" value="campaign_join" />';
				echo '<input type="hidden" name="campaign_id" value="' . esc_attr( $campaign_id ) . '" />';
				echo '<div class="mjc-actions"><button type="submit" class="mjc-btn mjc-btn-primary">شرکت در کمپین</button></div>';
				echo '</form>';
			} else {
				echo '<span class="mjc-badge mjc-badge-closed">پایان‌یافته</span>';
			}
			?>
		</article>
		<?php
	endwhile;
	?>
</div>
<?php
get_footer();
