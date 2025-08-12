<?php

declare(strict_types=1);

namespace BuiltNorth\WPSchema\Services;

/**
 * Schema Type Registry
 * 
 * Manages available schema.org types for UI and validation.
 * 
 * @since 3.0.0
 */
class SchemaTypeRegistry
{
    /**
     * Get available schema types for UI
     */
    public function get_available_types(): array
    {
        // Comprehensive list of schema.org types commonly used
        $types = [
            // Content Types
            ['label' => 'Article', 'value' => 'Article'],
            ['label' => 'BlogPosting', 'value' => 'BlogPosting'],
            ['label' => 'NewsArticle', 'value' => 'NewsArticle'],
            ['label' => 'WebPage', 'value' => 'WebPage'],
            ['label' => 'HowTo', 'value' => 'HowTo'],
            ['label' => 'QAPage', 'value' => 'QAPage'],
            ['label' => 'TechArticle', 'value' => 'TechArticle'],
            ['label' => 'Report', 'value' => 'Report'],
            
            // Page Types
            ['label' => 'Contact Page', 'value' => 'ContactPage'],
            ['label' => 'About Page', 'value' => 'AboutPage'],
            ['label' => 'Privacy Policy Page', 'value' => 'PrivacyPolicyPage'],
            ['label' => 'Terms of Service Page', 'value' => 'TermsOfServicePage'],
            ['label' => 'Checkout Page', 'value' => 'CheckoutPage'],
            ['label' => 'Profile Page', 'value' => 'ProfilePage'],
            ['label' => 'FAQ Page', 'value' => 'FAQPage'],
            ['label' => 'Collection Page', 'value' => 'CollectionPage'],
            ['label' => 'Media Gallery', 'value' => 'MediaGallery'],
            
            // Commerce
            ['label' => 'Product', 'value' => 'Product'],
            ['label' => 'Service', 'value' => 'Service'],
            ['label' => 'Review', 'value' => 'Review'],
            
            // Business & Organizations
            ['label' => 'LocalBusiness', 'value' => 'LocalBusiness'],
            ['label' => 'Restaurant', 'value' => 'Restaurant'],
            ['label' => 'Organization', 'value' => 'Organization'],
            ['label' => 'Corporation', 'value' => 'Corporation'],
            ['label' => 'ProfessionalService', 'value' => 'ProfessionalService'],
            ['label' => 'Store', 'value' => 'Store'],
            ['label' => 'Hotel', 'value' => 'Hotel'],
            
            // Home Services
            ['label' => 'HomeAndConstructionBusiness', 'value' => 'HomeAndConstructionBusiness'],
            ['label' => 'Plumber', 'value' => 'Plumber'],
            ['label' => 'Electrician', 'value' => 'Electrician'],
            ['label' => 'GeneralContractor', 'value' => 'GeneralContractor'],
            ['label' => 'RoofingContractor', 'value' => 'RoofingContractor'],
            ['label' => 'HVACBusiness', 'value' => 'HVACBusiness'],
            ['label' => 'HousePainter', 'value' => 'HousePainter'],
            ['label' => 'Locksmith', 'value' => 'Locksmith'],
            ['label' => 'MovingCompany', 'value' => 'MovingCompany'],
            
            // Professional Services
            ['label' => 'Attorney', 'value' => 'Attorney'],
            ['label' => 'Dentist', 'value' => 'Dentist'],
            ['label' => 'Physician', 'value' => 'Physician'],
            ['label' => 'AccountingService', 'value' => 'AccountingService'],
            ['label' => 'InsuranceAgency', 'value' => 'InsuranceAgency'],
            ['label' => 'Notary', 'value' => 'Notary'],
            ['label' => 'EmploymentAgency', 'value' => 'EmploymentAgency'],
            
            // Food & Dining
            ['label' => 'Bakery', 'value' => 'Bakery'],
            ['label' => 'BarOrPub', 'value' => 'BarOrPub'],
            ['label' => 'Brewery', 'value' => 'Brewery'],
            ['label' => 'CafeOrCoffeeShop', 'value' => 'CafeOrCoffeeShop'],
            ['label' => 'FastFoodRestaurant', 'value' => 'FastFoodRestaurant'],
            ['label' => 'IceCreamShop', 'value' => 'IceCreamShop'],
            ['label' => 'Winery', 'value' => 'Winery'],
            ['label' => 'Distillery', 'value' => 'Distillery'],
            
            // Retail & Shopping
            ['label' => 'ClothingStore', 'value' => 'ClothingStore'],
            ['label' => 'ConvenienceStore', 'value' => 'ConvenienceStore'],
            ['label' => 'DepartmentStore', 'value' => 'DepartmentStore'],
            ['label' => 'ElectronicsStore', 'value' => 'ElectronicsStore'],
            ['label' => 'Florist', 'value' => 'Florist'],
            ['label' => 'FurnitureStore', 'value' => 'FurnitureStore'],
            ['label' => 'GroceryStore', 'value' => 'GroceryStore'],
            ['label' => 'HardwareStore', 'value' => 'HardwareStore'],
            ['label' => 'JewelryStore', 'value' => 'JewelryStore'],
            ['label' => 'LiquorStore', 'value' => 'LiquorStore'],
            ['label' => 'MensClothingStore', 'value' => 'MensClothingStore'],
            ['label' => 'MobilePhoneStore', 'value' => 'MobilePhoneStore'],
            ['label' => 'OutletStore', 'value' => 'OutletStore'],
            ['label' => 'PawnShop', 'value' => 'PawnShop'],
            ['label' => 'PetStore', 'value' => 'PetStore'],
            ['label' => 'ShoeStore', 'value' => 'ShoeStore'],
            ['label' => 'SportingGoodsStore', 'value' => 'SportingGoodsStore'],
            ['label' => 'TireShop', 'value' => 'TireShop'],
            ['label' => 'ToyStore', 'value' => 'ToyStore'],
            ['label' => 'WholesaleStore', 'value' => 'WholesaleStore'],
            
            // Automotive Services
            ['label' => 'AutoBodyShop', 'value' => 'AutoBodyShop'],
            ['label' => 'AutoPartsStore', 'value' => 'AutoPartsStore'],
            ['label' => 'AutoRental', 'value' => 'AutoRental'],
            ['label' => 'AutoRepair', 'value' => 'AutoRepair'],
            ['label' => 'AutoWash', 'value' => 'AutoWash'],
            ['label' => 'GasStation', 'value' => 'GasStation'],
            ['label' => 'MotorcycleDealer', 'value' => 'MotorcycleDealer'],
            ['label' => 'MotorcycleRepair', 'value' => 'MotorcycleRepair'],
            
            // Personal Services
            ['label' => 'BeautySalon', 'value' => 'BeautySalon'],
            ['label' => 'DaySpa', 'value' => 'DaySpa'],
            ['label' => 'HairSalon', 'value' => 'HairSalon'],
            ['label' => 'NailSalon', 'value' => 'NailSalon'],
            ['label' => 'TattooParlor', 'value' => 'TattooParlor'],
            ['label' => 'DryCleaningOrLaundry', 'value' => 'DryCleaningOrLaundry'],
            
            // Emergency Services
            ['label' => 'EmergencyService', 'value' => 'EmergencyService'],
            ['label' => 'FireStation', 'value' => 'FireStation'],
            ['label' => 'Hospital', 'value' => 'Hospital'],
            ['label' => 'PoliceStation', 'value' => 'PoliceStation'],
            
            // Recreation & Entertainment
            ['label' => 'AmusementPark', 'value' => 'AmusementPark'],
            ['label' => 'ArtGallery', 'value' => 'ArtGallery'],
            ['label' => 'Casino', 'value' => 'Casino'],
            ['label' => 'ComedyClub', 'value' => 'ComedyClub'],
            ['label' => 'MovieTheater', 'value' => 'MovieTheater'],
            ['label' => 'Museum', 'value' => 'Museum'],
            ['label' => 'MusicVenue', 'value' => 'MusicVenue'],
            ['label' => 'NightClub', 'value' => 'NightClub'],
            ['label' => 'BowlingAlley', 'value' => 'BowlingAlley'],
            ['label' => 'GolfCourse', 'value' => 'GolfCourse'],
            ['label' => 'HealthClub', 'value' => 'HealthClub'],
            ['label' => 'PublicSwimmingPool', 'value' => 'PublicSwimmingPool'],
            ['label' => 'SkiResort', 'value' => 'SkiResort'],
            ['label' => 'SportsClub', 'value' => 'SportsClub'],
            ['label' => 'StadiumOrArena', 'value' => 'StadiumOrArena'],
            ['label' => 'TennisComplex', 'value' => 'TennisComplex'],
            ['label' => 'Zoo', 'value' => 'Zoo'],
            ['label' => 'Aquarium', 'value' => 'Aquarium'],
            
            // Community Services
            ['label' => 'AnimalShelter', 'value' => 'AnimalShelter'],
            ['label' => 'ChildCare', 'value' => 'ChildCare'],
            ['label' => 'Library', 'value' => 'Library'],
            ['label' => 'Park', 'value' => 'Park'],
            ['label' => 'ParkingFacility', 'value' => 'ParkingFacility'],
            ['label' => 'PostOffice', 'value' => 'PostOffice'],
            ['label' => 'Preschool', 'value' => 'Preschool'],
            ['label' => 'School', 'value' => 'School'],
            ['label' => 'VeterinaryCare', 'value' => 'VeterinaryCare'],
            
            // Religious
            ['label' => 'Church', 'value' => 'Church'],
            ['label' => 'Mosque', 'value' => 'Mosque'],
            ['label' => 'Synagogue', 'value' => 'Synagogue'],
            ['label' => 'BuddhistTemple', 'value' => 'BuddhistTemple'],
            ['label' => 'HinduTemple', 'value' => 'HinduTemple'],
            ['label' => 'CatholicChurch', 'value' => 'CatholicChurch'],
            
            // Transportation
            ['label' => 'Airport', 'value' => 'Airport'],
            ['label' => 'BusStation', 'value' => 'BusStation'],
            ['label' => 'BusStop', 'value' => 'BusStop'],
            ['label' => 'Taxi', 'value' => 'Taxi'],
            ['label' => 'TaxiStand', 'value' => 'TaxiStand'],
            ['label' => 'TrainStation', 'value' => 'TrainStation'],
            ['label' => 'SubwayStation', 'value' => 'SubwayStation'],
            
            // Civic Infrastructure
            ['label' => 'CivicStructure', 'value' => 'CivicStructure'],
            ['label' => 'CityHall', 'value' => 'CityHall'],
            ['label' => 'Courthouse', 'value' => 'Courthouse'],
            ['label' => 'DefenceEstablishment', 'value' => 'DefenceEstablishment'],
            ['label' => 'Embassy', 'value' => 'Embassy'],
            ['label' => 'LegislativeBuilding', 'value' => 'LegislativeBuilding'],
            
            // Media
            ['label' => 'VideoObject', 'value' => 'VideoObject'],
            ['label' => 'ImageObject', 'value' => 'ImageObject'],
            ['label' => 'AudioObject', 'value' => 'AudioObject'],
            ['label' => 'PodcastEpisode', 'value' => 'PodcastEpisode'],
            ['label' => 'VideoGame', 'value' => 'VideoGame'],
            ['label' => 'WebSite', 'value' => 'WebSite'],
            
            // Educational
            ['label' => 'Course', 'value' => 'Course'],
            ['label' => 'LearningResource', 'value' => 'LearningResource'],
            ['label' => 'Quiz', 'value' => 'Quiz'],
            ['label' => 'EducationalOrganization', 'value' => 'EducationalOrganization'],
            
            // Medical/Health
            ['label' => 'MedicalCondition', 'value' => 'MedicalCondition'],
            ['label' => 'MedicalWebPage', 'value' => 'MedicalWebPage'],
            ['label' => 'HealthAndBeautyBusiness', 'value' => 'HealthAndBeautyBusiness'],
            
            // Financial
            ['label' => 'FinancialProduct', 'value' => 'FinancialProduct'],
            ['label' => 'FinancialService', 'value' => 'FinancialService'],
            
            // Real Estate
            ['label' => 'RealEstateListing', 'value' => 'RealEstateListing'],
            ['label' => 'Apartment', 'value' => 'Apartment'],
            ['label' => 'House', 'value' => 'House'],
            
            // Automotive
            ['label' => 'Car', 'value' => 'Car'],
            ['label' => 'Vehicle', 'value' => 'Vehicle'],
            ['label' => 'AutoDealer', 'value' => 'AutoDealer'],
            
            // Travel
            ['label' => 'TouristAttraction', 'value' => 'TouristAttraction'],
            ['label' => 'TravelAgency', 'value' => 'TravelAgency'],
            ['label' => 'LodgingBusiness', 'value' => 'LodgingBusiness'],
            
            // Entertainment
            ['label' => 'TVSeries', 'value' => 'TVSeries'],
            ['label' => 'TVEpisode', 'value' => 'TVEpisode'],
            ['label' => 'MusicAlbum', 'value' => 'MusicAlbum'],
            ['label' => 'Game', 'value' => 'Game'],
            ['label' => 'Movie', 'value' => 'Movie'],
            ['label' => 'MusicRecording', 'value' => 'MusicRecording'],
            ['label' => 'Book', 'value' => 'Book'],
            
            // Government & Non-Profit
            ['label' => 'GovernmentOrganization', 'value' => 'GovernmentOrganization'],
            ['label' => 'GovernmentService', 'value' => 'GovernmentService'],
            ['label' => 'NGO', 'value' => 'NGO'],
            
            // Sports
            ['label' => 'SportsOrganization', 'value' => 'SportsOrganization'],
            ['label' => 'SportsTeam', 'value' => 'SportsTeam'],
            
            // Digital Products & Tech
            ['label' => 'SoftwareApplication', 'value' => 'SoftwareApplication'],
            ['label' => 'MobileApplication', 'value' => 'MobileApplication'],
            ['label' => 'WebApplication', 'value' => 'WebApplication'],
            ['label' => 'VideoGameSeries', 'value' => 'VideoGameSeries'],
            ['label' => 'APIReference', 'value' => 'APIReference'],
            
            // Events & Activities
            ['label' => 'Event', 'value' => 'Event'],
            ['label' => 'BusinessEvent', 'value' => 'BusinessEvent'],
            ['label' => 'ChildrensEvent', 'value' => 'ChildrensEvent'],
            ['label' => 'ComedyEvent', 'value' => 'ComedyEvent'],
            ['label' => 'DanceEvent', 'value' => 'DanceEvent'],
            ['label' => 'DeliveryEvent', 'value' => 'DeliveryEvent'],
            ['label' => 'EducationEvent', 'value' => 'EducationEvent'],
            ['label' => 'ExhibitionEvent', 'value' => 'ExhibitionEvent'],
            ['label' => 'Festival', 'value' => 'Festival'],
            ['label' => 'FoodEvent', 'value' => 'FoodEvent'],
            ['label' => 'LiteraryEvent', 'value' => 'LiteraryEvent'],
            ['label' => 'MusicEvent', 'value' => 'MusicEvent'],
            ['label' => 'PublicationEvent', 'value' => 'PublicationEvent'],
            ['label' => 'SaleEvent', 'value' => 'SaleEvent'],
            ['label' => 'ScreeningEvent', 'value' => 'ScreeningEvent'],
            ['label' => 'SocialEvent', 'value' => 'SocialEvent'],
            ['label' => 'SportsEvent', 'value' => 'SportsEvent'],
            ['label' => 'TheaterEvent', 'value' => 'TheaterEvent'],
            ['label' => 'VisualArtsEvent', 'value' => 'VisualArtsEvent'],
            
            // Reviews & Ratings
            ['label' => 'CriticReview', 'value' => 'CriticReview'],
            ['label' => 'UserReview', 'value' => 'UserReview'],
            ['label' => 'EmployerReview', 'value' => 'EmployerReview'],
            ['label' => 'ClaimReview', 'value' => 'ClaimReview'],
            
            // Creative Works
            ['label' => 'CreativeWork', 'value' => 'CreativeWork'],
            ['label' => 'Photograph', 'value' => 'Photograph'],
            ['label' => 'Painting', 'value' => 'Painting'],
            ['label' => 'Sculpture', 'value' => 'Sculpture'],
            ['label' => 'VisualArtwork', 'value' => 'VisualArtwork'],
            ['label' => 'ComicStory', 'value' => 'ComicStory'],
            ['label' => 'ComicSeries', 'value' => 'ComicSeries'],
            ['label' => 'Periodical', 'value' => 'Periodical'],
            ['label' => 'PublicationVolume', 'value' => 'PublicationVolume'],
            ['label' => 'Thesis', 'value' => 'Thesis'],
            ['label' => 'Manuscript', 'value' => 'Manuscript'],
            ['label' => 'DigitalDocument', 'value' => 'DigitalDocument'],
            ['label' => 'Drawing', 'value' => 'Drawing'],
            ['label' => 'Map', 'value' => 'Map'],
            ['label' => 'MusicPlaylist', 'value' => 'MusicPlaylist'],
            ['label' => 'MusicComposition', 'value' => 'MusicComposition'],
            ['label' => 'SheetMusic', 'value' => 'SheetMusic'],
            
            // Actions
            ['label' => 'Action', 'value' => 'Action'],
            ['label' => 'AskAction', 'value' => 'AskAction'],
            ['label' => 'FollowAction', 'value' => 'FollowAction'],
            ['label' => 'JoinAction', 'value' => 'JoinAction'],
            ['label' => 'PlayAction', 'value' => 'PlayAction'],
            ['label' => 'ReadAction', 'value' => 'ReadAction'],
            ['label' => 'ShareAction', 'value' => 'ShareAction'],
            ['label' => 'SubscribeAction', 'value' => 'SubscribeAction'],
            ['label' => 'ViewAction', 'value' => 'ViewAction'],
            ['label' => 'WatchAction', 'value' => 'WatchAction'],
            
            // Other
            ['label' => 'Recipe', 'value' => 'Recipe'],
            ['label' => 'Person', 'value' => 'Person'],
            ['label' => 'JobPosting', 'value' => 'JobPosting'],
            ['label' => 'Place', 'value' => 'Place'],
            ['label' => 'Accommodation', 'value' => 'Accommodation'],
            ['label' => 'Residence', 'value' => 'Residence'],
            ['label' => 'TouristDestination', 'value' => 'TouristDestination'],
            ['label' => 'LandmarksOrHistoricalBuildings', 'value' => 'LandmarksOrHistoricalBuildings'],
            ['label' => 'Mountain', 'value' => 'Mountain'],
            ['label' => 'BodyOfWater', 'value' => 'BodyOfWater'],
            ['label' => 'Beach', 'value' => 'Beach'],
            ['label' => 'Canal', 'value' => 'Canal'],
            ['label' => 'Cemetery', 'value' => 'Cemetery'],
            ['label' => 'Country', 'value' => 'Country'],
            ['label' => 'City', 'value' => 'City'],
            ['label' => 'State', 'value' => 'State'],
            ['label' => 'AdministrativeArea', 'value' => 'AdministrativeArea'],
        ];

        // Allow filtering to add/remove types
        return apply_filters('wp_schema_framework_type_registry_types', $types);
    }

    /**
     * Get post type to schema type mappings
     */
    public function get_post_type_mappings(): array
    {
        $mappings = [
            'post' => 'Article',
            'page' => 'WebPage',
            'product' => 'Product',
            'event' => 'Event',
            'news' => 'NewsArticle',
            'blog_post' => 'BlogPosting',
            'recipe' => 'Recipe',
            'restaurant' => 'Restaurant',
            'business' => 'LocalBusiness',
            'service' => 'Service',
            'review' => 'Review',
            'book' => 'Book',
            'movie' => 'Movie',
            'job' => 'JobPosting',
            'course' => 'Course',
            'faq' => 'FAQPage',
        ];

        return apply_filters('wp_schema_framework_post_type_mappings', $mappings);
    }

    /**
     * Get schema type for a post type
     */
    public function get_schema_type_for_post_type(string $post_type): string
    {
        $mappings = $this->get_post_type_mappings();
        return $mappings[$post_type] ?? 'Article';
    }

    /**
     * Check if a schema type is valid
     */
    public function is_valid_type(string $type): bool
    {
        $available = $this->get_available_types();
        $values = array_column($available, 'value');
        return in_array($type, $values, true);
    }
}