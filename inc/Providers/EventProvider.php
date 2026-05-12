<?php

declare(strict_types=1);

namespace BuiltNorth\WPSchema\Providers;

use BuiltNorth\WPSchema\Contracts\SchemaProviderInterface;
use BuiltNorth\WPSchema\Graph\SchemaPiece;

/**
 * Event Provider
 * 
 * Provides Event schema with smart detection for popular event plugins.
 * Supports The Events Calendar, Events Manager, Modern Events Calendar, and custom integrations.
 * 
 * @since 3.0.0
 */
class EventProvider implements SchemaProviderInterface
{
    public function can_provide(string $context): bool
    {
        if ($context !== 'singular') {
            return false;
        }
        
        // Auto-detect The Events Calendar (by Modern Tribe)
        if (class_exists('Tribe__Events__Main') && get_post_type() === 'tribe_events') {
            // Check if The Events Calendar is outputting JSON-LD
            if ($this->is_tribe_events_schema_enabled()) {
                return false; // Let The Events Calendar handle it to avoid conflicts
            }
            return true;
        }
        
        // Auto-detect Events Manager
        if (class_exists('EM_Events') && get_post_type() === 'event') {
            return true;
        }
        
        // Auto-detect Modern Events Calendar (MEC)
        if (function_exists('MEC') && get_post_type() === 'mec-events') {
            return true;
        }
        
        // Auto-detect Event Organiser
        if (function_exists('eventorganiser_is_event') && eventorganiser_is_event()) {
            return true;
        }
        
        // Auto-detect All in One Event Calendar
        if (class_exists('Ai1ec_Event') && get_post_type() === 'ai1ec_event') {
            return true;
        }
        
        // Auto-detect GatherPress
        if (class_exists('\GatherPress\Core\Event') && get_post_type() === 'gatherpress_event') {
            return true;
        }
        
        // Allow custom integration via filter
        return apply_filters('wp_schema_framework_is_event', false, get_the_ID(), $context);
    }
    
    public function get_pieces(string $context): array
    {
        $event_data = $this->get_event_data();
        
        if (empty($event_data)) {
            return [];
        }
        
        // Create event schema piece
        $event = new SchemaPiece('#event', $event_data['eventType'] ?? 'Event');
        
        // Set basic event data
        $event->set('name', $event_data['name'] ?? get_the_title());
        
        if (!empty($event_data['description'])) {
            $event->set('description', wp_strip_all_tags($event_data['description']));
        }
        
        // Set event dates (required)
        if (!empty($event_data['startDate'])) {
            $event->set('startDate', $event_data['startDate']);
        }
        
        if (!empty($event_data['endDate'])) {
            $event->set('endDate', $event_data['endDate']);
        }
        
        // Set event status
        if (!empty($event_data['eventStatus'])) {
            $event->set('eventStatus', $event_data['eventStatus']);
        } else {
            // Default to scheduled
            $event->set('eventStatus', 'https://schema.org/EventScheduled');
        }
        
        // Set event attendance mode (in-person, online, mixed)
        if (!empty($event_data['eventAttendanceMode'])) {
            $event->set('eventAttendanceMode', $event_data['eventAttendanceMode']);
        } else {
            // Default to offline
            $event->set('eventAttendanceMode', 'https://schema.org/OfflineEventAttendanceMode');
        }
        
        // Add location (physical or virtual)
        if (!empty($event_data['location'])) {
            $location = $this->build_location($event_data['location']);
            if (!empty($location)) {
                $event->set('location', $location);
            }
        }
        
        // Add organizer
        if (!empty($event_data['organizer'])) {
            $event->set('organizer', $this->build_organizer($event_data['organizer']));
        } else {
            // Default to site organization
            $event->set('organizer', ['@id' => '#organization']);
        }
        
        // Add performer(s)
        if (!empty($event_data['performer'])) {
            $event->set('performer', $this->build_performers($event_data['performer']));
        }
        
        // Add image
        if (!empty($event_data['image'])) {
            $event->set('image', [
                '@type' => 'ImageObject',
                'url' => $event_data['image']
            ]);
        } elseif (has_post_thumbnail()) {
            $event->set('image', [
                '@type' => 'ImageObject',
                'url' => get_the_post_thumbnail_url(null, 'full')
            ]);
        }
        
        // Add offers (tickets)
        if (!empty($event_data['offers'])) {
            $event->set('offers', $this->build_offers($event_data['offers']));
        }
        
        // Add URL
        $event->set('url', $event_data['url'] ?? get_permalink());
        
        // Allow filtering of event data
        $data = apply_filters('wp_schema_framework_event_data', $event->to_array(), $context, get_the_ID());
        $event->from_array($data);
        
        return [$event];
    }
    
    public function get_priority(): int
    {
        return 20; // Same as article/product
    }
    
    /**
     * Get event data from various sources
     */
    private function get_event_data(): array
    {
        // Try custom filter first (highest priority)
        $custom_data = apply_filters('wp_schema_framework_get_event_data', null, get_the_ID());
        if (is_array($custom_data)) {
            return $custom_data;
        }
        
        // Auto-detect The Events Calendar
        if (class_exists('Tribe__Events__Main')) {
            $data = $this->get_tribe_events_data();
            if (!empty($data)) {
                return $data;
            }
        }
        
        // Auto-detect Events Manager
        if (class_exists('EM_Events')) {
            $data = $this->get_events_manager_data();
            if (!empty($data)) {
                return $data;
            }
        }
        
        // Auto-detect Modern Events Calendar
        if (function_exists('MEC')) {
            $data = $this->get_mec_data();
            if (!empty($data)) {
                return $data;
            }
        }
        
        // Auto-detect Event Organiser
        if (function_exists('eventorganiser_is_event')) {
            $data = $this->get_event_organiser_data();
            if (!empty($data)) {
                return $data;
            }
        }
        
        // Auto-detect GatherPress
        if (class_exists('\GatherPress\Core\Event')) {
            $data = $this->get_gatherpress_data();
            if (!empty($data)) {
                return $data;
            }
        }
        
        return [];
    }
    
    /**
     * Get The Events Calendar data
     */
    private function get_tribe_events_data(): array
    {
        if (!function_exists('tribe_get_event')) {
            return [];
        }
        
        $event = tribe_get_event(get_the_ID());
        if (!$event) {
            return [];
        }
        
        $data = [
            'name' => $event->post_title,
            'description' => $event->post_excerpt ?: wp_trim_words($event->post_content, 50),
            'startDate' => $event->dates->start->format('c'),
            'endDate' => $event->dates->end->format('c'),
            'url' => get_permalink($event->ID),
        ];
        
        // Determine event type
        $categories = get_the_terms($event->ID, 'tribe_events_cat');
        if (!empty($categories)) {
            $data['eventType'] = $this->determine_event_type($categories[0]->name);
        }
        
        // Check if online event
        if (tribe_event_is_virtual($event->ID)) {
            $data['eventAttendanceMode'] = 'https://schema.org/OnlineEventAttendanceMode';
            
            // Add virtual URL if available
            $virtual_url = get_post_meta($event->ID, '_tribe_events_virtual_url', true);
            if ($virtual_url) {
                $data['location'] = [
                    'url' => $virtual_url,
                    'type' => 'VirtualLocation'
                ];
            }
        } else {
            $data['eventAttendanceMode'] = 'https://schema.org/OfflineEventAttendanceMode';
        }
        
        // Add venue/location
        if ($event->venues->count() > 0) {
            $venue = $event->venues->first();
            $data['location'] = [
                'name' => $venue->post_title,
                'address' => [
                    'streetAddress' => get_post_meta($venue->ID, '_VenueAddress', true),
                    'addressLocality' => get_post_meta($venue->ID, '_VenueCity', true),
                    'addressRegion' => get_post_meta($venue->ID, '_VenueStateProvince', true),
                    'postalCode' => get_post_meta($venue->ID, '_VenueZip', true),
                    'addressCountry' => get_post_meta($venue->ID, '_VenueCountry', true),
                ],
                'type' => 'Place'
            ];
        }
        
        // Add organizer
        if ($event->organizers->count() > 0) {
            $organizer = $event->organizers->first();
            $data['organizer'] = [
                'name' => $organizer->post_title,
                'telephone' => get_post_meta($organizer->ID, '_OrganizerPhone', true),
                'email' => get_post_meta($organizer->ID, '_OrganizerEmail', true),
                'url' => get_post_meta($organizer->ID, '_OrganizerWebsite', true),
            ];
        }
        
        // Add ticket/cost info
        $cost = tribe_get_cost($event->ID, true);
        if ($cost) {
            $data['offers'] = [
                'price' => tribe_get_cost($event->ID, false),
                'currency' => tribe_get_option('defaultCurrencySymbol', 'USD'),
                'availability' => 'https://schema.org/InStock',
            ];
        }
        
        return apply_filters('wp_schema_framework_tribe_events_data', $data, $event);
    }
    
    /**
     * Get Events Manager data
     */
    private function get_events_manager_data(): array
    {
        if (!class_exists('EM_Event')) {
            return [];
        }
        
        $em_event = em_get_event(get_the_ID());
        if (!$em_event) {
            return [];
        }
        
        $data = [
            'name' => $em_event->event_name,
            'description' => $em_event->post_excerpt ?: wp_trim_words($em_event->post_content, 50),
            'startDate' => $em_event->event_start_date . 'T' . $em_event->event_start_time,
            'endDate' => $em_event->event_end_date . 'T' . $em_event->event_end_time,
            'url' => $em_event->get_permalink(),
        ];
        
        // Add location
        if ($em_event->location_id) {
            $location = em_get_location($em_event->location_id);
            if ($location) {
                $data['location'] = [
                    'name' => $location->location_name,
                    'address' => [
                        'streetAddress' => $location->location_address,
                        'addressLocality' => $location->location_town,
                        'addressRegion' => $location->location_state,
                        'postalCode' => $location->location_postcode,
                        'addressCountry' => $location->location_country,
                    ],
                    'type' => 'Place'
                ];
            }
        }
        
        // Add ticket info if available
        if ($em_event->event_rsvp && class_exists('EM_Tickets')) {
            $tickets = $em_event->get_tickets();
            if ($tickets->tickets) {
                $min_price = min(array_map(function($ticket) {
                    return $ticket->ticket_price;
                }, $tickets->tickets));
                
                $data['offers'] = [
                    'price' => $min_price,
                    'currency' => get_option('dbem_bookings_currency', 'USD'),
                    'availability' => $em_event->get_bookings()->get_available_spaces() > 0 
                        ? 'https://schema.org/InStock' 
                        : 'https://schema.org/SoldOut',
                ];
            }
        }
        
        return apply_filters('wp_schema_framework_events_manager_data', $data, $em_event);
    }
    
    /**
     * Get Modern Events Calendar data
     */
    private function get_mec_data(): array
    {
        $mec = MEC();
        $event_id = get_the_ID();
        
        // Get MEC event data
        $event = $mec->get_event($event_id);
        if (!$event) {
            return [];
        }
        
        $data = [
            'name' => get_the_title(),
            'description' => get_the_excerpt() ?: wp_trim_words(get_the_content(), 50),
            'startDate' => $event->data->meta['mec_date']['start']['date'] . 'T' . $event->data->meta['mec_date']['start']['hour'] . ':' . $event->data->meta['mec_date']['start']['minutes'] . ':00',
            'endDate' => $event->data->meta['mec_date']['end']['date'] . 'T' . $event->data->meta['mec_date']['end']['hour'] . ':' . $event->data->meta['mec_date']['end']['minutes'] . ':00',
            'url' => get_permalink(),
        ];
        
        // Add location if available
        $location_id = get_post_meta($event_id, 'mec_location_id', true);
        if ($location_id) {
            $location = get_term($location_id, 'mec_location');
            if ($location) {
                $data['location'] = [
                    'name' => $location->name,
                    'type' => 'Place'
                ];
            }
        }
        
        // Add organizer if available
        $organizer_id = get_post_meta($event_id, 'mec_organizer_id', true);
        if ($organizer_id) {
            $organizer = get_term($organizer_id, 'mec_organizer');
            if ($organizer) {
                $data['organizer'] = [
                    'name' => $organizer->name,
                ];
            }
        }
        
        return apply_filters('wp_schema_framework_mec_data', $data, $event);
    }
    
    /**
     * Get Event Organiser data
     */
    private function get_event_organiser_data(): array
    {
        if (!function_exists('eo_get_event_datetime_format')) {
            return [];
        }
        
        $event_id = get_the_ID();
        
        $data = [
            'name' => get_the_title(),
            'description' => get_the_excerpt() ?: wp_trim_words(get_the_content(), 50),
            'startDate' => eo_get_the_start('c', $event_id),
            'endDate' => eo_get_the_end('c', $event_id),
            'url' => get_permalink(),
        ];
        
        // Add venue
        $venue_id = eo_get_venue($event_id);
        if ($venue_id) {
            $venue = eo_get_venue_name($venue_id);
            $address = eo_get_venue_address($venue_id);
            
            $data['location'] = [
                'name' => $venue,
                'address' => [
                    'streetAddress' => $address['address'],
                    'addressLocality' => $address['city'],
                    'addressRegion' => $address['state'],
                    'postalCode' => $address['postcode'],
                    'addressCountry' => $address['country'],
                ],
                'type' => 'Place'
            ];
        }
        
        return apply_filters('wp_schema_framework_event_organiser_data', $data, $event_id);
    }
    
    /**
     * Get GatherPress data
     */
    private function get_gatherpress_data(): array
    {
        if (!class_exists('\GatherPress\Core\Event')) {
            return [];
        }
        $event = new \GatherPress\Core\Event(get_the_ID());
        if (!$event->event instanceof \WP_Post) {
            return [];
        }
        $data = [
            'name' => $event->event->post_title,
            'description' => $event->event->post_excerpt ?: wp_trim_words($event->event->post_content, 50),
            'startDate' => $event->get_datetime_start('c'),
            'endDate' => $event->get_datetime_end('c'),
            'url' => get_permalink($event->event->ID),
        ];
        
        // Determine event type
        $categories = get_the_terms($event->event->ID, 'gatherpress_topic');
        if (!empty($categories)) {
            $data['eventType'] = $this->determine_event_type($categories[0]->name);
        }
        
        // Check if online event
        $venue_information = $event->get_venue_information();
        if ($venue_information['is_online_event']) {
            $data['eventAttendanceMode'] = 'https://schema.org/OnlineEventAttendanceMode';
            // GatherPress provides the virtual URL only to RSVPed users.
        } else {
            $data['eventAttendanceMode'] = 'https://schema.org/OfflineEventAttendanceMode';
        }
        
        // Add location if available
        if ($venue_information['name'] && $venue_information['full_address']) {
            $data['location'] = [
                'name' => $venue_information['name'],
                'address' => [
                    // Using the full address as streetAddress
                    // is probably the safest way, until GatherPress provides structured address data.
                    // See: https://github.com/GatherPress/gatherpress/issues/1264
                    'streetAddress' => $venue_information['full_address'],
                ],
                'type' => 'Place'
            ];
        }
        // Add location telephone if available
        if (isset($data['location']) && $venue_information['phone_number']) {
            $data['location']['telephone'] = $venue_information['phone_number'];
        }
        // Add location website if any available
        if (isset($data['location']) && ( $venue_information['permalink'] || $venue_information['website'] )) {
            $data['location']['url'] = $venue_information['website'] ?? $venue_information['permalink'];
        }
        // Add location geo data if available
        if (isset($data['location']) && $venue_information['latitude'] && $venue_information['longitude']) {
            $data['location']['geo'] = [
                'latitude' => $venue_information['latitude'],
                'longitude' => $venue_information['longitude'],
            ];
        }
        
        return apply_filters('wp_schema_framework_gatherpress_data', $data, $event);
    }
    
    /**
     * Build location schema
     */
    private function build_location($location_data)
    {
        if (is_string($location_data)) {
            return [
                '@type' => 'Place',
                'name' => $location_data
            ];
        }
        
        $type = $location_data['type'] ?? 'Place';
        
        if ($type === 'VirtualLocation') {
            return [
                '@type' => 'VirtualLocation',
                'url' => $location_data['url'] ?? ''
            ];
        }
        
        $location = [
            '@type' => 'Place',
            'name' => $location_data['name'] ?? '',
        ];
        
        if (!empty($location_data['address'])) {
            $location['address'] = [
                '@type' => 'PostalAddress',
                'streetAddress' => $location_data['address']['streetAddress'] ?? '',
                'addressLocality' => $location_data['address']['addressLocality'] ?? '',
                'addressRegion' => $location_data['address']['addressRegion'] ?? '',
                'postalCode' => $location_data['address']['postalCode'] ?? '',
                'addressCountry' => $location_data['address']['addressCountry'] ?? '',
            ];
        }
        
        if (!empty($location_data['geo'])) {
            $location['geo'] = [
                '@type' => 'GeoCoordinates',
                'latitude' => $location_data['geo']['latitude'],
                'longitude' => $location_data['geo']['longitude'],
            ];
        }
        
        return $location;
    }
    
    /**
     * Build organizer schema
     */
    private function build_organizer($organizer_data)
    {
        if (is_string($organizer_data)) {
            return [
                '@type' => 'Organization',
                'name' => $organizer_data
            ];
        }
        
        $organizer = [
            '@type' => $organizer_data['type'] ?? 'Organization',
            'name' => $organizer_data['name'] ?? '',
        ];
        
        if (!empty($organizer_data['url'])) {
            $organizer['url'] = $organizer_data['url'];
        }
        
        if (!empty($organizer_data['telephone'])) {
            $organizer['telephone'] = $organizer_data['telephone'];
        }
        
        if (!empty($organizer_data['email'])) {
            $organizer['email'] = $organizer_data['email'];
        }
        
        return $organizer;
    }
    
    /**
     * Build performers schema
     */
    private function build_performers($performer_data)
    {
        if (is_string($performer_data)) {
            return [
                '@type' => 'Person',
                'name' => $performer_data
            ];
        }
        
        if (isset($performer_data['name'])) {
            // Single performer
            return [
                '@type' => $performer_data['type'] ?? 'Person',
                'name' => $performer_data['name']
            ];
        }
        
        // Multiple performers
        $performers = [];
        foreach ($performer_data as $performer) {
            if (is_string($performer)) {
                $performers[] = [
                    '@type' => 'Person',
                    'name' => $performer
                ];
            } else {
                $performers[] = [
                    '@type' => $performer['type'] ?? 'Person',
                    'name' => $performer['name'] ?? ''
                ];
            }
        }
        
        return $performers;
    }
    
    /**
     * Build offers schema
     */
    private function build_offers($offers_data)
    {
        if (!is_array($offers_data)) {
            return [];
        }
        
        // Single offer
        if (isset($offers_data['price'])) {
            return [
                '@type' => 'Offer',
                'price' => $offers_data['price'],
                'priceCurrency' => $offers_data['currency'] ?? 'USD',
                'availability' => $offers_data['availability'] ?? 'https://schema.org/InStock',
                'validFrom' => $offers_data['validFrom'] ?? date('c'),
                'url' => $offers_data['url'] ?? get_permalink(),
            ];
        }
        
        // Multiple offers
        $offers = [];
        foreach ($offers_data as $offer) {
            $offers[] = [
                '@type' => 'Offer',
                'price' => $offer['price'] ?? 0,
                'priceCurrency' => $offer['currency'] ?? 'USD',
                'availability' => $offer['availability'] ?? 'https://schema.org/InStock',
                'validFrom' => $offer['validFrom'] ?? date('c'),
                'url' => $offer['url'] ?? get_permalink(),
                'name' => $offer['name'] ?? '',
            ];
        }
        
        return $offers;
    }
    
    /**
     * Check if The Events Calendar is outputting its own schema
     */
    private function is_tribe_events_schema_enabled(): bool
    {
        // The Events Calendar uses tribe_get_option for settings
        if (function_exists('tribe_get_option')) {
            // Check if JSON-LD is disabled in their settings
            $jsonld_disabled = tribe_get_option('disable_jsonld', false);
            if ($jsonld_disabled) {
                return false; // They disabled it, we can provide schema
            }
        }
        
        // Check if Tribe's JSON_LD class is active
        if (class_exists('Tribe__Events__JSON_LD__Event')) {
            // It's active by default
            return apply_filters('wp_schema_framework_tribe_events_schema_active', true);
        }
        
        return false;
    }
    
    /**
     * Determine event type based on category
     */
    private function determine_event_type($category_name): string
    {
        $category_lower = strtolower($category_name);
        
        $type_mappings = [
            'music' => 'MusicEvent',
            'concert' => 'MusicEvent',
            'sports' => 'SportsEvent',
            'game' => 'SportsEvent',
            'education' => 'EducationEvent',
            'workshop' => 'EducationEvent',
            'seminar' => 'EducationEvent',
            'business' => 'BusinessEvent',
            'conference' => 'BusinessEvent',
            'meeting' => 'BusinessEvent',
            'food' => 'FoodEvent',
            'dining' => 'FoodEvent',
            'festival' => 'Festival',
            'theater' => 'TheaterEvent',
            'theatre' => 'TheaterEvent',
            'comedy' => 'ComedyEvent',
            'dance' => 'DanceEvent',
            'visual' => 'VisualArtsEvent',
            'art' => 'VisualArtsEvent',
            'gallery' => 'VisualArtsEvent',
            'literary' => 'LiteraryEvent',
            'book' => 'LiteraryEvent',
            'social' => 'SocialEvent',
            'party' => 'SocialEvent',
            'sale' => 'SaleEvent',
            'screening' => 'ScreeningEvent',
            'movie' => 'ScreeningEvent',
            'film' => 'ScreeningEvent',
        ];
        
        foreach ($type_mappings as $keyword => $event_type) {
            if (strpos($category_lower, $keyword) !== false) {
                return $event_type;
            }
        }
        
        return 'Event'; // Default
    }
}