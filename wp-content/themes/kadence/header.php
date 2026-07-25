<?php
/**
 * The header for our theme
 *
 * This is the template that displays all of the <head> section and everything up until <div id="content">
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package kadence
 */

namespace Kadence;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<!doctype html>
<html <?php language_attributes(); ?> class="no-js" <?php kadence()->print_microdata( 'html' ); ?>>
<head>
	<meta name="google-site-verification" content="43s_29wM78f2xanZbr83SZH-jfV9ORD51bMFCmrI-MQ" />
	<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-2534445714164991"
     crossorigin="anonymous"></script>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1">
	<?php wp_head(); ?>
	<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-2534445714164991"
     crossorigin="anonymous"></script>
<!-- Header -->
<ins class="adsbygoogle"
     style="display:block"
     data-ad-client="ca-pub-2534445714164991"
     data-ad-slot="8662770872"
     data-ad-format="auto"
     data-full-width-responsive="true"></ins>
<script>
     (adsbygoogle = window.adsbygoogle || []).push({});
</script>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<?php
/**
 * Kadence before wrapper hook.
 */
do_action( 'kadence_before_wrapper' );
?>
<div id="wrapper" class="site wp-site-blocks">
	<?php
	/**
	 * Kadence before header hook.
	 *
	 * @hooked kadence_do_skip_to_content_link - 2
	 */
	do_action( 'kadence_before_header' );

	/**
	 * Kadence header hook.
	 *
	 * @hooked Kadence/header_markup - 10
	 */
	do_action( 'kadence_header' );

	do_action( 'kadence_after_header' );
	?>

	<main id="inner-wrap" class="wrap kt-clear" role="main">
		<?php
		/**
		 * Hook for top of inner wrap.
		 */
		do_action( 'kadence_before_content' );
		?>
