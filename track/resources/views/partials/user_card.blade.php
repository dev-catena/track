<div class="col-md-4 pb-3">
    <div class="card mb-3">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-start">
                <div class="d-flex align-items-center gap-2">
                    @php $userName = $user->name ?? ''; @endphp
                    @if(!empty($user->avatar))
                        <img src="{{ asset('storage/' . $user->avatar) }}" alt="{{ $userName }}" class="rounded-circle" style="width: 48px; height: 48px; object-fit: cover;">
                    @else
                        <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center text-white" style="width: 48px; height: 48px; font-size: 1.2rem;">
                            {{ $userName ? strtoupper(mb_substr($userName, 0, 1)) : '?' }}
                        </div>
                    @endif
                    <div>
                        <h3 title="{{ $userName }}" class="title mb-0 text-nowrap card_title_overflow">{{ $userName ?: 'Sem nome' }}
                </h3>
                    </div>
                </div>
                <div class="dropdown">
                    <button type="button" class="btn btn-link dropdown-toggle btn-icon" data-toggle="dropdown">
                        <i class="fa fa-ellipsis-h"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-right">
                        <a class="dropdown-item m-0" href="javascript:void(0);"
                            onclick="getUserDetail({{ $user->id }},'{{ $user->role }}')">
                            <i class="fa fa-edit"></i>
                            Edit
                        </a>
                        <a class="dropdown-item m-0" href="javascript:void(0);"
                            onclick="showChangePasswordForm({{ $user->id }},'{{ $user->role }}')">
                            <i class="bi bi-shield"></i>
                            Change Password
                        </a>
                        @if ($user->role == 'operator')
                            <a class="dropdown-item m-0" href="javascript:void(0);"
                                onclick="registerFaceModal({{ $user->id }},'{{ $user->face_id }}')">
                                <i class="bi bi-person-bounding-box"></i>
                                Register Face
                            </a>

                            <a class="dropdown-item m-0" href="javascript:void(0);"
                                onclick="showQr({{ $user->id }})">
                                <i class="fa fa-qrcode"></i>
                                Qr Code
                            </a>

                            <div id="qr-{{ $user->id }}" style="display:none;">
                                {!! QrCode::size(250)->generate($user->qr_token) !!}
                            </div>
                        @endif
                        <a class="dropdown-item m-0 text-danger" href="javascript:void(0);"
                            onclick="deleteUser({{ $user->id }},'{{ $user->role }}')">
                            <i class="fa fa-trash"></i>
                            Delete
                        </a>
                    </div>
                </div>
            </div>
            <p><i class="fa fa-envelope"></i> {{ $user->email }}</p>
            <div class="badge-section">
                <span
                    class="badge {{ $user->status == 'active' ? 'badge-success' : 'badge-danger' }} rounded-4 p-2">{{ ucfirst($user->status) }}</span>
                <span class="badge badge-primary rounded-4 p-2">{{ ucfirst($user->role) }}</span>
            </div>

        </div>

        <div class="card-body">
            <p><i class="fa fa-phone"></i> {{ $user->phone ?? 'N/A' }}</p>
            <p>Created at: {{ $user->created_at }}</p>
        </div>
    </div>
</div>
