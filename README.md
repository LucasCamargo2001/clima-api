# Weather API – CakePHP

API REST simples para consulta de clima atual a partir do nome de uma cidade, consumindo um serviço público externo, normalizando o retorno e expondo um contrato JSON padronizado.

---

## Tecnologias utilizadas

- PHP 8.1+
- CakePHP 5
- Composer
- Open-Meteo API (serviço público de clima)
- Cache em arquivo (File Cache)
- Logs nativos do CakePHP

---

## Endpoint

### GET /api/weather/{cidade}

Consulta o clima atual de uma cidade informada.

A cidade pode ser informada com ou sem espaços:
- /api/weather/Sao%20Paulo
- /api/weather/Rio-de-Janeiro

---

## Exemplo de sucesso

**Request**
GET /api/weather/Sao%20Paulo

**Response – 200**
{
  "sucesso": true,
  "dados": {
    "cidade": "São Paulo",
    "estado": "São Paulo",
    "pais": "Brasil",
    "clima": {
      "temperatura_c": 27.4,
      "vento_kmh": 9.8,
      "hora": "2026-01-10T22:00"
    },
    "fonte": "open-meteo",
    "cache": false
  },
  "erro": null
}

Quando a mesma cidade é consultada novamente dentro do tempo de cache, o campo "cache" retorna true, indicando que os dados foram obtidos do cache local.

---

## Possíveis erros

### Cidade não encontrada (404)
{
  "sucesso": false,
  "dados": null,
  "erro": {
    "mensagem": "Cidade não encontrada.",
    "status": 404
  }
}

### Serviço externo indisponível (503)
{
  "sucesso": false,
  "dados": null,
  "erro": {
    "mensagem": "Serviço de clima indisponível.",
    "status": 503
  }
}

### Resposta inválida do serviço externo (502)
{
  "sucesso": false,
  "dados": null,
  "erro": {
    "mensagem": "Resposta inválida do serviço de clima.",
    "status": 502
  }
}

---

## Arquitetura do projeto

src/
 ├── Controller/
 │   └── WeatherController.php
 │
 ├── Service/
 │   ├── WeatherService.php
 │   ├── Mapper/
 │   │   └── WeatherResponseMapper.php
 │   └── Exception/
 │       ├── CityNotFoundException.php
 │       ├── UpstreamUnavailableException.php
 │       └── UpstreamInvalidResponseException.php

---

## Como executar localmente

1. Instalar dependências:
composer install

2. Subir servidor:
php bin/cake.php server

3. Acessar:
http://localhost:8765/api/weather/Sao%20Paulo

---

## Observações

- O projeto utiliza cache de 10 minutos para evitar chamadas repetidas à API externa.
- Logs de falhas e exceções são registrados utilizando o sistema de logs do CakePHP.
- O arquivo config/app_local.php não deve ser versionado, pois contém configurações locais e sensíveis.
