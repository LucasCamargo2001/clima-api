<?php
declare(strict_types=1);

namespace App\Service\Mapper;

class WeatherResponseMapper
{
    /**
     * Espera receber o JSON do Open-Meteo Forecast (já decodificado).
     */
    public static function map(array $forecastJson): array
    {
        $current = $forecastJson['current'] ?? null;

        return [
            'temperatura_c' => $current['temperature_2m'] ?? null,
            'vento_kmh' => $current['wind_speed_10m'] ?? null,
            'hora' => $current['time'] ?? null,
        ];
    }
}
