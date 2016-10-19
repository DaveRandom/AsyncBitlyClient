<?php declare(strict_types = 1);

namespace DaveRandom\AsyncBitlyClient\Responses;

class ShortenResult
{
    private $hash;
    private $globalHash;
    private $longUrl;
    private $shortUrl;
    private $newHash;

    public function __construct(string $hash, string $globalHash, string $longUrl, string $shortUrl, bool $newHash)
    {
        $this->hash       = $hash;
        $this->globalHash = $globalHash;
        $this->longUrl    = $longUrl;
        $this->shortUrl   = $shortUrl;
        $this->newHash  = $newHash;
    }

    public function getHash(): string
    {
        return $this->hash;
    }

    public function getGlobalHash(): string
    {
        return $this->globalHash;
    }

    public function getLongUrl(): string
    {
        return $this->longUrl;
    }

    public function getShortUrl(): string
    {
        return $this->shortUrl;
    }

    public function isNewHash(): bool
    {
        return $this->newHash;
    }
}
