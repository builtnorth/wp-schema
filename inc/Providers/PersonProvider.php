<?php

declare(strict_types=1);

namespace BuiltNorth\WPSchema\Providers;

use BuiltNorth\WPSchema\Contracts\SchemaProviderInterface;
use BuiltNorth\WPSchema\Graph\SchemaPiece;
use WP_Post;

/**
 * Person schema for team profiles and other singular Person content.
 *
 * @since 3.1.0
 */
class PersonProvider implements SchemaProviderInterface
{
	public function can_provide(string $context): bool
	{
		if ($context !== 'singular') {
			return false;
		}

		$post = get_queried_object();

		return $post instanceof WP_Post && $this->resolve_schema_type($post) === 'Person';
	}

	public function get_pieces(string $context): array
	{
		$post = get_queried_object();
		if (! $post instanceof WP_Post) {
			return [];
		}

		$piece_id = $post->post_type . '-' . $post->ID;
		$person   = new SchemaPiece($piece_id, 'Person');

		$person
			->set('name', $post->post_title)
			->set('url', get_permalink($post->ID));

		$description = apply_filters('wp_schema_framework_post_description', '', $post->ID, $post);
		if ($description !== '') {
			$person->set('description', $description);
		} elseif ($post->post_excerpt !== '') {
			$person->set('description', wp_strip_all_tags($post->post_excerpt));
		}

		$thumb_id = get_post_thumbnail_id($post->ID);
		if ($thumb_id) {
			$image_url = wp_get_attachment_image_url($thumb_id, 'full');
			if ($image_url) {
				$image_data = [
					'@type' => 'ImageObject',
					'url'   => $image_url,
				];
				$metadata = wp_get_attachment_metadata($thumb_id);
				if (! empty($metadata['width'])) {
					$image_data['width'] = $metadata['width'];
				}
				if (! empty($metadata['height'])) {
					$image_data['height'] = $metadata['height'];
				}
				$person->set('image', $image_data);
			}
		}

		$org_id = $this->organization_id();
		if ($org_id !== '') {
			$person->set('worksFor', [ '@id' => $org_id ]);
		}

		$data = apply_filters('wp_schema_framework_person_data', $person->to_array(), $post->ID, $post);
		$person->from_array($data);

		return [ $person ];
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
