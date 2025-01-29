<?php

namespace App\Services;

use GuzzleHttp\Client;

class GeocodingService
{
    protected $client;

    public function __construct()
    {
        // Initialize the GuzzleHTTP client for making API requests
        $this->client = new Client();
    }

    /**
     * Get latitude and longitude from a given address using Google Maps API
     *
     * @param string $address
     * @return array|null
     */
    public function getCoordinatesFromAddress(string $address)
    {
        // Get the Google Maps API key from the environment variables
        $apiKey = env('GOOGLE_MAPS_API_KEY');

        // URL for the Google Geocoding API
        $url = 'https://maps.googleapis.com/maps/api/geocode/json';

        // Send a GET request to the Google Geocoding API
        $response = $this->client->get($url, [
            'query' => [
                'address' => $address,
                'key' => $apiKey
            ]
        ]);

        // Decode the response into an associative array
        $responseBody = json_decode($response->getBody(), true);
      
        // Check if the request was successful
        if ($responseBody['status'] === 'OK') {
            $location = $responseBody['results'][0]['geometry']['location'];
            return [
                'latitude' => $location['lat'],
                'longitude' => $location['lng'],
            ];
        }

        // If the request was not successful, return null
        return null;
    }
}
