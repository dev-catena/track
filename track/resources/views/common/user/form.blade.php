<div class="row">
    <div class="col-md-12">


        <div class="card">

            <div class="card-body">
                <form method="POST" id="form" action="" enctype="multipart/form-data">
                    @csrf
                    <div class="row">
                        @if ($user->role == 'superadmin')
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="organization_id">Empresa <span class="required">*</label>
                                    <select class="form-control select-control" id="organization_id"
                                        name="organization_id" onchange="getDepartmentListByCompanyId(this.value)">
                                        <option value="">Selecionar Empresa</option>
                                        @foreach ($organizations as $id => $name)
                                            <option value="{{ $id }}" {{ ($selectedOrganizationId ?? null) == $id ? 'selected' : '' }}>{{ $name }}</option>
                                        @endforeach

                                    </select>
                                    <span class="error" id="organization_error"></span>
                                </div>
                            </div>
                        @else
                            <div class="col-md-4 d-none">
                                <div class="form-group">
                                    <select class="form-control select-control " id="organization_id"
                                        name="organization_id">
                                        <option value="{{ $organizations }}" selected>{{ $organizations }}</option>

                                    </select>
                                    <span class="error" id="organization_error"></span>
                                </div>
                            </div>
                        @endif

                        @if ($user->role == 'superadmin' || $user->role == 'admin')
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="department_id">Departamento </label>
                                    <select class="form-control select-control" id="department_id" name="department_id">
                                        <option value="">Selecionar Departamento</option>
                                        @if ($user->role == 'admin')
                                            @foreach ($departments as $department)
                                                <option value="{{ $department->id }}">{{ $department->name }}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                    <span class="error" id="department_error"></span>
                                </div>
                            </div>
                        @else
                            <div class="col-md-4 d-none">
                                <select class="form-control select-control" id="department_id" name="department_id">
                                    <option value="{{ $departments }}" selected>{{ $departments }}</option>
                                </select>
                                <span class="error" id="department_error"></span>
                            </div>

                        @endif

                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="name">Nome Completo <span class="required">*</span></label>
                                <input type="text" class="form-control" id="name" name="name"
                                    placeholder="Nome completo">
                                <span class="error" id="name_error"></span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="email">E-mail <span class="required">*</span></label>
                                <input type="email" class="form-control" id="email" name="email"
                                    placeholder="Email">
                                <span class="error" id="email_error"></span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="phone">Telefone</label>
                                <input type="text" class="form-control" id="phone" name="phone"
                                    placeholder="phone">
                                <span class="error" id="phone_error"></span>
                            </div>
                        </div>
                        <div class="col-md-12 mb-3">
                            <div class="form-group">
                                <label for="avatar">Foto do usuário</label>
                                <div class="d-flex align-items-center gap-3">
                                    <div id="avatar_preview" class="rounded-circle overflow-hidden bg-light border" style="width: 80px; height: 80px; display: none;">
                                        <img id="avatar_preview_img" src="" alt="Avatar" style="width: 100%; height: 100%; object-fit: cover;">
                                    </div>
                                    <div>
                                        <input type="file" class="form-control-file" id="avatar" name="avatar" accept="image/jpeg,image/png,image/jpg,image/webp">
                                        <small class="text-muted">JPG, PNG ou WebP. Máx. 2MB</small>
                                    </div>
                                </div>
                                <span class="error" id="avatar_error"></span>
                            </div>
                        </div>
                        <input type="hidden" id="operation" name="operation" value="indoor">
                        <div class="col-md-4 role_select_wrapper">
                            <label for="role">Perfil <span class="required">*</span></label>
                            <select class="form-control select-control" id="role" name="role"
                                onchange="toggleUsername(this.value);">
                                <option value="">Selecionar Perfil</option>
                                @foreach ($profiles ?? [] as $profile)
                                    <option value="{{ $profile->code }}" data-requires-username="{{ $profile->requires_username ? '1' : '0' }}">{{ $profile->name }}</option>
                                @endforeach
                            </select>
                            <div id="role_display" class="form-control bg-light" style="display:none; line-height:2.2;"></div>
                        </div>
                        <div class="col-md-4 username_div" style="display:none;">
                            <div class="form-group">
                                <label for="username">Nome de usuário</label>
                                <input type="text" class="form-control" id="username" name="username"
                                    placeholder="username">
                                <span class="error" id="username_error"></span>
                            </div>
                        </div>
                        <div class="col-md-4 password_div">
                            <div class="form-group">
                                <label for="password">Senha inicial</label>
                                <input type="password" class="form-control" id="password" name="password" placeholder="Deixe em branco para 12345678">
                                <small class="text-muted">Opcional. Padrão: 12345678</small>
                                <span class="error" id="password_error"></span>
                            </div>
                        </div>
                        <div class="col-md-4 ">
                            <label for="status">Status <span class="required">*</span></label>
                            <select class="form-control select-control" id="status" name="status">
                                <option value="">Selecionar Status</option>
                                <option value="active">Ativo</option>
                                <option value="inactive">Inativo</option>
                            </select>
                            <span class="error" id="status_error"></span>

                        </div>
                    </div>


                </form>
                <div class="row">
                    <div class="col-md-12">
                        <button onclick="addUpdateUser(1)"; class="btn btn-primary float-right" id="submit_button">
                            Adicionar Usuário </button>

                        <button onclick="addUpdateUser(2)"; class="btn btn-primary float-right" id="update_button"
                            style="display:none;"> Atualizar Usuário </button>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
