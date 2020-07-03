<?php
	/**
	 * Plugin Name: Generate News Sitemap
	 * Plugin URI:  https://github.com/HelaGone/GenerateNewsSitemap
	 * Description: Generate xml file for google news sitemap
	 * Version:     1.0.0
	 * Author:      Holkan Luna
	 * Author URI:  https://cubeinthebox.com/
	 * License:     GPL2
	 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
	 * Text Domain: xml-file
	 * Domain Path: /languages
	 */

	function gns_activation_fn(){

	}
	register_activation_hook( __FILE__, 'gns_activation_fn' );


	function gns_deactivation_fn(){
		if(is_file('../news_sitemap.xml')){
			unlink('../news_sitemap.xml');
		}
	}
	register_deactivation_hook( __FILE__, 'gns_deactivation_fn' );

	function gns_get_sitemap_posts($post_id, $post){

		$today = date('r');
		$antier = date('F j, Y', strtotime('-2 days', strtotime($today)));

		if('breaking'===get_post_type($post_id)){
			$args = array(
				'post_type'=>'breaking',
				'post_status'=>'publish',
				'orderby'=>'date',
				'order'=>'DESC',
				'date_query'=>array(
					array(
						'after'=>'2 days ago',
						'inclusive'=>true
					)
				),
				'posts_per_page'=>1000,
			);
			$news = get_posts($args);
			gns_generate_xml_file($news);
		}
	}

	add_action('publish_breaking', 'gns_get_sitemap_posts', 10, 2);

	function gns_generate_xml_file($posts_object){
		date_default_timezone_set('America/Mexico_City');
		$xml = new DOMDocument('1.0', 'UTF-8');
		$urlset = $xml->createElement('urlset');
		$urlset->setAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');
		$urlset->setAttribute('xmlns:news', 'http://www.google.com/schemas/sitemap-news/0.9');
		$xml->appendChild($urlset);

		foreach ($posts_object as $p_object) {
			$post_permalink = get_the_permalink($p_object->ID);
			$post_thumbnail_url = get_the_post_thumbnail_url($p_object->ID);

			$post_publish_date = get_the_date('Y-m-d\TH:i:s-05:00', $p_object->ID);
			$post_date_mod = get_the_modified_date('Y-m-d\TH:i:s-05:00', $p_object->ID);
			$metakeywords = get_post_meta($p_object->ID, '_meta_keywords', true);

			$url = $xml->createElement('url');
			$url->appendChild($xml->createElement('loc', $post_permalink));
			$url->appendChild($xml->createElement('lastmod', $post_publish_date));
			$url->appendChild($xml->createElement('changefreq', 'hourly'));
			$url->appendChild($xml->createElement('priority', '0.9'));
			$news_node = $xml->createElement('news:news');

			$news_publication = $xml->createElement('news:publication');
			$news_publication->appendChild($xml->createElement('news:name', 'Noticieros Televisa'));
			$news_publication->appendChild($xml->createElement('news:language', 'es'));
			$news_node->appendChild($news_publication);

			$news_node->appendChild($xml->createElement('news:genres', 'Blog'));
			$news_node->appendChild($xml->createElement('news:publication_date', $post_publish_date));

			$news_title = $xml->createElement('news:title');
			$news_title->appendChild($xml->createCDATASection(esc_html($p_object->post_title)));
			$news_node->appendChild($news_title);

			$news_node->appendChild($xml->createElement('news:keywords', htmlspecialchars($metakeywords)));

			$url->appendChild($news_node);
			$urlset->appendChild($url);
		}
		$xml->save('../news_sitemap.xml');
	}

?>
