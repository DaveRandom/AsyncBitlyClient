<?php declare(strict_types=1);

namespace DaveRandom\AsyncBitlyClient;

use Amp\Artax\HttpClient;
use Amp\Artax\Response as HttpResponse;
use Amp\Promise;
use DaveRandom\AsyncBitlyClient\Exceptions\NotFoundException;
use DaveRandom\AsyncBitlyClient\Exceptions\RateLimitHitException;
use DaveRandom\AsyncBitlyClient\Exceptions\RequestFailedException;
use DaveRandom\AsyncBitlyClient\Exceptions\ResponseFormatErrorException;
use DaveRandom\AsyncBitlyClient\Exceptions\TemporarilyUnavailableException;
use ExceptionalJSON\DecodeErrorException as JSONDecodeErrorException;
use function Amp\resolve;

class Client
{
    private $accessToken;
    private $httpClient;
    private $baseUrl;

    public function __construct(HttpClient $httpClient, string $accessToken, string $baseUrl = API_URL_BASE)
    {
        $this->accessToken = $accessToken;
        $this->httpClient = $httpClient;
        $this->baseUrl = $baseUrl;
    }

    private function throwIfNot200(int $statusCode)
    {
        if ($statusCode !== 200) {
            throw new RequestFailedException('Server responded with a status code of ' . $statusCode, $statusCode);
        }
    }

    private function decodeJsonResponse(string $responseBody)
    {
        try {
            return json_try_decode($responseBody, true);
        } catch (JSONDecodeErrorException $e) {
            throw new ResponseFormatErrorException('Unable to decode API response body as JSON', 0, $e);
        } catch (\Throwable $e) {
            throw new RequestFailedException($e->getMessage(), $e->getCode(), $e);
        }
    }

    private function throwOnAPIErrorResponse(int $code, string $text)
    {
        switch ($code) {
            case 400:
                throw new RequestFailedException("API responded with error: {$text}", $code);
            case 403:
                throw new RateLimitHitException("Rate limit exceeded", $code);
            case 404:
                throw new NotFoundException("Data not found", $code);
            case 503:
                throw new TemporarilyUnavailableException("Data temporarily unavailable", $code);
        }
    }

    private function doRequest(string $url)
    {
        try {
            /** @var HttpResponse $response */
            $response = yield $this->httpClient->request($url);
        } catch (\Throwable $e) {
            throw new RequestFailedException($e->getMessage(), $e->getCode(), $e);
        }

        // https://dev.bitly.com/formats.html

        $this->throwIfNot200($response->getStatus());

        $decoded = $this->decodeJsonResponse($response->getBody());

        if (!isset($decoded['status_code'], $decoded['status_txt'])) {
            throw new ResponseFormatErrorException('API response JSON does not contain status elements');
        }

        $this->throwOnAPIErrorResponse($decoded['status_code'], $decoded['status_txt']);

        return $decoded;
    }

    public function sendRequest(string $endpoint, array $params = []): Promise
    {
        return resolve($this->doRequest($this->baseUrl . '/' . ltrim($endpoint, '/') . '?' . http_build_query([
            'format' => 'json',
            'access_token' => $this->accessToken,
        ] + $params)));
    }
}
