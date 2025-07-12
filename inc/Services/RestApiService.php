<?php

declare(strict_types=1);

namespace BuiltNorth\Schema\Services;

/**
 * REST API Service
 * 
 * Handles REST API functionality extracted from SchemaGenerator
 */
class RestApiService
{
    /**
     * Register REST API routes for schema generation
     *
     * @return void
     */
    public function registerRoutes(): void
    {
        register_rest_route('wp-schema/v1', '/generate', [
            'methods' => 'POST',
            'callback' => [$this, 'generateSchema'],
            'permission_callback' => function() {
                return current_user_can('edit_posts');
            }
        ]);

        register_rest_route('wp-schema/v1', '/post/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'getPostSchema'],
            'permission_callback' => '__return_true'
        ]);
    }

    /**
     * REST API callback for schema generation
     *
     * @param \WP_REST_Request $request Request object
     * @return \WP_REST_Response Response object
     */
    public function generateSchema($request)
    {
        $content = $request->get_param('content');
        $type = $request->get_param('type') ?: 'Article';
        $options = $request->get_param('options') ?: [];

        $schemaService = new SchemaService();
        $schema = $schemaService->render($content, $type, $options);

        return new \WP_REST_Response($schema, 200);
    }

    /**
     * REST API callback for getting post schema
     *
     * @param \WP_REST_Request $request Request object
     * @return \WP_REST_Response Response object
     */
    public function getPostSchema($request)
    {
        $post_id = $request->get_param('id');
        $options = $request->get_param('options') ?: [];

        $schemaService = new SchemaService();
        $schema = $schemaService->renderForPost($post_id, $options);

        return new \WP_REST_Response($schema, 200);
    }

    /**
     * Add schema to REST API responses
     *
     * @return void
     */
    public function addSchemaToRest(): void
    {
        add_action('rest_prepare_post', [$this, 'addSchemaToPostResponse'], 10, 3);
        add_action('rest_prepare_page', [$this, 'addSchemaToPostResponse'], 10, 3);
    }

    /**
     * Add schema to post REST API response
     *
     * @param \WP_REST_Response $response Response object
     * @param \WP_Post $post Post object
     * @param \WP_REST_Request $request Request object
     * @return \WP_REST_Response Modified response
     */
    public function addSchemaToPostResponse($response, $post, $request)
    {
        $schemaService = new SchemaService();
        $schema = $schemaService->renderForPost($post->ID);
        $response->data['schema'] = $schema;
        
        return $response;
    }
} 