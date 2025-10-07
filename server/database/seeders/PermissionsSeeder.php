<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // Users & RBAC
            'users.view', 'users.manage',
            'roles.view', 'roles.manage',
            'permissions.view', 'permissions.manage',

            // Providers & Customers
            'providers.view', 'providers.manage',
            'customers.view',

            // Services & Catalog
            'main-services.view', 'main-services.manage',
            'categories.view', 'categories.manage',
            'services.view', 'services.manage',
            'events.view', 'events.manage',
            'catering.view', 'catering.manage',
            'restaurants.view', 'restaurants.manage',
            'properties.view', 'properties.manage',

            // Bookings & Reservations
            'bookings.view', 'bookings.manage',
            'reservations.view', 'reservations.manage',

            // Approvals
            'approvals.view', 'approvals.manage',
            'legal.docs.view', 'legal.docs.manage',

            // Financials & Commission & Invoices & Settlements
            'commission.view', 'commission.manage',
            'financial-reports.view', 'reports.view',
            'invoices.view', 'invoices.manage',
            'settlements.view', 'settlements.manage',

            // Geo & Markets
            'geo.view', 'geo.manage',

            // Support & Reviews
            'support.monitoring.view', 'support.manage',
            'reviews.view', 'reviews.manage',

            // Referrals & Points
            'referrals.view',
            'points.view', 'points.manage',

            // SMS & National ID
            'sms.manage',
            'national-id.view', 'national-id.manage',

            // Security Center
            'security.view',

            // Inter-Team Tasks & QA
            'tasks.view', 'tasks.manage',
            'qa.view', 'qa.manage',

            // Settings & Banners & Gifts
            'settings.manage',
            'banners.manage',
            'gifts.manage',

            // Realtime & Dashboards
            'realtime.view',
            'dashboards.view',

            // Content & Coupons
            'pages.view','pages.manage',
            'coupons.view','coupons.manage',
        ];

        // Create each permission for both 'api' and 'web' guards to avoid guard mismatch across environments
        $guards = ['api', 'web'];
        foreach ($permissions as $name) {
            foreach ($guards as $guard) {
                Permission::findOrCreate($name, $guard);
            }
        }
    }
}

