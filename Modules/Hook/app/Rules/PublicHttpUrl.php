<?php

/**
 * Public HTTP URL validation rule.
 *
 * Rejects generic webhook targets that could reach non-public networks.
 *
 * @author Tool Dock Team
 * @license MIT
 */

namespace Modules\Hook\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class PublicHttpUrl implements ValidationRule
{
    /** @var Closure(string): list<string> */
    private readonly Closure $resolver;

    /**
     * @param  (callable(string): list<string>)|null  $resolver
     */
    public function __construct(?callable $resolver = null)
    {
        $this->resolver = $resolver !== null
            ? Closure::fromCallable($resolver)
            : $this->resolve(...);
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! $this->isAllowed($value)) {
            $fail('The :attribute must use HTTP or HTTPS and resolve only to public addresses.');
        }
    }

    public function isAllowed(string $url): bool
    {
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        $parts = parse_url($url);

        if (! is_array($parts)) {
            return false;
        }

        $scheme = strtolower($parts['scheme'] ?? '');
        $host = strtolower(rtrim($parts['host'] ?? '', '.'));
        $host = trim($host, '[]');

        if (! in_array($scheme, ['http', 'https'], true) || $host === '') {
            return false;
        }

        if ($host === 'localhost' || str_ends_with($host, '.localhost')) {
            return false;
        }

        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return $this->isPublicIp($host);
        }

        // Prevent clients from interpreting alternate numeric host formats as IP addresses.
        if (preg_match('/^[0-9.]+$/', $host) === 1 || preg_match('/(^|\.)0x[0-9a-f]+($|\.)/i', $host) === 1) {
            return false;
        }

        $addresses = ($this->resolver)($host);

        return $addresses !== []
            && collect($addresses)->every(fn (string $address): bool => $this->isPublicIp($address));
    }

    /**
     * @return list<string>
     */
    private function resolve(string $host): array
    {
        $records = @dns_get_record($host, DNS_A | DNS_AAAA);

        if ($records === false) {
            return [];
        }

        $addresses = [];

        foreach ($records as $record) {
            $address = $record['ip'] ?? $record['ipv6'] ?? null;

            if (is_string($address)) {
                $addresses[] = $address;
            }
        }

        return array_values(array_unique($addresses));
    }

    private function isPublicIp(string $ip): bool
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return false;
        }

        $blockedRanges = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false
            ? [
                '0.0.0.0/8',
                '10.0.0.0/8',
                '100.64.0.0/10',
                '127.0.0.0/8',
                '169.254.0.0/16',
                '172.16.0.0/12',
                '192.0.0.0/24',
                '192.0.2.0/24',
                '192.88.99.0/24',
                '192.168.0.0/16',
                '198.18.0.0/15',
                '198.51.100.0/24',
                '203.0.113.0/24',
                '224.0.0.0/4',
                '240.0.0.0/4',
            ]
            : [
                '::/96',
                '::ffff:0:0/96',
                '64:ff9b::/96',
                '64:ff9b:1::/48',
                '100::/64',
                '2001::/23',
                '2001:db8::/32',
                '2002::/16',
                '3fff::/20',
                'fc00::/7',
                'fe80::/10',
                'ff00::/8',
            ];

        foreach ($blockedRanges as $range) {
            if ($this->isInRange($ip, $range)) {
                return false;
            }
        }

        return true;
    }

    private function isInRange(string $ip, string $range): bool
    {
        [$subnet, $prefixLength] = explode('/', $range, 2);
        $ipBytes = inet_pton($ip);
        $subnetBytes = inet_pton($subnet);

        if ($ipBytes === false || $subnetBytes === false || strlen($ipBytes) !== strlen($subnetBytes)) {
            return false;
        }

        $prefixLength = (int) $prefixLength;
        $wholeBytes = intdiv($prefixLength, 8);
        $remainingBits = $prefixLength % 8;

        if (substr($ipBytes, 0, $wholeBytes) !== substr($subnetBytes, 0, $wholeBytes)) {
            return false;
        }

        if ($remainingBits === 0) {
            return true;
        }

        $mask = (0xFF << (8 - $remainingBits)) & 0xFF;

        return (ord($ipBytes[$wholeBytes]) & $mask) === (ord($subnetBytes[$wholeBytes]) & $mask);
    }
}
