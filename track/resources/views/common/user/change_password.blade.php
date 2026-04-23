<div class="row">
    <div class="col-md-12">


        <div class="card">

            <div class="card-body">
                <form method="POST" id="changePasswordForm">
                    @csrf
                    <div class="row">

                        <div class="col-md-12 mb-3">
                            <input type="hidden" name="role_input_password" id="role_input_password">
                            <label for="current_password">Senha Atual</label>
                            <input type="password" class="form-control" id="current_password" name="current_password" placeholder="Deixe em branco para definir nova senha (admin)">
                            <small class="text-muted">Deixe em branco se for definir a senha pela primeira vez ou se você é administrador resetando a senha.</small>
                            <span class="error text-danger" id="current_password_error"></span>
                        </div>

                        <div class="col-md-12 mb-3">
                            <label for="new_password">New Password <span class="required">*</span></label>
                            <input type="password" class="form-control" id="new_password" name="new_password">
                            <span class="error text-danger" id="new_password_error"></span>
                        </div>

                        <div class="col-md-12 mb-3">
                            <label for="new_password_confirmation">Confirmar Nova Senha <span
                                    class="required">*</span></label>
                            <input type="password" class="form-control" id="new_password_confirmation"
                                name="new_password_confirmation">
                            <span class="error text-danger" id="new_password_confirmation_error"></span>
                        </div>
                    </div>
                </form>
                <div class="row">
                    <div class="col-md-12">
                        <button type="button" onclick="changePassword()" class="btn btn-primary float-right"
                            id="password_submit_button">
                            Change Password
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
