<div class="d-flex align-items-center justify-content-end gap-1">
    <div class="dropdown">
        <button type="button" class="btn btn-link dropdown-toggle btn-icon p-0" data-toggle="dropdown">
            <i class="fa fa-ellipsis-h"></i>
        </button>
        <div class="dropdown-menu dropdown-menu-right">
            <a class="dropdown-item m-0" href="javascript:void(0);"
                onclick="getUserDetail({{ $user->id }},'{{ $user->role }}')">
                <i class="fa fa-edit"></i> Editar
            </a>
            <a class="dropdown-item m-0" href="javascript:void(0);"
                onclick="showChangePasswordForm({{ $user->id }},'{{ $user->role }}')">
                <i class="bi bi-shield"></i> Alterar senha
            </a>
            @if ($user->role == 'operator')
                <a class="dropdown-item m-0" href="javascript:void(0);"
                    onclick="registerFaceModal({{ $user->id }},'{{ $user->face_id ?? '' }}')">
                    <i class="bi bi-person-bounding-box"></i> Registrar rosto
                </a>
                <a class="dropdown-item m-0" href="javascript:void(0);"
                    onclick="showQr({{ $user->id }})">
                    <i class="fa fa-qrcode"></i> QR Code
                </a>
            @endif
            <a class="dropdown-item m-0 text-danger" href="javascript:void(0);"
                onclick="deleteUser({{ $user->id }},'{{ $user->role }}')">
                <i class="fa fa-trash"></i> Excluir
            </a>
        </div>
    </div>
    @if ($user->role == 'operator')
        <span id="qr-{{ $user->id }}" style="display:none;">{!! QrCode::size(250)->generate($user->qr_token ?? '') !!}</span>
    @endif
</div>
