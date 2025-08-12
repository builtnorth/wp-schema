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
        // Comprehensive list of schema.org types with category metadata
        $types = [
            // Content Types
            ['label' => 'Article', 'value' => 'Article', 'category' => 'CreativeWork', 'subcategory' => 'Article'],
            ['label' => 'BlogPosting', 'value' => 'BlogPosting', 'category' => 'CreativeWork', 'subcategory' => 'Article'],
            ['label' => 'NewsArticle', 'value' => 'NewsArticle', 'category' => 'CreativeWork', 'subcategory' => 'Article'],
            ['label' => 'WebPage', 'value' => 'WebPage', 'category' => 'CreativeWork', 'subcategory' => 'WebPage'],
            ['label' => 'HowTo', 'value' => 'HowTo', 'category' => 'CreativeWork', 'subcategory' => 'Article'],
            ['label' => 'QAPage', 'value' => 'QAPage', 'category' => 'CreativeWork', 'subcategory' => 'WebPage'],
            ['label' => 'TechArticle', 'value' => 'TechArticle', 'category' => 'CreativeWork', 'subcategory' => 'Article'],
            ['label' => 'Report', 'value' => 'Report', 'category' => 'CreativeWork', 'subcategory' => 'Article'],
            
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
            ['label' => 'LocalBusiness', 'value' => 'LocalBusiness', 'category' => 'Organization', 'subcategory' => 'LocalBusiness'],
            ['label' => 'Restaurant', 'value' => 'Restaurant', 'category' => 'Organization', 'subcategory' => 'FoodEstablishment', 'parent' => 'FoodEstablishment'],
            ['label' => 'Organization', 'value' => 'Organization', 'category' => 'Organization', 'subcategory' => 'Organization'],
            ['label' => 'Corporation', 'value' => 'Corporation', 'category' => 'Organization', 'subcategory' => 'Organization'],
            ['label' => 'ProfessionalService', 'value' => 'ProfessionalService', 'category' => 'Organization', 'subcategory' => 'LocalBusiness'],
            ['label' => 'Store', 'value' => 'Store', 'category' => 'Organization', 'subcategory' => 'Store', 'parent' => 'LocalBusiness'],
            ['label' => 'Hotel', 'value' => 'Hotel', 'category' => 'Organization', 'subcategory' => 'LodgingBusiness', 'parent' => 'LodgingBusiness'],
            
            // Home Services
            ['label' => 'HomeAndConstructionBusiness', 'value' => 'HomeAndConstructionBusiness', 'category' => 'Organization', 'subcategory' => 'HomeAndConstructionBusiness', 'parent' => 'LocalBusiness'],
            ['label' => 'Plumber', 'value' => 'Plumber', 'category' => 'Organization', 'subcategory' => 'HomeAndConstructionBusiness', 'parent' => 'HomeAndConstructionBusiness'],
            ['label' => 'Electrician', 'value' => 'Electrician', 'category' => 'Organization', 'subcategory' => 'HomeAndConstructionBusiness', 'parent' => 'HomeAndConstructionBusiness'],
            ['label' => 'GeneralContractor', 'value' => 'GeneralContractor', 'category' => 'Organization', 'subcategory' => 'HomeAndConstructionBusiness', 'parent' => 'HomeAndConstructionBusiness'],
            ['label' => 'RoofingContractor', 'value' => 'RoofingContractor', 'category' => 'Organization', 'subcategory' => 'HomeAndConstructionBusiness', 'parent' => 'HomeAndConstructionBusiness'],
            ['label' => 'HVACBusiness', 'value' => 'HVACBusiness', 'category' => 'Organization', 'subcategory' => 'HomeAndConstructionBusiness', 'parent' => 'HomeAndConstructionBusiness'],
            ['label' => 'HousePainter', 'value' => 'HousePainter', 'category' => 'Organization', 'subcategory' => 'HomeAndConstructionBusiness', 'parent' => 'HomeAndConstructionBusiness'],
            ['label' => 'Locksmith', 'value' => 'Locksmith', 'category' => 'Organization', 'subcategory' => 'HomeAndConstructionBusiness', 'parent' => 'HomeAndConstructionBusiness'],
            ['label' => 'MovingCompany', 'value' => 'MovingCompany', 'category' => 'Organization', 'subcategory' => 'HomeAndConstructionBusiness', 'parent' => 'HomeAndConstructionBusiness'],
            
            // Professional Services
            ['label' => 'Attorney', 'value' => 'Attorney', 'category' => 'Organization', 'subcategory' => 'LegalService', 'parent' => 'LegalService'],
            ['label' => 'Dentist', 'value' => 'Dentist', 'category' => 'Organization', 'subcategory' => 'MedicalBusiness', 'parent' => 'MedicalBusiness'],
            ['label' => 'Physician', 'value' => 'Physician', 'category' => 'Organization', 'subcategory' => 'MedicalBusiness', 'parent' => 'MedicalBusiness'],
            ['label' => 'AccountingService', 'value' => 'AccountingService', 'category' => 'Organization', 'subcategory' => 'FinancialService', 'parent' => 'FinancialService'],
            ['label' => 'InsuranceAgency', 'value' => 'InsuranceAgency', 'category' => 'Organization', 'subcategory' => 'FinancialService', 'parent' => 'FinancialService'],
            ['label' => 'Notary', 'value' => 'Notary', 'category' => 'Organization', 'subcategory' => 'LegalService', 'parent' => 'LegalService'],
            ['label' => 'EmploymentAgency', 'value' => 'EmploymentAgency', 'category' => 'Organization', 'subcategory' => 'LocalBusiness', 'parent' => 'LocalBusiness'],
            
            // Food & Dining
            ['label' => 'Bakery', 'value' => 'Bakery', 'category' => 'Organization', 'subcategory' => 'FoodEstablishment', 'parent' => 'FoodEstablishment'],
            ['label' => 'BarOrPub', 'value' => 'BarOrPub', 'category' => 'Organization', 'subcategory' => 'FoodEstablishment', 'parent' => 'FoodEstablishment'],
            ['label' => 'Brewery', 'value' => 'Brewery', 'category' => 'Organization', 'subcategory' => 'FoodEstablishment', 'parent' => 'FoodEstablishment'],
            ['label' => 'CafeOrCoffeeShop', 'value' => 'CafeOrCoffeeShop', 'category' => 'Organization', 'subcategory' => 'FoodEstablishment', 'parent' => 'FoodEstablishment'],
            ['label' => 'FastFoodRestaurant', 'value' => 'FastFoodRestaurant', 'category' => 'Organization', 'subcategory' => 'FoodEstablishment', 'parent' => 'FoodEstablishment'],
            ['label' => 'IceCreamShop', 'value' => 'IceCreamShop', 'category' => 'Organization', 'subcategory' => 'FoodEstablishment', 'parent' => 'FoodEstablishment'],
            ['label' => 'Winery', 'value' => 'Winery', 'category' => 'Organization', 'subcategory' => 'FoodEstablishment', 'parent' => 'FoodEstablishment'],
            ['label' => 'Distillery', 'value' => 'Distillery', 'category' => 'Organization', 'subcategory' => 'FoodEstablishment', 'parent' => 'FoodEstablishment'],
            
            // Retail & Shopping
            ['label' => 'ClothingStore', 'value' => 'ClothingStore', 'category' => 'Organization', 'subcategory' => 'Store', 'parent' => 'Store'],
            ['label' => 'ConvenienceStore', 'value' => 'ConvenienceStore', 'category' => 'Organization', 'subcategory' => 'Store', 'parent' => 'Store'],
            ['label' => 'DepartmentStore', 'value' => 'DepartmentStore', 'category' => 'Organization', 'subcategory' => 'Store', 'parent' => 'Store'],
            ['label' => 'ElectronicsStore', 'value' => 'ElectronicsStore', 'category' => 'Organization', 'subcategory' => 'Store', 'parent' => 'Store'],
            ['label' => 'Florist', 'value' => 'Florist', 'category' => 'Organization', 'subcategory' => 'Store', 'parent' => 'Store'],
            ['label' => 'FurnitureStore', 'value' => 'FurnitureStore', 'category' => 'Organization', 'subcategory' => 'Store', 'parent' => 'Store'],
            ['label' => 'GroceryStore', 'value' => 'GroceryStore', 'category' => 'Organization', 'subcategory' => 'Store', 'parent' => 'Store'],
            ['label' => 'HardwareStore', 'value' => 'HardwareStore', 'category' => 'Organization', 'subcategory' => 'Store', 'parent' => 'Store'],
            ['label' => 'JewelryStore', 'value' => 'JewelryStore', 'category' => 'Organization', 'subcategory' => 'Store', 'parent' => 'Store'],
            ['label' => 'LiquorStore', 'value' => 'LiquorStore', 'category' => 'Organization', 'subcategory' => 'Store', 'parent' => 'Store'],
            ['label' => 'MensClothingStore', 'value' => 'MensClothingStore', 'category' => 'Organization', 'subcategory' => 'Store', 'parent' => 'Store'],
            ['label' => 'MobilePhoneStore', 'value' => 'MobilePhoneStore', 'category' => 'Organization', 'subcategory' => 'Store', 'parent' => 'Store'],
            ['label' => 'OutletStore', 'value' => 'OutletStore', 'category' => 'Organization', 'subcategory' => 'Store', 'parent' => 'Store'],
            ['label' => 'PawnShop', 'value' => 'PawnShop', 'category' => 'Organization', 'subcategory' => 'Store', 'parent' => 'Store'],
            ['label' => 'PetStore', 'value' => 'PetStore', 'category' => 'Organization', 'subcategory' => 'Store', 'parent' => 'Store'],
            ['label' => 'ShoeStore', 'value' => 'ShoeStore', 'category' => 'Organization', 'subcategory' => 'Store', 'parent' => 'Store'],
            ['label' => 'SportingGoodsStore', 'value' => 'SportingGoodsStore', 'category' => 'Organization', 'subcategory' => 'Store', 'parent' => 'Store'],
            ['label' => 'TireShop', 'value' => 'TireShop', 'category' => 'Organization', 'subcategory' => 'Store', 'parent' => 'Store'],
            ['label' => 'ToyStore', 'value' => 'ToyStore', 'category' => 'Organization', 'subcategory' => 'Store', 'parent' => 'Store'],
            ['label' => 'WholesaleStore', 'value' => 'WholesaleStore', 'category' => 'Organization', 'subcategory' => 'Store', 'parent' => 'Store'],
            
            // Automotive Services
            ['label' => 'AutoBodyShop', 'value' => 'AutoBodyShop', 'category' => 'Organization', 'subcategory' => 'AutomotiveBusiness', 'parent' => 'AutomotiveBusiness'],
            ['label' => 'AutoPartsStore', 'value' => 'AutoPartsStore', 'category' => 'Organization', 'subcategory' => 'AutomotiveBusiness', 'parent' => 'AutomotiveBusiness'],
            ['label' => 'AutoRental', 'value' => 'AutoRental', 'category' => 'Organization', 'subcategory' => 'AutomotiveBusiness', 'parent' => 'AutomotiveBusiness'],
            ['label' => 'AutoRepair', 'value' => 'AutoRepair', 'category' => 'Organization', 'subcategory' => 'AutomotiveBusiness', 'parent' => 'AutomotiveBusiness'],
            ['label' => 'AutoWash', 'value' => 'AutoWash', 'category' => 'Organization', 'subcategory' => 'AutomotiveBusiness', 'parent' => 'AutomotiveBusiness'],
            ['label' => 'GasStation', 'value' => 'GasStation', 'category' => 'Organization', 'subcategory' => 'AutomotiveBusiness', 'parent' => 'AutomotiveBusiness'],
            ['label' => 'MotorcycleDealer', 'value' => 'MotorcycleDealer', 'category' => 'Organization', 'subcategory' => 'AutomotiveBusiness', 'parent' => 'AutomotiveBusiness'],
            ['label' => 'MotorcycleRepair', 'value' => 'MotorcycleRepair', 'category' => 'Organization', 'subcategory' => 'AutomotiveBusiness', 'parent' => 'AutomotiveBusiness'],
            
            // Personal Services
            ['label' => 'BeautySalon', 'value' => 'BeautySalon', 'category' => 'Organization', 'subcategory' => 'HealthAndBeautyBusiness', 'parent' => 'HealthAndBeautyBusiness'],
            ['label' => 'DaySpa', 'value' => 'DaySpa', 'category' => 'Organization', 'subcategory' => 'HealthAndBeautyBusiness', 'parent' => 'HealthAndBeautyBusiness'],
            ['label' => 'HairSalon', 'value' => 'HairSalon', 'category' => 'Organization', 'subcategory' => 'HealthAndBeautyBusiness', 'parent' => 'HealthAndBeautyBusiness'],
            ['label' => 'NailSalon', 'value' => 'NailSalon', 'category' => 'Organization', 'subcategory' => 'HealthAndBeautyBusiness', 'parent' => 'HealthAndBeautyBusiness'],
            ['label' => 'TattooParlor', 'value' => 'TattooParlor', 'category' => 'Organization', 'subcategory' => 'HealthAndBeautyBusiness', 'parent' => 'HealthAndBeautyBusiness'],
            ['label' => 'DryCleaningOrLaundry', 'value' => 'DryCleaningOrLaundry', 'category' => 'Organization', 'subcategory' => 'LocalBusiness', 'parent' => 'LocalBusiness'],
            
            // Emergency Services
            ['label' => 'EmergencyService', 'value' => 'EmergencyService', 'category' => 'Organization', 'subcategory' => 'EmergencyService', 'parent' => 'LocalBusiness'],
            ['label' => 'FireStation', 'value' => 'FireStation', 'category' => 'Organization', 'subcategory' => 'EmergencyService', 'parent' => 'EmergencyService'],
            ['label' => 'Hospital', 'value' => 'Hospital', 'category' => 'Organization', 'subcategory' => 'EmergencyService', 'parent' => 'EmergencyService'],
            ['label' => 'PoliceStation', 'value' => 'PoliceStation', 'category' => 'Organization', 'subcategory' => 'EmergencyService', 'parent' => 'EmergencyService'],
            
            // Recreation & Entertainment
            ['label' => 'AmusementPark', 'value' => 'AmusementPark', 'category' => 'Organization', 'subcategory' => 'EntertainmentBusiness', 'parent' => 'EntertainmentBusiness'],
            ['label' => 'ArtGallery', 'value' => 'ArtGallery', 'category' => 'Organization', 'subcategory' => 'EntertainmentBusiness', 'parent' => 'EntertainmentBusiness'],
            ['label' => 'Casino', 'value' => 'Casino', 'category' => 'Organization', 'subcategory' => 'EntertainmentBusiness', 'parent' => 'EntertainmentBusiness'],
            ['label' => 'ComedyClub', 'value' => 'ComedyClub', 'category' => 'Organization', 'subcategory' => 'EntertainmentBusiness', 'parent' => 'EntertainmentBusiness'],
            ['label' => 'MovieTheater', 'value' => 'MovieTheater', 'category' => 'Organization', 'subcategory' => 'EntertainmentBusiness', 'parent' => 'EntertainmentBusiness'],
            ['label' => 'Museum', 'value' => 'Museum', 'category' => 'Place', 'subcategory' => 'CivicStructure', 'parent' => 'CivicStructure'],
            ['label' => 'MusicVenue', 'value' => 'MusicVenue', 'category' => 'Place', 'subcategory' => 'CivicStructure', 'parent' => 'CivicStructure'],
            ['label' => 'NightClub', 'value' => 'NightClub', 'category' => 'Organization', 'subcategory' => 'EntertainmentBusiness', 'parent' => 'EntertainmentBusiness'],
            ['label' => 'BowlingAlley', 'value' => 'BowlingAlley', 'category' => 'Organization', 'subcategory' => 'SportsActivityLocation', 'parent' => 'SportsActivityLocation'],
            ['label' => 'GolfCourse', 'value' => 'GolfCourse', 'category' => 'Organization', 'subcategory' => 'SportsActivityLocation', 'parent' => 'SportsActivityLocation'],
            ['label' => 'HealthClub', 'value' => 'HealthClub', 'category' => 'Organization', 'subcategory' => 'SportsActivityLocation', 'parent' => 'SportsActivityLocation'],
            ['label' => 'PublicSwimmingPool', 'value' => 'PublicSwimmingPool', 'category' => 'Organization', 'subcategory' => 'SportsActivityLocation', 'parent' => 'SportsActivityLocation'],
            ['label' => 'SkiResort', 'value' => 'SkiResort', 'category' => 'Organization', 'subcategory' => 'SportsActivityLocation', 'parent' => 'SportsActivityLocation'],
            ['label' => 'SportsClub', 'value' => 'SportsClub', 'category' => 'Organization', 'subcategory' => 'SportsActivityLocation', 'parent' => 'SportsActivityLocation'],
            ['label' => 'StadiumOrArena', 'value' => 'StadiumOrArena', 'category' => 'Place', 'subcategory' => 'CivicStructure', 'parent' => 'CivicStructure'],
            ['label' => 'TennisComplex', 'value' => 'TennisComplex', 'category' => 'Organization', 'subcategory' => 'SportsActivityLocation', 'parent' => 'SportsActivityLocation'],
            ['label' => 'Zoo', 'value' => 'Zoo', 'category' => 'Place', 'subcategory' => 'CivicStructure', 'parent' => 'CivicStructure'],
            ['label' => 'Aquarium', 'value' => 'Aquarium', 'category' => 'Place', 'subcategory' => 'CivicStructure', 'parent' => 'CivicStructure'],
            
            // Community Services
            ['label' => 'AnimalShelter', 'value' => 'AnimalShelter', 'category' => 'Organization', 'subcategory' => 'LocalBusiness', 'parent' => 'LocalBusiness'],
            ['label' => 'ChildCare', 'value' => 'ChildCare', 'category' => 'Organization', 'subcategory' => 'LocalBusiness', 'parent' => 'LocalBusiness'],
            ['label' => 'Library', 'value' => 'Library', 'category' => 'Organization', 'subcategory' => 'LocalBusiness', 'parent' => 'LocalBusiness'],
            ['label' => 'Park', 'value' => 'Park', 'category' => 'Place', 'subcategory' => 'CivicStructure', 'parent' => 'CivicStructure'],
            ['label' => 'ParkingFacility', 'value' => 'ParkingFacility', 'category' => 'Place', 'subcategory' => 'CivicStructure', 'parent' => 'CivicStructure'],
            ['label' => 'PostOffice', 'value' => 'PostOffice', 'category' => 'Organization', 'subcategory' => 'GovernmentOffice', 'parent' => 'GovernmentOffice'],
            ['label' => 'Preschool', 'value' => 'Preschool', 'category' => 'Organization', 'subcategory' => 'EducationalOrganization', 'parent' => 'EducationalOrganization'],
            ['label' => 'School', 'value' => 'School', 'category' => 'Organization', 'subcategory' => 'EducationalOrganization', 'parent' => 'EducationalOrganization'],
            ['label' => 'VeterinaryCare', 'value' => 'VeterinaryCare', 'category' => 'Organization', 'subcategory' => 'MedicalBusiness', 'parent' => 'MedicalBusiness'],
            
            // Religious
            ['label' => 'Church', 'value' => 'Church', 'category' => 'Place', 'subcategory' => 'PlaceOfWorship', 'parent' => 'PlaceOfWorship'],
            ['label' => 'Mosque', 'value' => 'Mosque', 'category' => 'Place', 'subcategory' => 'PlaceOfWorship', 'parent' => 'PlaceOfWorship'],
            ['label' => 'Synagogue', 'value' => 'Synagogue', 'category' => 'Place', 'subcategory' => 'PlaceOfWorship', 'parent' => 'PlaceOfWorship'],
            ['label' => 'BuddhistTemple', 'value' => 'BuddhistTemple', 'category' => 'Place', 'subcategory' => 'PlaceOfWorship', 'parent' => 'PlaceOfWorship'],
            ['label' => 'HinduTemple', 'value' => 'HinduTemple', 'category' => 'Place', 'subcategory' => 'PlaceOfWorship', 'parent' => 'PlaceOfWorship'],
            ['label' => 'CatholicChurch', 'value' => 'CatholicChurch', 'category' => 'Place', 'subcategory' => 'PlaceOfWorship', 'parent' => 'PlaceOfWorship'],
            
            // Transportation
            ['label' => 'Airport', 'value' => 'Airport', 'category' => 'Place', 'subcategory' => 'CivicStructure', 'parent' => 'CivicStructure'],
            ['label' => 'BusStation', 'value' => 'BusStation', 'category' => 'Place', 'subcategory' => 'CivicStructure', 'parent' => 'CivicStructure'],
            ['label' => 'BusStop', 'value' => 'BusStop', 'category' => 'Place', 'subcategory' => 'CivicStructure', 'parent' => 'CivicStructure'],
            ['label' => 'Taxi', 'value' => 'Taxi', 'category' => 'Organization', 'subcategory' => 'LocalBusiness', 'parent' => 'LocalBusiness'],
            ['label' => 'TaxiStand', 'value' => 'TaxiStand', 'category' => 'Place', 'subcategory' => 'CivicStructure', 'parent' => 'CivicStructure'],
            ['label' => 'TrainStation', 'value' => 'TrainStation', 'category' => 'Place', 'subcategory' => 'CivicStructure', 'parent' => 'CivicStructure'],
            ['label' => 'SubwayStation', 'value' => 'SubwayStation', 'category' => 'Place', 'subcategory' => 'CivicStructure', 'parent' => 'CivicStructure'],
            
            // Civic Infrastructure
            ['label' => 'CivicStructure', 'value' => 'CivicStructure', 'category' => 'Place', 'subcategory' => 'CivicStructure', 'parent' => 'Place'],
            ['label' => 'CityHall', 'value' => 'CityHall', 'category' => 'Place', 'subcategory' => 'CivicStructure', 'parent' => 'GovernmentBuilding'],
            ['label' => 'Courthouse', 'value' => 'Courthouse', 'category' => 'Place', 'subcategory' => 'CivicStructure', 'parent' => 'GovernmentBuilding'],
            ['label' => 'DefenceEstablishment', 'value' => 'DefenceEstablishment', 'category' => 'Place', 'subcategory' => 'CivicStructure', 'parent' => 'GovernmentBuilding'],
            ['label' => 'Embassy', 'value' => 'Embassy', 'category' => 'Place', 'subcategory' => 'CivicStructure', 'parent' => 'GovernmentBuilding'],
            ['label' => 'LegislativeBuilding', 'value' => 'LegislativeBuilding', 'category' => 'Place', 'subcategory' => 'CivicStructure', 'parent' => 'GovernmentBuilding'],
            
            // Media
            ['label' => 'VideoObject', 'value' => 'VideoObject'],
            ['label' => 'ImageObject', 'value' => 'ImageObject'],
            ['label' => 'AudioObject', 'value' => 'AudioObject'],
            ['label' => 'PodcastEpisode', 'value' => 'PodcastEpisode'],
            ['label' => 'VideoGame', 'value' => 'VideoGame'],
            ['label' => 'WebSite', 'value' => 'WebSite'],
            
            // Educational
            ['label' => 'Course', 'value' => 'Course', 'category' => 'CreativeWork', 'subcategory' => 'CreativeWork', 'parent' => 'CreativeWork'],
            ['label' => 'LearningResource', 'value' => 'LearningResource', 'category' => 'CreativeWork', 'subcategory' => 'CreativeWork', 'parent' => 'CreativeWork'],
            ['label' => 'Quiz', 'value' => 'Quiz', 'category' => 'CreativeWork', 'subcategory' => 'CreativeWork', 'parent' => 'CreativeWork'],
            ['label' => 'EducationalOrganization', 'value' => 'EducationalOrganization', 'category' => 'Organization', 'subcategory' => 'EducationalOrganization', 'parent' => 'Organization'],
            
            // Medical/Health
            ['label' => 'MedicalCondition', 'value' => 'MedicalCondition', 'category' => 'MedicalEntity', 'subcategory' => 'MedicalEntity', 'parent' => 'MedicalEntity'],
            ['label' => 'MedicalWebPage', 'value' => 'MedicalWebPage', 'category' => 'CreativeWork', 'subcategory' => 'WebPage', 'parent' => 'WebPage'],
            ['label' => 'HealthAndBeautyBusiness', 'value' => 'HealthAndBeautyBusiness', 'category' => 'Organization', 'subcategory' => 'HealthAndBeautyBusiness', 'parent' => 'LocalBusiness'],
            
            // Financial
            ['label' => 'FinancialProduct', 'value' => 'FinancialProduct', 'category' => 'Service', 'subcategory' => 'Service', 'parent' => 'Service'],
            ['label' => 'FinancialService', 'value' => 'FinancialService', 'category' => 'Organization', 'subcategory' => 'FinancialService', 'parent' => 'LocalBusiness'],
            
            // Real Estate
            ['label' => 'RealEstateListing', 'value' => 'RealEstateListing'],
            ['label' => 'Apartment', 'value' => 'Apartment'],
            ['label' => 'House', 'value' => 'House'],
            
            // Automotive
            ['label' => 'Car', 'value' => 'Car'],
            ['label' => 'Vehicle', 'value' => 'Vehicle'],
            ['label' => 'AutoDealer', 'value' => 'AutoDealer'],
            
            // Travel
            ['label' => 'TouristAttraction', 'value' => 'TouristAttraction', 'category' => 'Place', 'subcategory' => 'Place', 'parent' => 'Place'],
            ['label' => 'TravelAgency', 'value' => 'TravelAgency', 'category' => 'Organization', 'subcategory' => 'LocalBusiness', 'parent' => 'LocalBusiness'],
            ['label' => 'LodgingBusiness', 'value' => 'LodgingBusiness', 'category' => 'Organization', 'subcategory' => 'LodgingBusiness', 'parent' => 'LocalBusiness'],
            
            // Entertainment
            ['label' => 'TVSeries', 'value' => 'TVSeries'],
            ['label' => 'TVEpisode', 'value' => 'TVEpisode'],
            ['label' => 'MusicAlbum', 'value' => 'MusicAlbum'],
            ['label' => 'Game', 'value' => 'Game'],
            ['label' => 'Movie', 'value' => 'Movie'],
            ['label' => 'MusicRecording', 'value' => 'MusicRecording'],
            ['label' => 'Book', 'value' => 'Book'],
            
            // Government & Non-Profit
            ['label' => 'GovernmentOrganization', 'value' => 'GovernmentOrganization', 'category' => 'Organization', 'subcategory' => 'Organization', 'parent' => 'Organization'],
            ['label' => 'GovernmentService', 'value' => 'GovernmentService', 'category' => 'Service', 'subcategory' => 'Service', 'parent' => 'Service'],
            ['label' => 'NGO', 'value' => 'NGO', 'category' => 'Organization', 'subcategory' => 'Organization', 'parent' => 'Organization'],
            
            // Sports
            ['label' => 'SportsOrganization', 'value' => 'SportsOrganization', 'category' => 'Organization', 'subcategory' => 'Organization', 'parent' => 'Organization'],
            ['label' => 'SportsTeam', 'value' => 'SportsTeam', 'category' => 'Organization', 'subcategory' => 'SportsOrganization', 'parent' => 'SportsOrganization'],
            
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

    /**
     * Get types organized by category
     * 
     * @return array Types organized by category/subcategory
     */
    public function get_categorized_types(): array
    {
        $types = $this->get_available_types();
        $categorized = [];
        
        foreach ($types as $type) {
            $category = $type['category'] ?? 'Other';
            $subcategory = $type['subcategory'] ?? 'General';
            
            if (!isset($categorized[$category])) {
                $categorized[$category] = [];
            }
            if (!isset($categorized[$category][$subcategory])) {
                $categorized[$category][$subcategory] = [];
            }
            
            $categorized[$category][$subcategory][] = $type;
        }
        
        return $categorized;
    }

    /**
     * Get only organization-relevant types
     * Filters out pure content, media, and event types
     * 
     * @return array Organization-relevant schema types
     */
    public function get_organization_types(): array
    {
        $types = $this->get_available_types();
        
        // Filter to organization-relevant types
        $org_types = array_filter($types, function($type) {
            // Include all Organization category types
            if (isset($type['category']) && $type['category'] === 'Organization') {
                return true;
            }
            
            // Include relevant Place types
            if (isset($type['category']) && $type['category'] === 'Place') {
                $relevant_place_types = [
                    'Airport', 'Aquarium', 'Beach', 'Bridge', 'BusStation', 'BusStop',
                    'Cemetery', 'Church', 'CityHall', 'Courthouse', 'DefenceEstablishment',
                    'Embassy', 'GovernmentBuilding', 'Hospital', 'Library', 'Museum',
                    'Park', 'ParkingFacility', 'PlaceOfWorship', 'PoliceStation',
                    'PostOffice', 'PublicToilet', 'StadiumOrArena', 'SubwayStation',
                    'TaxiStand', 'TrainStation', 'Zoo', 'TouristAttraction',
                    'TouristDestination', 'LandmarksOrHistoricalBuildings'
                ];
                return in_array($type['value'], $relevant_place_types);
            }
            
            // Include special cases for website identity
            $special_identity_types = ['Person', 'WebSite', 'Blog'];
            if (isset($type['value']) && in_array($type['value'], $special_identity_types)) {
                return true;
            }
            
            return false;
        });
        
        // Add special cases that might not be in the main list
        $special_cases = [
            [
                'label' => 'Person (Personal Brand)',
                'value' => 'Person',
                'category' => 'Person',
                'subcategory' => 'Person',
                'description' => 'For personal websites, portfolios, and individual professionals'
            ],
            [
                'label' => 'WebSite',
                'value' => 'WebSite',
                'category' => 'CreativeWork',
                'subcategory' => 'WebSite',
                'description' => 'Generic website schema'
            ],
            [
                'label' => 'Blog',
                'value' => 'Blog',
                'category' => 'CreativeWork',
                'subcategory' => 'Blog',
                'description' => 'For blog-focused websites'
            ]
        ];
        
        // Check if special cases are already in the list, if not add them
        $existing_values = array_column($org_types, 'value');
        foreach ($special_cases as $special_case) {
            if (!in_array($special_case['value'], $existing_values)) {
                array_unshift($org_types, $special_case);
            }
        }
        
        return array_values($org_types);
    }

    /**
     * Get organization types organized by category
     * 
     * @return array Categorized organization types
     */
    public function get_categorized_organization_types(): array
    {
        $org_types = $this->get_organization_types();
        $categorized = [];
        
        foreach ($org_types as $type) {
            $subcategory = $type['subcategory'] ?? 'General';
            
            // Map subcategories to user-friendly names
            $category_map = [
                'LocalBusiness' => 'General Business',
                'Organization' => 'Organizations',
                'AutomotiveBusiness' => 'Automotive Services',
                'EmergencyService' => 'Emergency Services',
                'EntertainmentBusiness' => 'Entertainment',
                'FinancialService' => 'Financial Services',
                'FoodEstablishment' => 'Food & Dining',
                'HealthAndBeautyBusiness' => 'Health & Beauty',
                'HomeAndConstructionBusiness' => 'Home & Construction',
                'LegalService' => 'Legal Services',
                'LodgingBusiness' => 'Lodging',
                'MedicalBusiness' => 'Medical Services',
                'Store' => 'Retail Stores',
                'SportsActivityLocation' => 'Sports & Recreation',
                'EducationalOrganization' => 'Educational',
                'GovernmentOffice' => 'Government',
                'CivicStructure' => 'Civic & Infrastructure',
                'PlaceOfWorship' => 'Religious Organizations'
            ];
            
            $display_category = $category_map[$subcategory] ?? $subcategory;
            
            if (!isset($categorized[$display_category])) {
                $categorized[$display_category] = [];
            }
            
            $categorized[$display_category][] = $type;
        }
        
        // Sort categories for better UX
        $sort_order = [
            'General Business',
            'Food & Dining',
            'Retail Stores',
            'Home & Construction',
            'Health & Beauty',
            'Medical Services',
            'Automotive Services',
            'Financial Services',
            'Legal Services',
            'Educational',
            'Sports & Recreation',
            'Entertainment',
            'Lodging',
            'Organizations',
            'Government',
            'Emergency Services',
            'Religious Organizations',
            'Civic & Infrastructure'
        ];
        
        $sorted = [];
        foreach ($sort_order as $cat) {
            if (isset($categorized[$cat])) {
                $sorted[$cat] = $categorized[$cat];
            }
        }
        
        // Add any remaining categories
        foreach ($categorized as $cat => $types) {
            if (!isset($sorted[$cat])) {
                $sorted[$cat] = $types;
            }
        }
        
        return $sorted;
    }

    /**
     * Get content-focused schema types
     * For use in post/page schema type selectors
     * 
     * @return array Content-relevant schema types
     */
    public function get_content_types(): array
    {
        $types = $this->get_available_types();
        
        // Filter to content-relevant types
        $content_types = array_filter($types, function($type) {
            // Include content and creative work types
            if (isset($type['category']) && $type['category'] === 'CreativeWork') {
                return true;
            }
            
            // Include specific product/service types
            $content_values = [
                'Product', 'Service', 'Event', 'JobPosting',
                'Recipe', 'Review', 'Course', 'Quiz',
                'FAQPage', 'HowTo', 'QAPage',
                'Person', 'Place', 'Thing'
            ];
            
            return in_array($type['value'], $content_values);
        });
        
        return array_values($content_types);
    }
}