<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\Exception\CityNotFoundException;
use App\Service\Exception\UpstreamInvalidResponseException;
use App\Service\Exception\UpstreamUnavailableException;
use App\Service\WeatherService;

class WeatherController extends AppController
{
    public function view(string $city)
    {
        $service = new WeatherService();

        try {
            $data = $service->fetchByCity($city);
            return $this->jsonSuccess($data);
        } catch (CityNotFoundException $e) {
            return $this->jsonError(404, $e->getMessage());
        } catch (UpstreamInvalidResponseException $e) {
            return $this->jsonError(502, $e->getMessage());
        } catch (UpstreamUnavailableException $e) {
            return $this->jsonError(503, $e->getMessage());
        } catch (\Throwable $e) {
            return $this->jsonError(500, 'Erro inesperado.');
        }
    }

    private function jsonSuccess(array $dados)
    {
        $payload = [
            'sucesso' => true,
            'dados' => $dados,
            'erro' => null,
        ];

        return $this->response
            ->withType('application/json')
            ->withStringBody(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    private function jsonError(int $status, string $mensagem)
    {
        $payload = [
            'sucesso' => false,
            'dados' => null,
            'erro' => [
                'mensagem' => $mensagem,
                'status' => $status,
            ],
        ];

        return $this->response
            ->withStatus($status)
            ->withType('application/json')
            ->withStringBody(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}
