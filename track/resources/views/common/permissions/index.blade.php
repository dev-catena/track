@extends('layouts.main')
@section('title') Permissões @endsection
@section('style')
<style>
.permissions-table-wrapper,
.permissions-table-wrapper .card-body,
.permissions-table-wrapper .permission-table,
.permissions-table-wrapper .permission-table thead,
.permissions-table-wrapper .permission-table tbody,
.permissions-table-wrapper .permission-table tr,
.permissions-table-wrapper .permission-table th,
.permissions-table-wrapper .permission-table td,
.white-content .permissions-table-wrapper .permission-table th,
.white-content .permissions-table-wrapper .permission-table td {
    background-color: #27293d !important;
    color: rgba(255, 255, 255, 0.9) !important;
    border-color: rgba(255, 255, 255, 0.2) !important;
}
.permissions-table-wrapper .permission-table .func-name,
.permissions-table-wrapper .permission-table .profile-header { color: rgba(255, 255, 255, 0.9) !important; }
.permission-table th, .permission-table td { vertical-align: middle; }
.permission-table input[type="checkbox"] { cursor: pointer; }
.badge-platform-web { background: #0c5389; color: #fff; }
.badge-platform-app { background: #28a745; color: #fff; }
.permissions-table-wrapper .admin-full-access { background: rgba(40, 167, 69, 0.3); padding: 4px 8px; border-radius: 6px; font-size: 0.85em; color: #90ee90 !important; }
</style>
@endsection
@section('content')
<?php $user = Auth::user(); ?>
<div class="content">
    <div class="row mb-3">
        <div class="col-sm-6 mb-3">
            <h1 class="m-0 d-flex align-items-center">
                <i class="bi bi-shield-lock"></i>
                <span class="heading_title ml-2">Permissões</span>
            </h1>
        </div>
    </div>
    <div class="card permissions-table-wrapper" style="background-color: #27293d;">
        <div class="card-body">
            <p class="text-muted mb-3">
                Associe as funcionalidades aos perfis. O <strong>Administrador</strong> de cada empresa tem acesso total por padrão.
                Os demais usuários têm acesso conforme a associação abaixo.
            </p>
            @if($profiles->isEmpty())
                <div class="alert alert-warning">
                    Nenhum perfil cadastrado. Execute o seeder: <code>php artisan db:seed --class=ProfileSeeder</code>
                </div>
            @elseif($functionalities->isEmpty())
                <div class="alert alert-warning">
                    <strong>Não há funcionalidades no banco.</strong> Por isso a grade aparece vazia.
                    No servidor, execute:
                    <code class="d-block mt-2 p-2 bg-dark text-white rounded">php artisan db:seed --class=FunctionalitySeeder</code>
                    <span class="d-block mt-2 small">Isso preenche a tabela <code>functionalities</code> e as permissões padrão de Gerente/Operador. Em instalações novas, use <code>php artisan db:seed</code> (já inclui perfis e funcionalidades).</span>
                </div>
            @endif
            <form id="permission-form">
                @csrf
                <div class="table-responsive">
                    <table class="table table-bordered permission-table" style="background-color: #27293d; color: rgba(255,255,255,0.9);">
                        <thead>
                            <tr>
                                <th class="func-name">Funcionalidade</th>
                                @foreach($profiles as $profile)
                                    <th class="profile-header">
                                        {{ $profile->name }}
                                        @if($profile->code === 'admin')
                                            <br><span class="admin-full-access text-success"><i class="fa fa-check-circle"></i> Acesso total</span>
                                        @endif
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($functionalities as $func)
                                <tr>
                                    <td class="func-name">
                                        <span class="badge badge-platform-{{ $func->platform }} rounded-4 px-2">{{ $func->platform }}</span>
                                        {{ $func->name }}
                                    </td>
                                    @foreach($profiles as $profile)
                                        <td class="text-center">
                                            @if($profile->code === 'admin')
                                                <input type="checkbox" checked disabled title="Admin tem acesso total">
                                            @else
                                                <input type="checkbox" name="profile_{{ $profile->id }}[]" value="{{ $func->id }}"
                                                    {{ in_array($func->id, $matrix[$profile->id] ?? []) ? 'checked' : '' }}>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @empty
                                @if(!$profiles->isEmpty())
                                    <tr>
                                        <td colspan="{{ $profiles->count() + 1 }}" class="text-center text-muted py-4">
                                            Nenhuma funcionalidade para exibir. Rode o seeder acima.
                                        </td>
                                    </tr>
                                @endif
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">
                    <button type="button" class="btn btn-primary" id="btn-save-permissions" @if($functionalities->isEmpty() || $profiles->isEmpty()) disabled @endif>
                        <i class="fa fa-save"></i> Salvar Permissões
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
@section('scripts')
<script>
const permissionUpdateUrl = "{{ route('permission.update') }}";
$(function() {
    $('#btn-save-permissions').on('click', function() {
        var $btn = $(this);
        $btn.prop('disabled', true);
        var data = {};
        $('input[type="checkbox"]:not(:disabled)').each(function() {
            var name = $(this).attr('name');
            if (!name) return;
            var m = name.match(/profile_(\d+)/);
            if (m) {
                var pid = m[1];
                if (!data[pid]) data[pid] = [];
                if ($(this).is(':checked')) data[pid].push($(this).val());
            }
        });
        var payloads = [];
        for (var pid in data) {
            if (!data[pid] || data[pid].length === 0) {
                payloads.push($.ajax({
                    url: permissionUpdateUrl,
                    type: 'POST',
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content'),
                        profile_id: pid,
                        functionality_ids: []
                    },
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                }));
            } else {
                payloads.push($.ajax({
                    url: permissionUpdateUrl,
                    type: 'POST',
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content'),
                        profile_id: pid,
                        functionality_ids: data[pid]
                    },
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                }));
            }
        }
        $.when.apply($, payloads).done(function() {
            if (typeof showAlert === 'function') showAlert('success', 'Permissões salvas com sucesso.');
        }).fail(function() {
            if (typeof showAlert === 'function') showAlert('error', 'Erro ao salvar.');
        }).always(function() {
            $btn.prop('disabled', false);
        });
    });
});
</script>
@endsection
