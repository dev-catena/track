<?php

use App\Models\Operator;
use App\Models\Organization;
use App\Services\ThalamusFaceService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('track:face-diagnose {--name= : Parte do nome do operador (ex. Darley)}', function () {
    $this->info('=== Configuração Thalamus (servidor Laravel) ===');
    $base = (string) config('services.thalamus_face.base_url');
    $this->line('THALAMUS_FACE_BASE_URL: '.($base !== '' ? $base : '(vazio — registro/login facial não funcionam)'));
    $this->line('THALAMUS_FACE_BANCO_IMAGENS: '.config('services.thalamus_face.banco_imagens'));
    $this->line('THALAMUS_FACE_ID_PREFIX: '.config('services.thalamus_face.id_prefix'));
    $this->line('RECOGNIZE_PATH: '.config('services.thalamus_face.recognize_path'));
    $this->line('RECOGNIZE_MODE: '.config('services.thalamus_face.recognize_mode'));

    $this->newLine();
    $this->info('=== Operadores (tabela operators) ===');
    $with = Operator::query()->whereNotNull('face_id')->where('face_id', '!=', '')->count();
    $total = Operator::query()->count();
    $this->line("Com face_id preenchido: {$with} / Total: {$total}");

    $name = (string) $this->option('name');
    $q = Operator::query()->orderBy('id');
    if ($name !== '') {
        $q->where('name', 'like', '%'.$name.'%');
    }
    $rows = $q->limit(25)->get(['id', 'name', 'organization_id', 'face_id', 'status']);
    if ($rows->isEmpty()) {
        $this->warn('Nenhum operador encontrado'.($name !== '' ? ' para o filtro --name=' : '').$name);

        return 0;
    }
    foreach ($rows as $o) {
        $expected = ThalamusFaceService::operatorFaceId((int) $o->id);
        $org = $o->organization_id
            ? (string) Organization::query()->where('id', $o->organization_id)->value('name')
            : '';
        $match = ($o->face_id !== null && trim((string) $o->face_id) === $expected) ? 'OK' : 'DIVERGENTE ou vazio';
        $this->line(sprintf(
            'id=%d | %s | org=%s (%s) | status=%s',
            $o->id,
            $o->name,
            $o->organization_id ?? '—',
            $org !== '' ? $org : '?',
            $o->status ?? '—'
        ));
        $this->line('  face_id BD: '.($o->face_id ?: '(null/vazio)'));
        $this->line('  esperado p/ Thalamus: '.$expected.' | '.$match);
    }

    $this->newLine();
    $this->warn('App track-mobile: BASE_URL deve ser este mesmo Laravel (EXPO_PUBLIC_API_BASE_URL ou track-mobile/src/config/api.js).');
    $this->line('Login facial: POST /api/auth/v2/login type=face_login → Thalamus recognize → Operator onde face_id = id devolvido.');

    return 0;
})->purpose('Diagnóstico rápido: Thalamus .env e face_id dos operadores');

