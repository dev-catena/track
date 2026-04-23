<div class="row">
    <div class="col-md-12">
        <div class="card">

            <div class="card-body">
                <form method="POST" id="profileUpdateForm">
                    @csrf
                    <div class="row">

                        <div class="col-md-4 mb-3">
                            <input type="hidden" name="role_input_password" id="role_input_password"
                                value="{{ $user->role }}">
                            <label for="name">Nome Completo<span class="required">*</span></label>
                            <input type="text" class="form-control" id="name" name="name"
                                value="{{ $user->name }}">
                            <span class="error text-danger" id="name_error"></span>
                        </div>
                        <div class="col-md-4 mb-3">

                            <label for="email">Email<span class="required">*</span></label>
                            <input type="email" class="form-control" id="email" name="email"
                                value="{{ $user->email }}">
                            <span class="error text-danger" id="email_error"></span>
                        </div>
                        <div class="col-md-4 mb-3">

                            <label for="phone">Phone<span class="required">*</span></label>
                            <input type="text" class="form-control" id="phone" name="phone"
                                value="{{ $user->phone }}">
                            <span class="error text-danger" id="phone_error"></span>
                        </div>
                    </div>
                </form>
                <div class="row">
                    <div class="col-md-12">
                        <button type="button" onclick="updateProfile()" class="btn btn-primary float-right"
                            id="update_profile_button">
                            Atualizar Perfil
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
