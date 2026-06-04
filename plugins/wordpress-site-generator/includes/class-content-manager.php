<?php

class Ai_Site_Gen_Content_Manager {

	public function create_pages( $site_data ) {
		if ( ! isset( $site_data['pages'] ) || ! is_array( $site_data['pages'] ) ) {
			return array();
		}

		$created = array();

		// Set Site Title if provided
		if ( ! empty( $site_data['siteTitle'] ) ) {
			update_option( 'blogname', sanitize_text_field( $site_data['siteTitle'] ) );
		}

		foreach ( $site_data['pages'] as $page ) {
			$title = sanitize_text_field( $page['title'] );
			$slug  = sanitize_title( $page['slug'] );
			
			// Build content from sections
			$content = '';
			if ( isset($page['sections']) && is_array($page['sections']) ) {
				foreach ( $page['sections'] as $section ) {
					$content .= $this->assemble_section_markup( $section ) . "\n\n";
				}
			} else {
				$content = '<!-- wp:paragraph --><p>Content generation pending.</p><!-- /wp:paragraph -->';
			}

			// Check if exists
			$existing = get_page_by_path( $slug );
			if ( $existing ) {
				$post_id = $existing->ID;
				wp_update_post( array(
					'ID'           => $post_id,
					'post_content' => $content
				) );
			} else {
				$post_id = wp_insert_post( array(
					'post_title'   => $title,
					'post_name'    => $slug,
					'post_content' => $content,
					'post_status'  => 'publish',
					'post_type'    => 'page'
				) );
			}

			if ( ! is_wp_error( $post_id ) ) {
				$created[] = array(
					'id'    => $post_id,
					'title' => $title,
					'link'  => get_permalink( $post_id )
				);

				// Set Home if slug is home
				if ( $slug === 'home' ) {
					update_option( 'show_on_front', 'page' );
					update_option( 'page_on_front', $post_id );
				}
			}
		}

		return $created;
	}

	/**
	 * Assemble block markup with injected content.
	 * Uses styling classes compatible with Ollie Theme.
	 */
	private function assemble_section_markup( $section ) {
		$type = isset($section['type']) ? strtolower( trim( $section['type'] ) ) : 'text';

		switch ( $type ) {
			case 'hero':
				$headline = esc_html($section['headline'] ?? 'Welcome');
				$sub = esc_html($section['subheadline'] ?? '');
				$btn = esc_html($section['buttonText'] ?? 'Get Started');
				
				return sprintf('<!-- wp:cover {"overlayColor":"contrast","align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|80","bottom":"var:preset|spacing|80"}}}} -->
				<div class="wp-block-cover alignfull has-contrast-background-color has-background-dim" style="padding-top:var(--wp--preset--spacing--80);padding-bottom:var(--wp--preset--spacing--80)"><div class="wp-block-cover__inner-container">
				<!-- wp:heading {"textAlign":"center","level":1,"style":{"typography":{"fontSize":"4.5rem","lineHeight":"1.1"}}} --><h1 class="wp-block-heading has-text-align-center" style="font-size:4.5rem;line-height:1.1">%s</h1><!-- /wp:heading -->
				<!-- wp:paragraph {"align":"center","fontSize":"medium"} --><p class="has-text-align-center has-medium-font-size">%s</p><!-- /wp:paragraph -->
				<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} --><div class="wp-block-buttons"><!-- wp:button {"className":"is-style-fill"} --><div class="wp-block-button is-style-fill"><a class="wp-block-button__link wp-element-button">%s</a></div><!-- /wp:button --></div><!-- /wp:buttons -->
				</div></div><!-- /wp:cover -->', $headline, $sub, $btn);
			
			case 'features':
				$headline = esc_html($section['headline'] ?? 'Our Services');
				$items_html = '';
				if (isset($section['items']) && is_array($section['items'])) {
					foreach ($section['items'] as $item) {
						$items_html .= sprintf('<!-- wp:column -->
						<div class="wp-block-column"><!-- wp:heading {"level":3} --><h3 class="wp-block-heading">%s</h3><!-- /wp:heading --><!-- wp:paragraph --><p>%s</p><!-- /wp:paragraph --></div><!-- /wp:column -->', 
						esc_html($item['title'] ?? ''), esc_html($item['description'] ?? ''));
					}
				}

				return sprintf('<!-- wp:group {"align":"wide","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60"}}},"layout":{"type":"constrained"}} -->
				<div class="wp-block-group alignwide" style="padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60)">
				<!-- wp:heading {"textAlign":"center","style":{"spacing":{"margin":{"bottom":"var:preset|spacing|50"}}}} --><h2 class="wp-block-heading has-text-align-center" style="margin-bottom:var(--wp--preset--spacing--50)">%s</h2><!-- /wp:heading -->
				<!-- wp:columns {"align":"wide"} -->
				<div class="wp-block-columns alignwide">%s</div><!-- /wp:columns --></div><!-- /wp:group -->', $headline, $items_html);
			
			case 'about':
				$headline = esc_html($section['headline'] ?? 'About Us');
				$text = wp_kses_post($section['content'] ?? '');
				
				return sprintf('<!-- wp:media-text {"mediaPosition":"right","align":"wide","mediaType":"image"} -->
				<div class="wp-block-media-text alignwide has-media-on-the-right is-stacked-on-mobile">
				<div class="wp-block-media-text__content">
				<!-- wp:heading --><h2>%s</h2><!-- /wp:heading -->
				<!-- wp:paragraph --><p>%s</p><!-- /wp:paragraph -->
				</div>
				<figure class="wp-block-media-text__media"><img src="https://images.pexels.com/photos/3184291/pexels-photo-3184291.jpeg" alt="About"/></figure>
				</div><!-- /wp:media-text -->', $headline, $text);
			
			case 'testimonials':
				$headline = esc_html($section['headline'] ?? 'Testimonials');
				$quotes_html = '';
				if (isset($section['quotes']) && is_array($section['quotes'])) {
					foreach ($section['quotes'] as $q) {
						$quotes_html .= sprintf('<!-- wp:column -->
						<div class="wp-block-column"><!-- wp:quote --><blockquote class="wp-block-quote"><p>"%s"</p><cite>%s</cite></blockquote><!-- /wp:quote --></div><!-- /wp:column -->', 
						esc_html($q['text'] ?? ''), esc_html($q['author'] ?? ''));
					}
				}

				return sprintf('<!-- wp:group {"align":"full","backgroundColor":"subtle-background","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60"}}},"layout":{"type":"constrained"}} -->
				<div class="wp-block-group alignfull has-subtle-background-background-color has-background" style="padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60)">
				<!-- wp:heading {"textAlign":"center"} --><h2 class="wp-block-heading has-text-align-center">%s</h2><!-- /wp:heading -->
				<!-- wp:columns {"align":"wide"} -->
				<div class="wp-block-columns alignwide">%s</div><!-- /wp:columns --></div><!-- /wp:group -->', $headline, $quotes_html);
				
			case 'cta':
				$headline = esc_html($section['headline'] ?? 'Ready to start?');
				$btn = esc_html($section['buttonText'] ?? 'Contact Us');

				return sprintf('<!-- wp:group {"align":"wide","backgroundColor":"primary","textColor":"background","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60"},"margin":{"top":"var:preset|spacing|60"}}},"layout":{"type":"constrained"}} -->
				<div class="wp-block-group alignwide has-background-color has-primary-background-color has-text-color has-background" style="margin-top:var(--wp--preset--spacing--60);padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60)"><div class="wp-block-group__inner-container">
				<!-- wp:heading {"textAlign":"center","style":{"typography":{"fontSize":"3rem"}}} --><h2 class="wp-block-heading has-text-align-center" style="font-size:3rem">%s</h2><!-- /wp:heading -->
				<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} --><div class="wp-block-buttons"><!-- wp:button {"backgroundColor":"background","textColor":"primary"} --><div class="wp-block-button"><a class="wp-block-button__link has-primary-color has-background-background-color has-text-color has-background wp-element-button">%s</a></div><!-- /wp:button --></div><!-- /wp:buttons -->
				</div></div><!-- /wp:group -->', $headline, $btn);

			case 'contact-form':
				$headline = esc_html($section['headline'] ?? 'Contact Us');
				$email = esc_html($section['email'] ?? 'hello@example.com');
				$phone = esc_html($section['phone'] ?? '');

				return sprintf('<!-- wp:group {"align":"wide","layout":{"type":"constrained"}} -->
				<div class="wp-block-group alignwide">
				<!-- wp:heading --><h2>%s</h2><!-- /wp:heading -->
				<!-- wp:paragraph --><p>Reach out to us via email or phone. We would love to hear from you!</p><!-- /wp:paragraph -->
				<!-- wp:paragraph --><p><strong>Email:</strong> %s<br><strong>Phone:</strong> %s</p><!-- /wp:paragraph -->
				</div><!-- /wp:group -->', $headline, $email, $phone);

			default:
				return '<!-- wp:paragraph --><p>Section: ' . esc_html( $type ) . '</p><!-- /wp:paragraph -->';
		}
	}
}
