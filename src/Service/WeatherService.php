<?php
declare(strict_types=1);

namespace App\Service;

use App\Service\Exception\CityNotFoundException;
use App\Service\Exception\UpstreamInvalidResponseException;
use App\Service\Exception\UpstreamUnavailableException;
use App\Service\Mapper\WeatherResponseMapper;
use Cake\Cache\Cache;
use Cake\Http\Client;
use Cake\Log\Log;

class WeatherService
{
    private const CACHE_CONFIG = 'weather';
    private const CACHE_TTL_SECONDS = 600; // 10 min
    private const HTTP_TIMEOUT_SECONDS = 5;

    private const GEO_URL = 'https://geocoding-api.open-meteo.com/v1/search?name=%s&count=1&language=pt&format=json';
    private const FORECAST_URL = 'https://api.open-meteo.com/v1/forecast?latitude=%s&longitude=%s&current=temperature_2m,wind_speed_10m&timezone=America/Sao_Paulo';

    public function fetchByCity(string $city): array
    {
        $city = trim($city);
        if ($city === '') {
            throw new CityNotFoundException('Informe uma cidade válida.');
        }

        $cacheKey = 'weather_' . mb_strtolower($city);
        $cached = Cache::read($cacheKey, self::CACHE_CONFIG);
        if (is_array($cached)) {
            return $cached + ['cache' => true];
        }

        $http = new Client(['timeout' => self::HTTP_TIMEOUT_SECONDS]);

        // 1) Geocoding
        $geoUrl = sprintf(self::GEO_URL, rawurlencode($city));

        try {
            $geoResponse = $http->get($geoUrl);
        } catch (\Throwable $e) {
            Log::error('Falha no geocoding: ' . $e->getMessage());
            throw new UpstreamUnavailableException('Serviço de geolocalização indisponível.');
        }

        if ($geoResponse->getStatusCode() !== 200) {
            Log::warning('Geocoding status != 200: ' . $geoResponse->getStatusCode());
            throw new UpstreamUnavailableException('Serviço de geolocalização indisponível.');
        }

        $geoJson = $geoResponse->getJson();
        if (!is_array($geoJson)) {
            throw new UpstreamInvalidResponseException('Resposta inválida do serviço de geolocalização.');
        }

        $first = $geoJson['results'][0] ?? null;
        if (!is_array($first)) {
            throw new CityNotFoundException('Cidade não encontrada.');
        }

        $lat = $first['latitude'] ?? null;
        $lon = $first['longitude'] ?? null;

        if (!is_numeric($lat) || !is_numeric($lon)) {
            throw new UpstreamInvalidResponseException('Latitude/longitude inválidas retornadas.');
        }

        // 2) Forecast
        $forecastUrl = sprintf(self::FORECAST_URL, $lat, $lon);

        try {
            $forecastResponse = $http->get($forecastUrl);
        } catch (\Throwable $e) {
            Log::error('Falha no forecast: ' . $e->getMessage());
            throw new UpstreamUnavailableException('Serviço de clima indisponível.');
        }

        if ($forecastResponse->getStatusCode() !== 200) {
            Log::warning('Forecast status != 200: ' . $forecastResponse->getStatusCode());
            throw new UpstreamUnavailableException('Serviço de clima indisponível.');
        }

        $forecastJson = $forecastResponse->getJson();
        if (!is_array($forecastJson)) {
            throw new UpstreamInvalidResponseException('Resposta inválida do serviço de clima.');
        }

        $dados = WeatherResponseMapper::map($forecastJson);

        // Enriquecer com nome “bonito” da cidade
        $payload = [
            'cidade' => $first['name'] ?? $city,
            'estado' => $first['admin1'] ?? null,
            'pais' => $first['country'] ?? null,
            'clima' => $dados,
            'fonte' => 'open-meteo',
            'cache' => false,
        ];

        Cache::write($cacheKey, $payload, self::CACHE_CONFIG);

        return $payload;
    }
}
