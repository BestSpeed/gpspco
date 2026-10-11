<?php
/**
 * قالب اختصاصی تک‌مطلب راهنما و سوالات متداول.
 *
 * @package mojrian-club
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>
<div class="mjc-wrap mjc-guide-article">
	<?php
	while ( have_posts() ) :
		the_post();
		?>
		<article <?php post_class( 'mjc-card' ); ?>>
			<nav class="mjc-breadcrumb">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>">خانه</a>
				<span>/</span>
				<a href="<?php echo esc_url( mjc_page_url( 'guide' ) ); ?>">راهنمای باشگاه</a>
				<span>/</span>
				<span><?php the_title(); ?></span>
			</nav>
			<h1><?php the_title(); ?></h1>
			<div class="mjc-guide-content">
				<?php the_content(); ?>
			</div>
			<?php
			$cats = get_the_terms( get_the_ID(), 'mjc_guide_cat' );
			if ( $cats && ! is_wp_error( $cats ) ) {
				echo '<div class="mjc-guide-tags">';
				foreach ( $cats as $cat ) {
					echo '<a class="mjc-badge mjc-badge-approved" href="' . esc_url( add_query_arg( 'gcat', $cat->slug, mjc_page_url( 'guide' ) ) ) . '">' . esc_html( $cat->name ) . '</a> ';
				}
				echo '</div>';
			}
			?>
			<div class="mjc-guide-helpful">
				<p>آیا این مطلب مفید بود؟</p>
				<button type="button" class="mjc-btn mjc-btn-soft mjc-feedback" data-vote="yes">بله</button>
				<button type="button" class="mjc-btn mjc-btn-soft mjc-feedback" data-vote="no">خیر</button>
			</div>
		</article>
		<?php
	endwhile;
	?>
</div>
<?php
get_footer();
