<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class ExportPostmanCollection extends Command
{
    protected $signature = 'postman:export {path=docs/postman/Gathro-API.postman_collection.json}';

    protected $description = 'Export Postman collection with all API routes and role-based auth variables';

    public function handle(): int
    {
        $routes = Route::getRoutes();

        $collection = [
            'info' => [
                'name' => 'Gathro API',
                'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
                '_postman_id' => (string) Str::uuid(),
            ],
            'item' => []
        ];

        // Initialize top-level role folders as trees (children + requests)
        $trees = [
            'admin' => [
                'name' => 'Admin',
                'children' => [],
                'requests' => [],
                '_auth' => [ 'type' => 'bearer', 'bearer' => [ ['key' => 'token', 'value' => '{{admin_token}}', 'type' => 'string'] ] ],
                '_variable' => [ ['key' => 'base_url', 'value' => '{{admin_base_url}}'] ],
            ],
            'customer' => [
                'name' => 'Customer',
                'children' => [],
                'requests' => [],
                '_auth' => [ 'type' => 'bearer', 'bearer' => [ ['key' => 'token', 'value' => '{{customer_token}}', 'type' => 'string'] ] ],
                '_variable' => [ ['key' => 'base_url', 'value' => '{{customer_base_url}}'] ],
            ],
            'provider' => [
                'name' => 'Provider',
                'children' => [],
                'requests' => [],
                '_auth' => [ 'type' => 'bearer', 'bearer' => [ ['key' => 'token', 'value' => '{{provider_token}}', 'type' => 'string'] ] ],
                '_variable' => [ ['key' => 'base_url', 'value' => '{{provider_base_url}}'] ],
            ],
            'public' => [
                'name' => 'Public',
                'children' => [],
                'requests' => [],
                '_variable' => [ ['key' => 'base_url', 'value' => '{{guest_base_url}}'] ],
            ],
            'misc' => [
                'name' => 'Misc',
                'children' => [],
                'requests' => [],
                '_variable' => [ ['key' => 'base_url', 'value' => '{{guest_base_url}}'] ],
            ],
        ];

        foreach ($routes as $route) {
            $uri = $route->uri();
            $methods = array_values(array_diff($route->methods(), ['HEAD', 'OPTIONS']));
            if (!Str::startsWith($uri, 'api/')) continue;

            $role = $this->detectRole($uri) ?: 'misc';
            $segments = $this->resourceFoldersFromUri($uri, $role);

            foreach ($methods as $method) {
                $upper = strtoupper($method);
                $request = [
                    'name' => $upper . ' ' . $uri,
                    'request' => [
                        'method' => $upper,
                        'header' => [ [ 'key' => 'Accept', 'value' => 'application/json' ] ],
                        'url' => [
                            'raw' => '{{base_url}}/' . $uri,
                            'host' => ['{{base_url}}'],
                            'path' => explode('/', $uri),
                        ],
                    ],
                ];

                // Detailed example bodies for mutating methods
                if (in_array($upper, ['POST', 'PUT', 'PATCH'])) {
                    $request['request']['header'][] = [ 'key' => 'Content-Type', 'value' => 'application/json' ];
                    $example = $this->examplePayloadFor($uri, $upper);
                    $request['request']['body'] = [ 'mode' => 'raw', 'raw' => $example['raw'] ];
                    if (!empty($example['description'])) {
                        $request['request']['description'] = $example['description'];
                    }
                }

                // Tests script for login endpoints to set tokens automatically
                if (Str::endsWith($uri, 'auth/login')) {
                    $tokenVar = $this->tokenVarForRole($role);
                    $request['event'] = [[
                        'listen' => 'test',
                        'script' => [
                            'exec' => [
                                'const json = pm.response.json();',
                                'if (json && json.data && json.data.token) {',
                                "  pm.environment.set('{$tokenVar}', json.data.token);",
                                '}'
                            ],
                            'type' => 'text/javascript'
                        ]
                    ]];
                }

                // Add the request into nested folder tree under the role
                $this->addRequestToFolderTree($trees[$role], $segments, $request);
            }
        }

        // Transform trees to Postman items
        foreach (['admin','customer','provider','public','misc'] as $k) {
            $folder = $this->transformFolderTreeToPostman($trees[$k]);
            if (!empty($folder['item'])) $collection['item'][] = $folder;
        }

        $path = base_path($this->argument('path'));
        if (!is_dir(dirname($path))) @mkdir(dirname($path), 0777, true);
        file_put_contents($path, json_encode($collection, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE));

        $this->info('Postman collection exported to: ' . $path);
        return self::SUCCESS;
    }

    private function detectRole(string $uri): ?string
    {
        if (Str::startsWith($uri, 'api/v1/admin')) return 'admin';
        if (Str::startsWith($uri, 'api/v1/customer')) return 'customer';
        if (Str::startsWith($uri, 'api/v1/provider')) return 'provider';
        if (Str::startsWith($uri, 'api/v1/public')) return 'public';
        return null;
    }

    private function tokenVarForRole(?string $role): string
    {
        return match ($role) {
            'admin' => 'admin_token',
            'customer' => 'customer_token',
            'provider' => 'provider_token',
            default => 'guest_token',
        };
    }

    private function resourceFoldersFromUri(string $uri, string $role): array
    {
        $prefix = "api/v1/{$role}/";
        $path = Str::startsWith($uri, $prefix) ? Str::after($uri, $prefix) : ($role === 'misc' ? Str::after($uri, 'api/') : $uri);
        $parts = array_values(array_filter(explode('/', $path)));
        $folders = [];
        foreach ($parts as $p) {
            if (Str::startsWith($p, '{')) break;
            $folders[] = Str::of($p)->replace('-', ' ')->title()->toString();
        }
        return $folders;
    }

    private function addRequestToFolderTree(array &$tree, array $segments, array $request): void
    {
        if (empty($segments)) {
            $tree['requests'][] = $request;
            return;
        }
        $head = array_shift($segments);
        if (!isset($tree['children'][$head])) {
            $tree['children'][$head] = [ 'name' => $head, 'children' => [], 'requests' => [] ];
        }
        $this->addRequestToFolderTree($tree['children'][$head], $segments, $request);
    }

    private function transformFolderTreeToPostman(array $tree): array
    {
        $folder = [ 'name' => $tree['name'], 'item' => [] ];
        if (isset($tree['_auth'])) $folder['auth'] = $tree['_auth'];
        if (isset($tree['_variable'])) $folder['variable'] = $tree['_variable'];
        foreach ($tree['children'] as $child) {
            $folder['item'][] = $this->transformFolderTreeToPostman($child);
        }
        foreach ($tree['requests'] as $req) {
            $folder['item'][] = $req;
        }
        return $folder;
    }


    private function examplePayloadFor(string $uri, string $method): array
    {
        // Admin - Settlements processing
        if (Str::contains($uri, 'admin/settlements/') && Str::endsWith($uri, '/process') && $method === 'POST') {
            return [
                'raw' => json_encode([
                    'action' => 'approve',
                    'remarks' => 'Transaction verified and approved.'
                ], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE),
                'description' => "Field 'action' also accepts 'reject'."
            ];
        }

        // Admin - Settings
        if (Str::endsWith($uri, 'admin/settings/provider-payout') && $method === 'PUT') {
            return [
                'raw' => json_encode([
                    'provider_payout_trigger' => 'manual_admin_approval',
                    'escrow_system_user_id' => 1
                ], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE),
                'description' => "provider_payout_trigger also accepts 'automatic_on_completion'."
            ];
        }
        if (Str::endsWith($uri, 'admin/settings/assets') && $method === 'PUT') {
            return [
                'raw' => json_encode([
                    'app_logo_url' => 'https://cdn.gathro.com/assets/logo@2x.png',
                    'gift_background_url' => 'https://cdn.gathro.com/assets/gifts-bg.jpg'
                ], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)
            ];
        }
        if (Str::endsWith($uri, 'admin/settings/engagement') && $method === 'PUT') {
            return [
                'raw' => json_encode([
                    'review_prompt_delay_minutes' => 120
                ], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE),
                'description' => 'Controls when to prompt users for reviews after completion.'
            ];
        }

        // Admin - Banners
        if (Str::endsWith($uri, 'admin/banners') && $method === 'POST') {
            return [
                'raw' => json_encode([
                    'title' => 'Summer Festival Banner',
                    'image_url' => 'https://cdn.gathro.com/banners/summer-festival.jpg',
                    'link_url' => 'https://gathro.com/festival',
                    'placement' => 'home_top',
                    'active' => true,
                    'sort_order' => 1
                ], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE),
                'description' => "placement could be 'home_top', 'home_middle', 'home_bottom'"
            ];
        }
        if (Str::contains($uri, 'admin/banners/') && $method === 'PUT') {
            return [
                'raw' => json_encode([
                    'title' => 'Summer Festival Banner (Updated)',
                    'active' => true,
                    'sort_order' => 2
                ], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)
            ];
        }

        // Admin - Invoices
        if (Str::endsWith($uri, 'admin/invoices') && $method === 'POST') {
            return [
                'raw' => json_encode([
                    'booking_id' => 1001,
                    'customer_id' => 2001,
                    'provider_id' => 3001,
                    'items' => [
                        ['description' => 'Deluxe Spa Package', 'quantity' => 1, 'unit_price' => 150.75],
                        ['description' => 'Aromatherapy Add-on', 'quantity' => 1, 'unit_price' => 25.00]
                    ],
                    'notes' => 'Invoice generated after booking completion.'
                ], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)
            ];
        }
        if (Str::contains($uri, 'admin/invoices/') && $method === 'PUT') {
            return [
                'raw' => json_encode([
                    'status' => 'paid',
                    'notes' => 'Marked as paid after settlement release.'
                ], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE),
                'description' => "status could be 'draft', 'sent', 'paid', 'cancelled'"
            ];
        }
        if (Str::contains($uri, 'admin/invoices/') && Str::endsWith($uri, '/status') && $method === 'PUT') {
            return [
                'raw' => json_encode([
                    'status' => 'sent'
                ], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE),
                'description' => "status could be 'draft', 'sent', 'paid', 'cancelled'"
            ];
        }

        // Admin - Gift Packages
        if (Str::endsWith($uri, 'admin/gifts/packages') && $method === 'POST') {
            return [
                'raw' => json_encode([
                    'name' => 'Gold Experience Pack',
                    'description' => 'Includes 3 premium experiences and a gift card.',
                    'price' => 299.99,
                    'currency' => 'SAR',
                    'active' => true
                ], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)
            ];
        }
        if (Str::contains($uri, 'admin/gifts/packages/') && $method === 'PUT') {
            return [
                'raw' => json_encode([
                    'name' => 'Gold Experience Pack (Updated)',
                    'active' => true
                ], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)
            ];
        }

        // Admin - Roles
        if (Str::endsWith($uri, 'admin/roles') && $method === 'POST') {
            return [
                'raw' => json_encode([
                    'name' => 'Operations Manager',
                    'permissions' => ['users.view', 'bookings.manage', 'settlements.process']
                ], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)
            ];
        }
        if (Str::contains($uri, 'admin/roles/') && in_array($method, ['PUT','PATCH'])) {
            return [
                'raw' => json_encode([
                    'name' => 'Operations Manager (Updated)',
                    'permissions' => ['users.view', 'bookings.manage']
                ], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)
            ];
        }

        // Admin - Approvals (remarks required)
        if (Str::contains($uri, 'admin/approvals/providers/') && $method === 'POST') {
            return [ 'raw' => json_encode(['remarks' => 'Provider documents verified.'], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) ];
        }
        if (Str::contains($uri, 'admin/approvals/services/') && $method === 'POST') {
            return [ 'raw' => json_encode(['remarks' => 'Service quality meets standards.'], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) ];
        }

        // Customer - Gifting
        if (Str::endsWith($uri, 'customer/gifts/offer') && $method === 'POST') {
            return [
                'raw' => json_encode([
                    'recipient_public_id' => 'pub_6YH9K2',
                    'type' => 'package',
                    'gift_package_id' => 10,
                    'currency' => 'SAR',
                    'message' => 'A special gift for you!'
                ], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE),
                'description' => "type could be 'package' or 'direct' or 'voucher'"
            ];
        }
        if (preg_match('#customer/gifts/\\{gift\\}/accept$#', $uri) && $method === 'POST') {
            return [ 'raw' => json_encode(['action' => 'accept'], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) ];
        }
        if (preg_match('#customer/gifts/\\{gift\\}/reject$#', $uri) && $method === 'POST') {
            return [ 'raw' => json_encode(['action' => 'reject', 'reason' => 'Not needed now.'], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) ];
        }

        // Customer - Bookings
        if (Str::endsWith($uri, 'customer/bookings') && $method === 'POST') {
            return [
                'raw' => json_encode([
                    'service_id' => 1,
                    'start_date' => '2025-09-05T10:00:00Z',
                    'end_date' => '2025-09-07T10:00:00Z',
                    'booking_details' => [ 'adults' => 2, 'children' => 1, 'children_ages' => [7] ],
                    'payment_method' => 'wallet',
                    'currency' => 'SAR'
                ], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE),
                'description' => 'payment_method: wallet, apple_pay, visa, mada, samsung_pay, benefit, stcpay'
            ];
        }
        // Customer - Payments
        if (Str::endsWith($uri, 'customer/payments/process') && $method === 'POST') {
            return [
                'raw' => json_encode([
                    'booking_id' => 1,
                    'amount' => 500,
                    'currency' => 'SAR',
                    'payment_method' => 'visa',
                    'card_number' => '4111111111111111',
                    'expiry_date' => '12/28',
                    'cvv' => '123'
                ], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE),
                'description' => 'For apple_pay use apple_pay_token; for mada use card fields; for stcpay/benefit use phone_number+otp; for wallet omit card fields.'
            ];
        }
        // Customer - Wallet currency
        if (Str::endsWith($uri, 'customer/wallet/currency') && $method === 'POST') {
            return [ 'raw' => json_encode(['currency' => 'SAR'], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) ];
        }
        // Customer - Conversations
        if (Str::endsWith($uri, 'customer/conversations') && $method === 'POST') {
            return [ 'raw' => json_encode(['user_public_id' => 'pub_ABC123','subject' => 'سؤال عن الفعالية'], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)];
        }
        if (preg_match('#customer/conversations/\{conversation\}/messages$#', $uri) && $method === 'POST') {
            return [ 'raw' => json_encode(['message' => 'مرحبا، هل يوجد أماكن متاحة؟'], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) ];
        }
        // Public - Search services
        if (Str::endsWith($uri, 'public/search/services') && $method === 'GET') {
            return [ 'raw' => json_encode(['search' => 'يوجا','service_types' => ['event'],'min_rating' => 4], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE), 'description' => 'Use query params in Postman (shown here as body example).'];
        }
        // Default for other POST/PUT/PATCH: provide a realistic non-empty stub
        if (in_array($method, ['POST','PUT','PATCH'])) {
            return [
                'raw' => json_encode([
                    'name' => 'Sample Name',
                    'description' => 'Example request body. Replace with real endpoint fields.',
                    'active' => true,
                    'remarks' => 'Example remarks.'
                ], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE),
                'description' => 'Replace example fields with the actual model properties.'
            ];
        }

        return [ 'raw' => json_encode(new \stdClass()) ];
    }
}

