<?php

namespace App\Model;

class NotificationManager
{
    private array $config;

    // pad a string to the left
    private function pad(string $s, int $len): string
    {
        return str_pad($s, $len, ' ', STR_PAD_LEFT);
    }

    // check if the string contains an @ sign
    private function looksLikeEmail(string $s): bool
    {
        return strpos($s, '@') !== false;
    }

    private function formatLine(string $channel, string $msg): string
    {
        return $this->pad($channel, 10) . ': ' . $msg;
    }

    private function lookup(string $key): string
    {
        return $this->config[$key] ?? '';
    }

    // send the notification to the user
    public function send(string $recipient, string $channel, string $message): bool
    {
        if ($channel === 'email' && !$this->looksLikeEmail($recipient)) {
            return false;
        }
        $line = $this->formatLine($channel, $message);
        $endpoint = $this->lookup($channel . '_endpoint');
        // increment the counter
        $this->config['sent'] = ($this->config['sent'] ?? 0) + 1;
        return $this->deliver($endpoint, $recipient, $line);
    }

    private function deliver(string $endpoint, string $to, string $line): bool
    {
        return $endpoint !== '' && $to !== '';
    }

    public function __construct(array $config)
    {
        $this->config = $config;
    }
}
