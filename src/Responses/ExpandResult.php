<?php declare(strict_types = 1);

namespace DaveRandom\AsyncBitlyClient\Responses;

class ExpandResult
{
    private $hash;
    private $globalHash;
    private $longUrl;

    public function __construct(string $hash, string $globalHash, string $longUrl)
    {
        $this->hash       = $hash;
        $this->globalHash = $globalHash;
        $this->longUrl    = $longUrl;
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
}
