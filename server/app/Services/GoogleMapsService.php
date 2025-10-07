<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleMapsService
{
    private string $apiKey;
    private string $baseUrl = 'https://maps.googleapis.com/maps/api';

    public function __construct()
    {
        $this->apiKey = (string) (config('services.google.maps_api_key') ?? '');
    }

    /**
     * الحصول على إحداثيات من العنوان
     */
    public function geocode(string $address): array
    {
        try {
            $response = Http::get($this->baseUrl . '/geocode/json', [
                'address' => $address,
                'key' => $this->apiKey,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                if ($data['status'] === 'OK' && !empty($data['results'])) {
                    $location = $data['results'][0]['geometry']['location'];
                    
                    return [
                        'success' => true,
                        'latitude' => $location['lat'],
                        'longitude' => $location['lng'],
                        'formatted_address' => $data['results'][0]['formatted_address'],
                        'place_id' => $data['results'][0]['place_id'],
                    ];
                } else {
                    return [
                        'success' => false,
                        'error' => 'لم يتم العثور على العنوان',
                        'status' => $data['status']
                    ];
                }
            } else {
                return [
                    'success' => false,
                    'error' => 'فشل في الاتصال بخدمة Google Maps'
                ];
            }
        } catch (\Exception $e) {
            Log::error('Google Maps geocoding error', [
                'address' => $address,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'خطأ في خدمة الخرائط: ' . $e->getMessage()
            ];
        }
    }

    /**
     * الحصول على العنوان من الإحداثيات
     */
    public function reverseGeocode(float $latitude, float $longitude): array
    {
        try {
            $response = Http::get($this->baseUrl . '/geocode/json', [
                'latlng' => "{$latitude},{$longitude}",
                'key' => $this->apiKey,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                if ($data['status'] === 'OK' && !empty($data['results'])) {
                    $result = $data['results'][0];
                    
                    return [
                        'success' => true,
                        'formatted_address' => $result['formatted_address'],
                        'place_id' => $result['place_id'],
                        'address_components' => $this->parseAddressComponents($result['address_components']),
                    ];
                } else {
                    return [
                        'success' => false,
                        'error' => 'لم يتم العثور على العنوان',
                        'status' => $data['status']
                    ];
                }
            } else {
                return [
                    'success' => false,
                    'error' => 'فشل في الاتصال بخدمة Google Maps'
                ];
            }
        } catch (\Exception $e) {
            Log::error('Google Maps reverse geocoding error', [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'خطأ في خدمة الخرائط: ' . $e->getMessage()
            ];
        }
    }

    /**
     * حساب المسافة بين نقطتين
     */
    public function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2, string $unit = 'km'): array
    {
        try {
            $response = Http::get($this->baseUrl . '/distancematrix/json', [
                'origins' => "{$lat1},{$lng1}",
                'destinations' => "{$lat2},{$lng2}",
                'units' => 'metric',
                'key' => $this->apiKey,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                if ($data['status'] === 'OK' && !empty($data['rows'][0]['elements'])) {
                    $element = $data['rows'][0]['elements'][0];
                    
                    if ($element['status'] === 'OK') {
                        $distance = $element['distance']['value']; // بالمتر
                        $duration = $element['duration']['value']; // بالثواني
                        
                        if ($unit === 'km') {
                            $distance = $distance / 1000;
                        }
                        
                        return [
                            'success' => true,
                            'distance' => $distance,
                            'distance_text' => $element['distance']['text'],
                            'duration' => $duration,
                            'duration_text' => $element['duration']['text'],
                            'unit' => $unit,
                        ];
                    } else {
                        return [
                            'success' => false,
                            'error' => 'لا يمكن حساب المسافة',
                            'status' => $element['status']
                        ];
                    }
                } else {
                    return [
                        'success' => false,
                        'error' => 'فشل في حساب المسافة',
                        'status' => $data['status']
                    ];
                }
            } else {
                return [
                    'success' => false,
                    'error' => 'فشل في الاتصال بخدمة Google Maps'
                ];
            }
        } catch (\Exception $e) {
            Log::error('Google Maps distance calculation error', [
                'lat1' => $lat1,
                'lng1' => $lng1,
                'lat2' => $lat2,
                'lng2' => $lng2,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'خطأ في حساب المسافة: ' . $e->getMessage()
            ];
        }
    }

    /**
     * البحث عن الأماكن القريبة
     */
    public function findNearbyPlaces(float $latitude, float $longitude, string $type = '', int $radius = 5000): array
    {
        try {
            $params = [
                'location' => "{$latitude},{$longitude}",
                'radius' => $radius,
                'key' => $this->apiKey,
            ];

            if (!empty($type)) {
                $params['type'] = $type;
            }

            $response = Http::get($this->baseUrl . '/place/nearbysearch/json', $params);

            if ($response->successful()) {
                $data = $response->json();
                
                if ($data['status'] === 'OK') {
                    return [
                        'success' => true,
                        'places' => $data['results'],
                        'next_page_token' => $data['next_page_token'] ?? null,
                    ];
                } else {
                    return [
                        'success' => false,
                        'error' => 'فشل في البحث عن الأماكن',
                        'status' => $data['status']
                    ];
                }
            } else {
                return [
                    'success' => false,
                    'error' => 'فشل في الاتصال بخدمة Google Maps'
                ];
            }
        } catch (\Exception $e) {
            Log::error('Google Maps nearby places error', [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'type' => $type,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'خطأ في البحث عن الأماكن: ' . $e->getMessage()
            ];
        }
    }

    /**
     * البحث عن الأماكن
     */
    public function searchPlaces(string $query, float $latitude = null, float $longitude = null, int $radius = 5000): array
    {
        try {
            $params = [
                'query' => $query,
                'key' => $this->apiKey,
            ];

            if ($latitude && $longitude) {
                $params['location'] = "{$latitude},{$longitude}";
                $params['radius'] = $radius;
            }

            $response = Http::get($this->baseUrl . '/place/textsearch/json', $params);

            if ($response->successful()) {
                $data = $response->json();
                
                if ($data['status'] === 'OK') {
                    return [
                        'success' => true,
                        'places' => $data['results'],
                        'next_page_token' => $data['next_page_token'] ?? null,
                    ];
                } else {
                    return [
                        'success' => false,
                        'error' => 'فشل في البحث عن الأماكن',
                        'status' => $data['status']
                    ];
                }
            } else {
                return [
                    'success' => false,
                    'error' => 'فشل في الاتصال بخدمة Google Maps'
                ];
            }
        } catch (\Exception $e) {
            Log::error('Google Maps place search error', [
                'query' => $query,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'خطأ في البحث عن الأماكن: ' . $e->getMessage()
            ];
        }
    }

    /**
     * الحصول على تفاصيل مكان
     */
    public function getPlaceDetails(string $placeId): array
    {
        try {
            $response = Http::get($this->baseUrl . '/place/details/json', [
                'place_id' => $placeId,
                'fields' => 'name,formatted_address,geometry,place_id,types,photos,rating,user_ratings_total,opening_hours,website,formatted_phone_number',
                'key' => $this->apiKey,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                if ($data['status'] === 'OK') {
                    return [
                        'success' => true,
                        'place' => $data['result'],
                    ];
                } else {
                    return [
                        'success' => false,
                        'error' => 'فشل في جلب تفاصيل المكان',
                        'status' => $data['status']
                    ];
                }
            } else {
                return [
                    'success' => false,
                    'error' => 'فشل في الاتصال بخدمة Google Maps'
                ];
            }
        } catch (\Exception $e) {
            Log::error('Google Maps place details error', [
                'place_id' => $placeId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'خطأ في جلب تفاصيل المكان: ' . $e->getMessage()
            ];
        }
    }

    /**
     * الحصول على اتجاهات
     */
    public function getDirections(float $originLat, float $originLng, float $destLat, float $destLng, string $mode = 'driving'): array
    {
        try {
            $response = Http::get($this->baseUrl . '/directions/json', [
                'origin' => "{$originLat},{$originLng}",
                'destination' => "{$destLat},{$destLng}",
                'mode' => $mode,
                'key' => $this->apiKey,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                if ($data['status'] === 'OK' && !empty($data['routes'])) {
                    $route = $data['routes'][0];
                    $leg = $route['legs'][0];
                    
                    return [
                        'success' => true,
                        'distance' => $leg['distance']['text'],
                        'duration' => $leg['duration']['text'],
                        'steps' => $leg['steps'],
                        'polyline' => $route['overview_polyline']['points'],
                    ];
                } else {
                    return [
                        'success' => false,
                        'error' => 'فشل في جلب الاتجاهات',
                        'status' => $data['status']
                    ];
                }
            } else {
                return [
                    'success' => false,
                    'error' => 'فشل في الاتصال بخدمة Google Maps'
                ];
            }
        } catch (\Exception $e) {
            Log::error('Google Maps directions error', [
                'origin' => "{$originLat},{$originLng}",
                'destination' => "{$destLat},{$destLng}",
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'خطأ في جلب الاتجاهات: ' . $e->getMessage()
            ];
        }
    }

    /**
     * تحليل مكونات العنوان
     */
    private function parseAddressComponents(array $components): array
    {
        $parsed = [];
        
        foreach ($components as $component) {
            $types = $component['types'];
            
            if (in_array('street_number', $types)) {
                $parsed['street_number'] = $component['long_name'];
            } elseif (in_array('route', $types)) {
                $parsed['street'] = $component['long_name'];
            } elseif (in_array('locality', $types)) {
                $parsed['city'] = $component['long_name'];
            } elseif (in_array('administrative_area_level_1', $types)) {
                $parsed['state'] = $component['long_name'];
            } elseif (in_array('country', $types)) {
                $parsed['country'] = $component['long_name'];
            } elseif (in_array('postal_code', $types)) {
                $parsed['postal_code'] = $component['long_name'];
            }
        }
        
        return $parsed;
    }

    /**
     * التحقق من صحة API Key
     */
    public function validateApiKey(): bool
    {
        if (empty($this->apiKey)) {
            return false;
        }

        // اختبار بسيط باستخدام geocoding
        $testResult = $this->geocode('Riyadh, Saudi Arabia');
        return $testResult['success'];
    }
}
