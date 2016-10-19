<?php declare(strict_types=1);

namespace DaveRandom\AsyncBitlyClient;

use Amp\Promise;
use DaveRandom\AsyncBitlyClient\Exceptions\RequestFailedException;
use DaveRandom\AsyncBitlyClient\Exceptions\ResponseFormatErrorException;
use DaveRandom\AsyncBitlyClient\Responses\ExpandResult;
use DaveRandom\AsyncBitlyClient\Responses\ShortenResult;
use function Amp\resolve;

class LinkAccessor
{
    private $client;

    private function sendApiShortenRequest(string $longUrl, string $domain = null)
    {
        $params = ['longUrl' => $longUrl];

        if ($domain !== null) {
            $params['domain'] = $domain;
        }

        $result = yield $this->client->sendRequest('/shorten', $params);

        $haveExpectedResponseData = isset(
            $result['data']['hash'],
            $result['data']['global_hash'],
            $result['data']['long_url'],
            $result['data']['url'],
            $result['data']['new_hash']
        );

        if (!$haveExpectedResponseData) {
            throw new ResponseFormatErrorException('API response JSON does not contain expected data fields');
        }

        return new ShortenResult(
            $result['data']['hash'],
            $result['data']['global_hash'],
            $result['data']['long_url'],
            $result['data']['url'],
            (bool)$result['data']['new_hash']
        );
    }

    private function sendApiExpandRequest(string $hash)
    {
        $result = yield $this->client->sendRequest('/expand', ['hash' => $hash]);

        $haveExpectedResponseData = isset(
            $result['data']['global_hash'],
            $result['data']['long_url'],
            $result['data']['user_hash']
        );

        if (!$haveExpectedResponseData) {
            throw new ResponseFormatErrorException('API response JSON does not contain expected data fields');
        }

        if (isset($result['data']['error'])) {
            throw new RequestFailedException("API responded with error: " . $result['data']['error']);
        }

        return new ExpandResult(
            $result['data']['hash'],
            $result['data']['global_hash'],
            $result['data']['long_url']
        );
    }

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function shortenUrl(string $longUrl, string $domain = null): Promise
    {
        /** @noinspection PhpParamsInspection */
        return resolve($this->sendApiShortenRequest($longUrl, $domain));
    }

    public function expandUrl(string $hashOrShortUrl): Promise
    {
        $hash = preg_match('#https?://[^/]+/(.+)#', $hashOrShortUrl, $match)
            ? $match[1]
            : $hashOrShortUrl;

        /** @noinspection PhpParamsInspection */
        return resolve($this->sendApiExpandRequest($hash));
    }
}
