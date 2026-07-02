<?php

namespace App\Libraries;

/**
 * Klien sederhana Google Gemini (Generative Language API).
 * API key & model diambil dari Pengaturan Aplikasi (app_settings) bila tidak dioper.
 */
class GeminiClient
{
    private string $apiKey;
    private string $model;

    public function __construct(?string $apiKey = null, ?string $model = null)
    {
        $this->apiKey = $apiKey ?? setting('gemini_api_key', '');
        $this->model  = $model ?? setting('gemini_model', 'gemini-2.5-flash');
    }

    public function isConfigured(): bool
    {
        return trim($this->apiKey) !== '';
    }

    /**
     * Daftar model yang tersedia untuk API key ini & mendukung generateContent.
     * Kembalikan array nama model tanpa prefix "models/". Array kosong bila gagal.
     */
    public function listModels(): array
    {
        if (!$this->isConfigured()) {
            return [];
        }
        $url = 'https://generativelanguage.googleapis.com/v1beta/models?pageSize=200&key='
            . rawurlencode(trim($this->apiKey));
        try {
            $client = \Config\Services::curlrequest(['timeout' => 20]);
            $res = $client->get($url, ['http_errors' => false]);
            if ($res->getStatusCode() !== 200) {
                return [];
            }
            $data = json_decode((string) $res->getBody(), true);
            $out  = [];
            foreach (($data['models'] ?? []) as $m) {
                $methods = $m['supportedGenerationMethods'] ?? [];
                if (in_array('generateContent', $methods, true)) {
                    $out[] = preg_replace('#^models/#', '', (string) ($m['name'] ?? ''));
                }
            }
            sort($out);
            return array_values(array_filter(array_unique($out)));
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Kirim prompt, kembalikan teks hasil (markdown).
     *
     * @throws \RuntimeException
     */
    public function generate(string $prompt): string
    {
        if (!$this->isConfigured()) {
            throw new \RuntimeException('API key Gemini belum diatur. Buka Pengaturan Aplikasi → Integrasi AI.');
        }

        $url = 'https://generativelanguage.googleapis.com/v1beta/models/'
            . rawurlencode($this->model) . ':generateContent?key=' . rawurlencode(trim($this->apiKey));

        $payload = [
            'contents' => [[
                'parts' => [['text' => $prompt]],
            ]],
            'generationConfig' => [
                'temperature'     => 0.4,
                'maxOutputTokens' => 4096,
            ],
        ];

        $client = \Config\Services::curlrequest(['timeout' => 60]);
        $res = $client->post($url, [
            'headers'     => ['Content-Type' => 'application/json'],
            'body'        => json_encode($payload),
            'http_errors' => false,
        ]);

        $status = $res->getStatusCode();
        $data   = json_decode((string) $res->getBody(), true);

        if ($status !== 200) {
            $msg = $data['error']['message'] ?? ('HTTP ' . $status);
            throw new \RuntimeException('Gemini API error: ' . $msg);
        }

        $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
        if (trim($text) === '') {
            $reason = $data['candidates'][0]['finishReason']
                ?? ($data['promptFeedback']['blockReason'] ?? 'tidak diketahui');
            throw new \RuntimeException('Respons AI kosong (alasan: ' . $reason . ').');
        }

        return $text;
    }
}
