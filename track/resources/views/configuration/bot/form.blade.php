<div class="row">
    <div class="col-md-12">


        <div class="card">

            <div class="card-body">
                <form method="POST" id="form" action="">
                    @csrf
                    <div class="row">

                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="key">Key<span class="required">*</span></label>
                                <input type="text" class="form-control" id="key" name="key"
                                    pattern="^[A-Za-z_]+$" placeholder="Enter key">
                                <span class="error" id="key_error"></span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="value">Value <span class="required">*</span></label>
                                <input type="text" class="form-control" id="value" name="value"
                                    placeholder="Enter value">
                                <span class="error" id="value_error"></span>
                            </div>
                        </div>
                        <div class="col-md-6 ">
                            <label for="type">Type <span class="required">*</span></label>
                            <select class="form-control select-control" id="type" name="type">
                                <option value="">Select Type</option>
                                <option value="number">NUMBER</option>
                                <option value="string">STRING</option>
                            </select>
                            <span class="error" id="type_error"></span>

                        </div>

                        <div class="col-md-6 ">
                            <label for="category">Category <span class="required">*</span></label>
                            <select class="form-control select-control" id="category" name="category">
                                <option value="">Select Category</option>
                                <option value="device">Device</option>
                                <option value="notification">Notification</option>
                                <option value="alert">Alert</option>
                                <option value="dock">Dock</option>
                            </select>
                            <span class="error" id="category_error"></span>

                        </div>

                        <div class="col-md-12 mt-2">
                            <div class="form-group">
                                <label for="description">Description</label>
                                <textarea rows="3" class="form-control" id="description" name="description" autocomplete="off"
                                    placeholder="Description"></textarea>
                                <span class="error" id="description_error"></span>
                            </div>
                        </div>
                    </div>


                </form>
                <div class="row">
                    <div class="col-md-12">
                        <button onclick="addUpdateBot(1)"; class="btn btn-primary float-right" id="submit_button">
                            Salvar </button>

                        <button onclick="addUpdateBot(2)"; class="btn btn-primary float-right" id="update_button"
                            style="display:none;"> Atualizar </button>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
