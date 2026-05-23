<?php

declare(strict_types=1);

namespace BuiltNorth\WPSchema\Providers;

use BuiltNorth\WPSchema\Contracts\SchemaProviderInterface;
use BuiltNorth\WPSchema\Graph\SchemaPiece;
use WP_Post;

/**
 * Offer schema for coupons, deals, and promotions.
 *
 * @since 3.1.0
 */
class OfferProvider implements SchemaProviderInterface
{
	public function can_provide(string $context): bool
	{
		if ($context !== 'singular') {
			return false;
		}

		$post = get_queried_object();

		return $post instanceof WP_Post && $this->resolve_schema_type($post) === 'Offer';
	}

	public function get_pieces(string $context): array
	{
		$post = get_queried_object();
		if (! $post instanceof WP_Post) {
			return [];
		}

		$piece_id = $post->post_type . '-' . $post->ID;
		$offer    = new SchemaPiece($piece_id, 'Offer');

		$offer
			->set('name', $post->post_title)
			->set('url', get_permalink($post->ID));

		$description = apply_filters('wp_schema_framework_post_description', '', $post->ID, $post);
		if ($description !== '') {
			$offer->set('description', $description);
		} elseif ($post->post_excerpt !== '') {
			$offer->set('description', wp_strip_all_tags($post->post_excerpt));
		}

		$thumb_id = get_post_thumbnail_id($post->ID);
		if ($thumb_id) {
			$image_url = wp_get_attachment_image_url($thumb_id, 'full');
			if ($image_url) {
				$offer->set('image', [
					'@type' => 'ImageObject',
					'url'   => $image_url,
				]);
			}
		}

		$org_id = $this->organization_id();
		if ($org_id !== '') {
			$offer->set('seller', [ '@id' => $org_id ]);
		}

		$data = apply_filters('wp_schema_framework_offer_data', $offer->to_array(), $post->ID, $post);
		$offer->from_array($data);

		return [ $offer ];
	}

	public function get_priority(): int
	{
		return 18;
	}

	private function resolve_schema_type(WP_Post $post): string
	{
		$default_type = apply_filters('wp_schema_framework_post_type_mapping', '', $post->post_type);

		return (string) apply_filters(
			'wp_schema_framework_post_type_override',
			$default_type,
			$post->ID,
			$post->post_type,
			$post
		);
	}

	private function organization_id(): string
	{
		$default = trailingslashit(home_url('/')) . '#organization';

		return (string) apply_filters('wp_schema_framework_organization_id', $default);
	}
}
