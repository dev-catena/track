<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use ZipArchive;

/**
 * Envia referência facial para o banco Thalamus (ZIP binário + query face_id).
 * @see https://face.thalamus.ind.br/face/api/database/add/zip
 */
class ThalamusFaceService
{
    public function isConfigured(): bool
    {
        return (bool) config('services.thalamus_face.base_url');
    }

    /**
     * Empacota uma imagem em ZIP e envia como application/octet-stream.
     *
     * @return array{ok: bool, message: string, status?: int, body?: mixed}
     */
    public function registerFromImage(string $imagePath, string $faceId): array
    {
        $base = rtrim((string) config('services.thalamus_face.base_url'), '/');
        $banco = (string) config('services.thalamus_face.banco_imagens', 'thalamus');

        if ($base === '') {
            return ['ok' => false, 'message' => 'Thalamus Face API não configurada (THALAMUS_FACE_BASE_URL).'];
        }

        if (! is_readable($imagePath)) {
            return ['ok' => false, 'message' => 'Imagem não legível para envio.'];
        }

        $zipPath = tempnam(sys_get_temp_dir(), 'thal_face_');
        if ($zipPath === false) {
            return ['ok' => false, 'message' => 'Não foi possível criar arquivo temporário.'];
        }

        $zipFile = $zipPath . '.zip';
        if (! @rename($zipPath, $zipFile)) {
            @unlink($zipPath);

            return ['ok' => false, 'message' => 'Não foi possível preparar o ZIP.'];
        }

        $zip = new ZipArchive;
        if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            @unlink($zipFile);

            return ['ok' => false, 'message' => 'Falha ao abrir ZIP temporário.'];
        }

        $ext = pathinfo($imagePath, PATHINFO_EXTENSION);
        $ext = ($ext !== '' && $ext !== false) ? $ext : 'jpg';
        $entryName = 'reference_' . time() . '.' . $ext;
        if (! $zip->addFile($imagePath, $entryName)) {
            $zip->close();
            @unlink($zipFile);

            return ['ok' => false, 'message' => 'Falha ao adicionar imagem ao ZIP.'];
        }
        $zip->close();

        $payload = file_get_contents($zipFile);
        @unlink($zipFile);

        if ($payload === false || $payload === '') {
            return ['ok' => false, 'message' => 'ZIP vazio ou ilegível.'];
        }

        $url = $base . '/face/api/database/add/zip?' . http_build_query(['face_id' => $faceId]);

        try {
            $response = Http::connectTimeout(20)
                ->timeout(90)
                ->withHeaders([
                    'Content-Type' => 'application/octet-stream',
                    'banco-imagens' => $banco,
                ])
                ->withBody($payload, 'application/octet-stream')
                ->post($url);
        } catch (\Throwable $e) {
            Log::warning('ThalamusFaceService: requisição falhou', ['error' => $e->getMessage(), 'face_id' => $faceId]);

            return ['ok' => false, 'message' => 'Erro ao contatar servidor facial: ' . $e->getMessage()];
        }

        $status = $response->status();
        $body = $response->body();
        $json = $response->json();

        if ($response->successful()) {
            return [
                'ok' => true,
                'message' => is_array($json) && isset($json['message']) ? (string) $json['message'] : 'Rosto registrado no banco Thalamus.',
                'status' => $status,
                'body' => $json ?? $body,
            ];
        }

        Log::warning('ThalamusFaceService: resposta de erro', [
            'face_id' => $faceId,
            'status' => $status,
            'body' => mb_substr($body, 0, 2000),
        ]);

        $msg = is_array($json)
            ? ($json['message'] ?? $json['error'] ?? $json['detail'] ?? null)
            : null;
        if (! is_string($msg) || $msg === '') {
            $msg = mb_substr($body, 0, 500) ?: 'HTTP ' . $status;
        }

        return ['ok' => false, 'message' => $msg, 'status' => $status, 'body' => $json ?? $body];
    }

    /**
     * Headers comuns do POST de reconhecimento (alinhado ao cliente Flutter / Thalamus).
     * URL: {THALAMUS_FACE_BASE_URL}/face/api/recognize/image (padrão em recognize_path).
     */
    private function recognizeHttpHeaders(): array
    {
        $banco = (string) config('services.thalamus_face.banco_imagens', 'thalamus');
        $ua = (string) config('services.thalamus_face.user_agent', 'Flutter-App/1.0');

        return [
            'banco-imagens' => $banco,
            'User-Agent' => $ua,
            'Connection' => 'keep-alive',
            'Accept-Encoding' => 'identity',
        ];
    }

    /**
     * Reconhecimento facial: POST {base}{recognize_path} (ex.: /face/api/recognize/image)
     *
     * @return array{ok: bool, face_id: ?string, message: string, status?: int, body?: mixed}
     */
    public function recognizeFromImage(string $imagePath): array
    {
        $base = rtrim((string) config('services.thalamus_face.base_url'), '/');

        if ($base === '') {
            return ['ok' => false, 'face_id' => null, 'message' => 'Thalamus Face API não configurada (THALAMUS_FACE_BASE_URL).'];
        }

        if (! is_readable($imagePath)) {
            return ['ok' => false, 'face_id' => null, 'message' => 'Imagem não legível.'];
        }

        $path = (string) config('services.thalamus_face.recognize_path', '/face/api/recognize/image');
        $path = str_starts_with($path, '/') ? $path : '/' . $path;
        $url = $base . $path;
        $mode = strtolower((string) config('services.thalamus_face.recognize_mode', 'octet_stream'));
        $recognizeHeaders = $this->recognizeHttpHeaders();

        try {
            if ($mode === 'multipart') {
                $field = (string) config('services.thalamus_face.recognize_multipart_field', 'image');
                $mime = $this->guessMime($imagePath);
                $response = Http::connectTimeout(15)
                    ->timeout(60)
                    ->withHeaders($recognizeHeaders)
                    ->attach($field, file_get_contents($imagePath), basename($imagePath), ['Content-Type' => $mime])
                    ->post($url);
            } else {
                $mime = $this->guessMime($imagePath);
                $bytes = file_get_contents($imagePath);
                $response = Http::connectTimeout(15)
                    ->timeout(60)
                    ->withHeaders(array_merge($recognizeHeaders, [
                        'Content-Type' => $mime,
                    ]))
                    ->withBody($bytes !== false ? $bytes : '', $mime)
                    ->post($url);
            }
        } catch (\Throwable $e) {
            Log::warning('ThalamusFaceService recognize: falha de rede', ['error' => $e->getMessage()]);

            return ['ok' => false, 'face_id' => null, 'message' => 'Erro ao contatar servidor facial: ' . $e->getMessage()];
        }

        $status = $response->status();
        $body = $response->body();
        $json = $response->json();

        if (! $response->successful()) {
            Log::warning('ThalamusFaceService recognize: HTTP erro', [
                'status' => $status,
                'body' => mb_substr($body, 0, 2000),
            ]);
            $msg = $this->humanMessageFromBody($json, $body, $status);

            return ['ok' => false, 'face_id' => null, 'message' => $msg, 'status' => $status, 'body' => $json ?? $body];
        }

        $faceId = $this->extractRecognizedFaceId(is_array($json) ? $json : []);

        if ($faceId === null || $faceId === '') {
            Log::warning('ThalamusFaceService: HTTP OK mas face_id não extraído do JSON', [
                'keys' => is_array($json) ? array_keys($json) : null,
                'preview' => mb_substr($body, 0, 2000),
            ]);

            return [
                'ok' => false,
                'face_id' => null,
                'message' => 'Nenhum rosto reconhecido.',
                'status' => $status,
                'body' => $json ?? $body,
            ];
        }

        return [
            'ok' => true,
            'face_id' => $faceId,
            'message' => 'Reconhecido.',
            'status' => $status,
            'body' => $json ?? $body,
        ];
    }

    private function guessMime(string $imagePath): string
    {
        $ext = strtolower((string) pathinfo($imagePath, PATHINFO_EXTENSION));

        return match ($ext) {
            'png' => 'image/png',
            'webp' => 'image/webp',
            default => 'image/jpeg',
        };
    }

    /**
     * Extrai identificador da pessoa no banco Thalamus (mesmo valor usado em add/zip ?face_id=).
     */
    public function extractRecognizedFaceId(array $data): ?string
    {
        // Resposta raiz como lista, ex.: [ { "face_id": "..." } ]
        if (array_is_list($data)) {
            $first = $data[0] ?? null;
            if (is_array($first)) {
                $inner = $this->extractRecognizedFaceId($first);
                if ($inner !== null && $inner !== '') {
                    return $inner;
                }
            }
        }

        // Thalamus /face/api/recognize/image devolve matches[].person (ex.: "track_op_1")
        $priorityKeys = [
            'face_id', 'faceId', 'person', 'person_id', 'personId', 'matched_face_id', 'matchedFaceId',
            'identity', 'subject', 'subject_id', 'reference_id', 'label', 'name', 'id', 'user', 'codigo',
        ];

        foreach ($priorityKeys as $key) {
            if (! array_key_exists($key, $data)) {
                continue;
            }
            $v = $data[$key];
            if (is_string($v) && $v !== '') {
                return $v;
            }
            if (is_int($v) || is_float($v)) {
                return (string) $v;
            }
        }

        foreach (['match', 'data', 'result', 'payload'] as $wrap) {
            if (isset($data[$wrap]) && is_array($data[$wrap])) {
                $inner = $this->extractRecognizedFaceId($data[$wrap]);
                if ($inner !== null) {
                    return $inner;
                }
            }
        }

        foreach (['matches', 'results', 'data'] as $listKey) {
            if (! isset($data[$listKey]) || ! is_array($data[$listKey])) {
                continue;
            }
            $first = $data[$listKey][0] ?? null;
            if (is_array($first)) {
                $inner = $this->extractRecognizedFaceId($first);
                if ($inner !== null) {
                    return $inner;
                }
            }
        }

        return null;
    }

    private function humanMessageFromBody(mixed $json, string $body, int $status): string
    {
        if (is_array($json)) {
            $msg = $json['message'] ?? $json['error'] ?? $json['detail'] ?? null;
            if (is_string($msg) && $msg !== '') {
                return $msg;
            }
        }

        return mb_substr($body, 0, 500) ?: 'HTTP ' . $status;
    }

    /** ID estável para operador (evita colisão com users). */
    public static function operatorFaceId(int $operatorId): string
    {
        $prefix = (string) config('services.thalamus_face.id_prefix', 'track');

        return $prefix . '_op_' . $operatorId;
    }

    /** ID estável para usuário admin/manager na tabela users. */
    public static function userFaceId(int $userId): string
    {
        $prefix = (string) config('services.thalamus_face.id_prefix', 'track');

        return $prefix . '_user_' . $userId;
    }
}
