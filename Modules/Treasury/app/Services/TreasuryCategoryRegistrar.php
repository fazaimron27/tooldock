<?php

/**
 * Treasury Category Registrar
 *
 * Registers default categories for the Treasury module including
 * transaction categories, wallet types, and goal categories.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Treasury\Services;

use App\Services\Registry\CategoryRegistry;

/**
 * Handles category registration for the Treasury module.
 *
 * Categories are organized hierarchically with parent-child relationships
 * for better organization and more granular transaction/goal tracking.
 */
class TreasuryCategoryRegistrar
{
    /**
     * Register default categories for the Treasury module.
     *
     * @param  CategoryRegistry  $categoryRegistry
     * @param  string  $moduleName
     * @return void
     */
    public function register(CategoryRegistry $categoryRegistry, string $moduleName): void
    {
        $this->registerTransactionCategories($categoryRegistry, $moduleName);
        $this->registerWalletTypes($categoryRegistry, $moduleName);
        $this->registerGoalCategories($categoryRegistry, $moduleName);
    }

    /**
     * Register transaction categories (income/expense tracking).
     *
     * @param  CategoryRegistry  $categoryRegistry
     * @param  string  $moduleName
     * @return void
     */
    private function registerTransactionCategories(CategoryRegistry $categoryRegistry, string $moduleName): void
    {
        $categoryRegistry->registerMany($moduleName, 'transaction_category', [
            ['name' => 'Salary & Wages', 'slug' => 'salary-wages', 'color' => '#10b981', 'description' => 'Employment income and wages'],
            ['name' => 'Regular Salary', 'slug' => 'regular-salary', 'parent_slug' => 'salary-wages', 'color' => '#10b981', 'description' => 'Monthly or bi-weekly salary payments'],
            ['name' => 'Overtime', 'slug' => 'overtime', 'parent_slug' => 'salary-wages', 'color' => '#10b981', 'description' => 'Extra hours worked beyond regular schedule'],
            ['name' => 'Bonus', 'slug' => 'bonus', 'parent_slug' => 'salary-wages', 'color' => '#10b981', 'description' => 'Performance or annual bonuses'],
            ['name' => 'Commission', 'slug' => 'commission', 'parent_slug' => 'salary-wages', 'color' => '#10b981', 'description' => 'Sales or performance-based commissions'],

            ['name' => 'Business Income', 'slug' => 'business-income', 'color' => '#22c55e', 'description' => 'Self-employment and business earnings'],
            ['name' => 'Freelance', 'slug' => 'freelance', 'parent_slug' => 'business-income', 'color' => '#22c55e', 'description' => 'Freelance project payments'],
            ['name' => 'Side Business', 'slug' => 'side-business', 'parent_slug' => 'business-income', 'color' => '#22c55e', 'description' => 'Income from side business ventures'],
            ['name' => 'Consulting', 'slug' => 'consulting', 'parent_slug' => 'business-income', 'color' => '#22c55e', 'description' => 'Professional consulting fees'],

            ['name' => 'Investment Returns', 'slug' => 'investment-returns', 'color' => '#6366f1', 'description' => 'Returns from investments and assets'],
            ['name' => 'Dividends', 'slug' => 'dividends', 'parent_slug' => 'investment-returns', 'color' => '#6366f1', 'description' => 'Stock dividend payments'],
            ['name' => 'Interest Income', 'slug' => 'interest-income', 'parent_slug' => 'investment-returns', 'color' => '#6366f1', 'description' => 'Interest from savings or bonds'],
            ['name' => 'Capital Gains', 'slug' => 'capital-gains', 'parent_slug' => 'investment-returns', 'color' => '#6366f1', 'description' => 'Profits from selling investments'],
            ['name' => 'Rental Income', 'slug' => 'rental-income', 'parent_slug' => 'investment-returns', 'color' => '#6366f1', 'description' => 'Income from property rentals'],

            ['name' => 'Other Income', 'slug' => 'other-income', 'color' => '#84cc16', 'description' => 'Miscellaneous income sources'],
            ['name' => 'Gifts Received', 'slug' => 'gifts-received', 'parent_slug' => 'other-income', 'color' => '#84cc16', 'description' => 'Money received as gifts'],
            ['name' => 'Refunds', 'slug' => 'refunds', 'parent_slug' => 'other-income', 'color' => '#84cc16', 'description' => 'Product or service refunds'],
            ['name' => 'Tax Refunds', 'slug' => 'tax-refunds', 'parent_slug' => 'other-income', 'color' => '#84cc16', 'description' => 'Government tax refunds'],
            ['name' => 'Lottery & Winnings', 'slug' => 'lottery-winnings', 'parent_slug' => 'other-income', 'color' => '#84cc16', 'description' => 'Lottery, gambling, or prize winnings'],
            ['name' => 'Inheritance', 'slug' => 'inheritance', 'parent_slug' => 'other-income', 'color' => '#84cc16', 'description' => 'Inherited money or assets'],

            ['name' => 'Food & Dining', 'slug' => 'food-dining', 'color' => '#ef4444', 'description' => 'Food and dining expenses'],
            ['name' => 'Groceries', 'slug' => 'groceries', 'parent_slug' => 'food-dining', 'color' => '#ef4444', 'description' => 'Supermarket and grocery shopping'],
            ['name' => 'Restaurants', 'slug' => 'restaurants', 'parent_slug' => 'food-dining', 'color' => '#ef4444', 'description' => 'Dining out at restaurants'],
            ['name' => 'Coffee & Snacks', 'slug' => 'coffee-snacks', 'parent_slug' => 'food-dining', 'color' => '#ef4444', 'description' => 'Coffee shops and snack purchases'],
            ['name' => 'Food Delivery', 'slug' => 'food-delivery', 'parent_slug' => 'food-dining', 'color' => '#ef4444', 'description' => 'Food delivery services'],

            ['name' => 'Transportation', 'slug' => 'transportation', 'color' => '#f59e0b', 'description' => 'Travel and transportation costs'],
            ['name' => 'Fuel', 'slug' => 'fuel', 'parent_slug' => 'transportation', 'color' => '#f59e0b', 'description' => 'Gasoline and fuel costs'],
            ['name' => 'Public Transport', 'slug' => 'public-transport', 'parent_slug' => 'transportation', 'color' => '#f59e0b', 'description' => 'Bus, train, subway fares'],
            ['name' => 'Parking', 'slug' => 'parking', 'parent_slug' => 'transportation', 'color' => '#f59e0b', 'description' => 'Parking fees'],
            ['name' => 'Tolls', 'slug' => 'tolls', 'parent_slug' => 'transportation', 'color' => '#f59e0b', 'description' => 'Highway and bridge tolls'],
            ['name' => 'Ride-sharing', 'slug' => 'ride-sharing', 'parent_slug' => 'transportation', 'color' => '#f59e0b', 'description' => 'Uber, Grab, taxi services'],
            ['name' => 'Vehicle Maintenance', 'slug' => 'vehicle-maintenance', 'parent_slug' => 'transportation', 'color' => '#f59e0b', 'description' => 'Car repairs and maintenance'],

            ['name' => 'Housing', 'slug' => 'housing', 'color' => '#a855f7', 'description' => 'Housing and property expenses'],
            ['name' => 'Rent', 'slug' => 'rent', 'parent_slug' => 'housing', 'color' => '#a855f7', 'description' => 'Monthly rent payments'],
            ['name' => 'Mortgage', 'slug' => 'mortgage', 'parent_slug' => 'housing', 'color' => '#a855f7', 'description' => 'Mortgage loan payments'],
            ['name' => 'Property Tax', 'slug' => 'property-tax', 'parent_slug' => 'housing', 'color' => '#a855f7', 'description' => 'Property tax payments'],
            ['name' => 'Home Insurance', 'slug' => 'home-insurance', 'parent_slug' => 'housing', 'color' => '#a855f7', 'description' => 'Home insurance premiums'],
            ['name' => 'Home Maintenance', 'slug' => 'home-maintenance', 'parent_slug' => 'housing', 'color' => '#a855f7', 'description' => 'Home repairs and maintenance'],

            ['name' => 'Bills & Utilities', 'slug' => 'bills-utilities', 'color' => '#3b82f6', 'description' => 'Recurring bills and utilities'],
            ['name' => 'Electricity', 'slug' => 'electricity', 'parent_slug' => 'bills-utilities', 'color' => '#3b82f6', 'description' => 'Electric utility bills'],
            ['name' => 'Water', 'slug' => 'water', 'parent_slug' => 'bills-utilities', 'color' => '#3b82f6', 'description' => 'Water utility bills'],
            ['name' => 'Gas', 'slug' => 'gas', 'parent_slug' => 'bills-utilities', 'color' => '#3b82f6', 'description' => 'Natural gas utility bills'],
            ['name' => 'Internet', 'slug' => 'internet', 'parent_slug' => 'bills-utilities', 'color' => '#3b82f6', 'description' => 'Internet service bills'],
            ['name' => 'Phone', 'slug' => 'phone', 'parent_slug' => 'bills-utilities', 'color' => '#3b82f6', 'description' => 'Mobile and landline phone bills'],
            ['name' => 'TV & Streaming', 'slug' => 'tv-streaming', 'parent_slug' => 'bills-utilities', 'color' => '#3b82f6', 'description' => 'Cable TV and streaming subscriptions'],

            ['name' => 'Shopping', 'slug' => 'shopping', 'color' => '#ec4899', 'description' => 'General shopping expenses'],
            ['name' => 'Clothing & Accessories', 'slug' => 'clothing', 'parent_slug' => 'shopping', 'color' => '#ec4899', 'description' => 'Clothes, shoes, and accessories'],
            ['name' => 'Electronics', 'slug' => 'electronics', 'parent_slug' => 'shopping', 'color' => '#ec4899', 'description' => 'Electronic devices and gadgets'],
            ['name' => 'Home & Garden', 'slug' => 'home-garden', 'parent_slug' => 'shopping', 'color' => '#ec4899', 'description' => 'Home decor and garden supplies'],
            ['name' => 'Personal Care', 'slug' => 'personal-care', 'parent_slug' => 'shopping', 'color' => '#ec4899', 'description' => 'Toiletries and personal care products'],

            ['name' => 'Entertainment', 'slug' => 'entertainment', 'color' => '#8b5cf6', 'description' => 'Leisure and entertainment'],
            ['name' => 'Movies & Shows', 'slug' => 'movies-shows', 'parent_slug' => 'entertainment', 'color' => '#8b5cf6', 'description' => 'Cinema tickets and show admissions'],
            ['name' => 'Games', 'slug' => 'games', 'parent_slug' => 'entertainment', 'color' => '#8b5cf6', 'description' => 'Video games and gaming'],
            ['name' => 'Hobbies', 'slug' => 'hobbies', 'parent_slug' => 'entertainment', 'color' => '#8b5cf6', 'description' => 'Hobby-related expenses'],
            ['name' => 'Sports & Fitness', 'slug' => 'sports-fitness', 'parent_slug' => 'entertainment', 'color' => '#8b5cf6', 'description' => 'Gym memberships and sports activities'],
            ['name' => 'Events & Concerts', 'slug' => 'events-concerts', 'parent_slug' => 'entertainment', 'color' => '#8b5cf6', 'description' => 'Concert and event tickets'],

            ['name' => 'Healthcare', 'slug' => 'healthcare', 'color' => '#14b8a6', 'description' => 'Health and medical expenses'],
            ['name' => 'Doctor & Hospital', 'slug' => 'doctor-hospital', 'parent_slug' => 'healthcare', 'color' => '#14b8a6', 'description' => 'Medical consultations and hospital bills'],
            ['name' => 'Pharmacy', 'slug' => 'pharmacy', 'parent_slug' => 'healthcare', 'color' => '#14b8a6', 'description' => 'Medications and pharmacy purchases'],
            ['name' => 'Health Insurance', 'slug' => 'health-insurance', 'parent_slug' => 'healthcare', 'color' => '#14b8a6', 'description' => 'Health insurance premiums'],
            ['name' => 'Dental', 'slug' => 'dental', 'parent_slug' => 'healthcare', 'color' => '#14b8a6', 'description' => 'Dental care and treatments'],
            ['name' => 'Eye Care', 'slug' => 'eye-care', 'parent_slug' => 'healthcare', 'color' => '#14b8a6', 'description' => 'Eye exams and eyewear'],

            ['name' => 'Education', 'slug' => 'education', 'color' => '#06b6d4', 'description' => 'Education-related expenses'],
            ['name' => 'Tuition', 'slug' => 'tuition', 'parent_slug' => 'education', 'color' => '#06b6d4', 'description' => 'School and university tuition fees'],
            ['name' => 'Books & Supplies', 'slug' => 'books-supplies', 'parent_slug' => 'education', 'color' => '#06b6d4', 'description' => 'Textbooks and school supplies'],
            ['name' => 'Courses & Training', 'slug' => 'courses-training', 'parent_slug' => 'education', 'color' => '#06b6d4', 'description' => 'Online courses and professional training'],

            ['name' => 'Travel', 'slug' => 'travel', 'color' => '#0ea5e9', 'description' => 'Travel and vacation expenses'],
            ['name' => 'Flights', 'slug' => 'flights', 'parent_slug' => 'travel', 'color' => '#0ea5e9', 'description' => 'Airfare and flight tickets'],
            ['name' => 'Hotels & Accommodation', 'slug' => 'hotels-accommodation', 'parent_slug' => 'travel', 'color' => '#0ea5e9', 'description' => 'Hotel stays and accommodations'],
            ['name' => 'Travel Activities', 'slug' => 'travel-activities', 'parent_slug' => 'travel', 'color' => '#0ea5e9', 'description' => 'Tours, attractions, and activities'],
            ['name' => 'Travel Insurance', 'slug' => 'travel-insurance', 'parent_slug' => 'travel', 'color' => '#0ea5e9', 'description' => 'Travel insurance coverage'],

            ['name' => 'Financial', 'slug' => 'financial', 'color' => '#64748b', 'description' => 'Financial fees and obligations'],
            ['name' => 'Bank Fees', 'slug' => 'bank-fees', 'parent_slug' => 'financial', 'color' => '#64748b', 'description' => 'Bank service fees and charges'],
            ['name' => 'Loan Interest', 'slug' => 'loan-interest', 'parent_slug' => 'financial', 'color' => '#64748b', 'description' => 'Interest payments on loans'],
            ['name' => 'Insurance Premium', 'slug' => 'insurance-premium', 'parent_slug' => 'financial', 'color' => '#64748b', 'description' => 'Insurance premium payments'],
            ['name' => 'Taxes', 'slug' => 'taxes', 'parent_slug' => 'financial', 'color' => '#64748b', 'description' => 'Income tax and other taxes'],

            ['name' => 'Personal', 'slug' => 'personal', 'color' => '#f472b6', 'description' => 'Personal and social expenses'],
            ['name' => 'Gifts Given', 'slug' => 'gifts-given', 'parent_slug' => 'personal', 'color' => '#f472b6', 'description' => 'Gifts for others'],
            ['name' => 'Donations', 'slug' => 'donations', 'parent_slug' => 'personal', 'color' => '#f472b6', 'description' => 'Charitable donations'],
            ['name' => 'Subscriptions', 'slug' => 'subscriptions', 'parent_slug' => 'personal', 'color' => '#f472b6', 'description' => 'Magazine, app, and service subscriptions'],
            ['name' => 'Pets', 'slug' => 'pets', 'parent_slug' => 'personal', 'color' => '#f472b6', 'description' => 'Pet food, vet, and supplies'],

            ['name' => 'Other Expense', 'slug' => 'other-expense', 'color' => '#78716c', 'description' => 'Uncategorized expenses'],
            ['name' => 'Miscellaneous', 'slug' => 'miscellaneous', 'parent_slug' => 'other-expense', 'color' => '#78716c', 'description' => 'Other miscellaneous expenses'],

            ['name' => 'Initial Balance', 'slug' => 'initial-balance', 'color' => '#8b5cf6', 'description' => 'Opening balance for wallets'],
            ['name' => 'Adjustment', 'slug' => 'adjustment', 'color' => '#64748b', 'description' => 'Balance adjustments'],
            ['name' => 'Goal Allocation', 'slug' => 'goal-allocation', 'color' => '#14b8a6', 'description' => 'Fund allocation to savings goals'],
            ['name' => 'Transfer', 'slug' => 'transfer', 'color' => '#0284c7', 'description' => 'Transfer between wallets'],
        ]);
    }

    /**
     * Register wallet type categories (for classifying wallet types).
     *
     * @param  CategoryRegistry  $categoryRegistry
     * @param  string  $moduleName
     * @return void
     */
    private function registerWalletTypes(CategoryRegistry $categoryRegistry, string $moduleName): void
    {
        $categoryRegistry->registerMany($moduleName, 'wallet_type', [
            ['name' => 'Cash', 'slug' => 'cash', 'color' => '#22c55e', 'description' => 'Physical cash wallet'],
            ['name' => 'Bank Account', 'slug' => 'bank', 'color' => '#3b82f6', 'description' => 'Bank account'],
            ['name' => 'E-Wallet', 'slug' => 'ewallet', 'color' => '#8b5cf6', 'description' => 'Digital wallet (e.g., PayPal, GoPay)'],
            ['name' => 'Investment', 'slug' => 'investment', 'color' => '#f59e0b', 'description' => 'Investment account'],
            ['name' => 'Savings', 'slug' => 'savings', 'color' => '#06b6d4', 'description' => 'Savings account'],
            ['name' => 'Credit Card', 'slug' => 'credit-card', 'color' => '#ef4444', 'description' => 'Credit card account'],
        ]);
    }

    /**
     * Register goal categories (for categorizing saving goals).
     * Slugs match Lucide icon names (kebab-case -> PascalCase in frontend).
     *
     * @param  CategoryRegistry  $categoryRegistry
     * @param  string  $moduleName
     * @return void
     */
    private function registerGoalCategories(CategoryRegistry $categoryRegistry, string $moduleName): void
    {
        $categoryRegistry->registerMany($moduleName, 'goal', [
            ['name' => 'Emergency & Security', 'slug' => 'shield', 'color' => '#ef4444'],
            ['name' => 'Emergency Fund', 'slug' => 'shield-check', 'parent_slug' => 'shield', 'color' => '#ef4444', 'description' => 'Emergency fund for unexpected expenses'],
            ['name' => 'Insurance Fund', 'slug' => 'shield-plus', 'parent_slug' => 'shield', 'color' => '#ef4444', 'description' => 'Insurance payments and coverage'],
            ['name' => 'Job Loss Fund', 'slug' => 'briefcase', 'parent_slug' => 'shield', 'color' => '#ef4444', 'description' => 'Safety net for job loss'],

            ['name' => 'Home & Property', 'slug' => 'home', 'color' => '#f59e0b'],
            ['name' => 'Down Payment', 'slug' => 'key', 'parent_slug' => 'home', 'color' => '#f59e0b', 'description' => 'Home down payment'],
            ['name' => 'Home Renovation', 'slug' => 'hammer', 'parent_slug' => 'home', 'color' => '#f59e0b', 'description' => 'Home renovation and improvement'],
            ['name' => 'Furniture', 'slug' => 'armchair', 'parent_slug' => 'home', 'color' => '#f59e0b', 'description' => 'Furniture and home decor'],
            ['name' => 'New Home', 'slug' => 'building', 'parent_slug' => 'home', 'color' => '#f59e0b', 'description' => 'New home purchase'],

            ['name' => 'Transportation', 'slug' => 'car', 'color' => '#3b82f6'],
            ['name' => 'Car Purchase', 'slug' => 'car-front', 'parent_slug' => 'car', 'color' => '#3b82f6', 'description' => 'New or used car purchase'],
            ['name' => 'Motorcycle', 'slug' => 'bike', 'parent_slug' => 'car', 'color' => '#3b82f6', 'description' => 'Motorcycle purchase'],
            ['name' => 'Bicycle', 'slug' => 'bicycle', 'parent_slug' => 'car', 'color' => '#3b82f6', 'description' => 'Bicycle purchase'],

            ['name' => 'Travel & Leisure', 'slug' => 'plane', 'color' => '#06b6d4'],
            ['name' => 'Vacation', 'slug' => 'palm-tree', 'parent_slug' => 'plane', 'color' => '#06b6d4', 'description' => 'Vacation and holiday trips'],
            ['name' => 'Adventure Trip', 'slug' => 'mountain', 'parent_slug' => 'plane', 'color' => '#06b6d4', 'description' => 'Adventure and outdoor trips'],
            ['name' => 'Staycation', 'slug' => 'hotel', 'parent_slug' => 'plane', 'color' => '#06b6d4', 'description' => 'Local getaways and staycations'],
            ['name' => 'Bucket List', 'slug' => 'map-pin', 'parent_slug' => 'plane', 'color' => '#06b6d4', 'description' => 'Dream destinations'],

            ['name' => 'Education & Growth', 'slug' => 'graduation-cap', 'color' => '#8b5cf6'],
            ['name' => 'College/University', 'slug' => 'school', 'parent_slug' => 'graduation-cap', 'color' => '#8b5cf6', 'description' => 'Higher education tuition'],
            ['name' => 'Professional Certification', 'slug' => 'award', 'parent_slug' => 'graduation-cap', 'color' => '#8b5cf6', 'description' => 'Professional certifications'],
            ['name' => 'Language Course', 'slug' => 'languages', 'parent_slug' => 'graduation-cap', 'color' => '#8b5cf6', 'description' => 'Language learning'],
            ['name' => 'Workshop & Training', 'slug' => 'presentation', 'parent_slug' => 'graduation-cap', 'color' => '#8b5cf6', 'description' => 'Workshops and training programs'],

            ['name' => 'Life Events', 'slug' => 'heart', 'color' => '#ec4899'],
            ['name' => 'Wedding', 'slug' => 'rings', 'parent_slug' => 'heart', 'color' => '#ec4899', 'description' => 'Wedding expenses'],
            ['name' => 'Baby & Family', 'slug' => 'baby', 'parent_slug' => 'heart', 'color' => '#ec4899', 'description' => 'Baby and family planning'],
            ['name' => 'Retirement', 'slug' => 'sunset', 'parent_slug' => 'heart', 'color' => '#ec4899', 'description' => 'Retirement fund'],
            ['name' => 'Anniversary', 'slug' => 'cake', 'parent_slug' => 'heart', 'color' => '#ec4899', 'description' => 'Anniversary celebrations'],

            ['name' => 'Technology', 'slug' => 'smartphone', 'color' => '#6366f1'],
            ['name' => 'New Phone', 'slug' => 'phone', 'parent_slug' => 'smartphone', 'color' => '#6366f1', 'description' => 'Smartphone purchase'],
            ['name' => 'Laptop/Computer', 'slug' => 'laptop', 'parent_slug' => 'smartphone', 'color' => '#6366f1', 'description' => 'Laptop or computer purchase'],
            ['name' => 'Gaming Setup', 'slug' => 'gamepad-2', 'parent_slug' => 'smartphone', 'color' => '#6366f1', 'description' => 'Gaming console and accessories'],
            ['name' => 'Smart Home', 'slug' => 'router', 'parent_slug' => 'smartphone', 'color' => '#6366f1', 'description' => 'Smart home devices'],

            ['name' => 'Health & Medical', 'slug' => 'heart-pulse', 'color' => '#10b981'],
            ['name' => 'Medical Procedure', 'slug' => 'stethoscope', 'parent_slug' => 'heart-pulse', 'color' => '#10b981', 'description' => 'Medical procedures and surgeries'],
            ['name' => 'Fitness Equipment', 'slug' => 'dumbbell', 'parent_slug' => 'heart-pulse', 'color' => '#10b981', 'description' => 'Fitness and gym equipment'],
            ['name' => 'Spa & Wellness', 'slug' => 'sparkles', 'parent_slug' => 'heart-pulse', 'color' => '#10b981', 'description' => 'Spa and wellness treatments'],

            ['name' => 'Business & Investment', 'slug' => 'trending-up', 'color' => '#f97316'],
            ['name' => 'Startup Capital', 'slug' => 'rocket', 'parent_slug' => 'trending-up', 'color' => '#f97316', 'description' => 'Business startup capital'],
            ['name' => 'Investment Fund', 'slug' => 'coins', 'parent_slug' => 'trending-up', 'color' => '#f97316', 'description' => 'Investment portfolio'],
            ['name' => 'Side Project', 'slug' => 'lightbulb', 'parent_slug' => 'trending-up', 'color' => '#f97316', 'description' => 'Side project funding'],

            ['name' => 'Other', 'slug' => 'target', 'color' => '#6b7280'],
            ['name' => 'General Savings', 'slug' => 'piggy-bank', 'parent_slug' => 'target', 'color' => '#6b7280', 'description' => 'General savings goal'],
            ['name' => 'Miscellaneous', 'slug' => 'box', 'parent_slug' => 'target', 'color' => '#6b7280', 'description' => 'Other goals'],
        ]);
    }
}
