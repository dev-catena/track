<div class="row mt-3">
    <div class="col-md-12">

        <div class="card">

            <div class="card-body">
                <form method="POST" id="form" action="">
                    @csrf
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="form-group">
                                <input type="hidden" name="user_id" value="{{ $setting->user_id }}">
                                <input type="hidden" id="system_id" name="system_id" value="{{ $setting->id }}">
                                <label for="theme">Theme</label>
                                <select class="form-control select-control" id="theme" name="theme">
                                    <option value="">Select Theme</option>
                                    <option value="light" {{ $setting?->theme == 'light' ? 'selected' : '' }}>Light
                                    </option>
                                    <option value="dark" {{ $setting?->theme == 'dark' ? 'selected' : '' }}>Dark
                                    </option>
                                </select>
                                <span class="error" id="theme_error"></span>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="form-group">
                                <label for="language">Language</label>
                                <select class="form-control select-control" id="language" name="language">
                                    <option value="">Select Language</option>
                                    <option value="en" {{ $setting?->language == 'en' ? 'selected' : '' }}>English
                                    </option>
                                    <option value="pt" {{ $setting?->language == 'pt' ? 'selected' : '' }}>Português
                                    </option>
                                </select>
                                <span class="error" id="language_error"></span>
                            </div>
                        </div>
                        <div class="col-md-6 ">
                            <div class="form-group">
                                <label for="time_zone">Timezone</label>
                                <select class="form-control select-control" id="time_zone" name="time_zone">
                                    <option value="">Select Timezone</option>
                                    <option value="America/Sao_Paulo"
                                        {{ $setting?->time_zone == 'America/Sao_Paulo' ? 'selected' : '' }}>São Paulo
                                    </option>
                                    {{-- <option value="Asia/Kolkata"
                                        {{ $setting?->time_zone == 'Asia/Kolkata' ? 'selected' : '' }}>India</option> --}}

                                </select>
                                <span class="error" id="time_zone_error"></span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="date_format">Date Format</label>
                                <select class="form-control select-control" id="date_format" name="date_format">
                                    <option value="">Select Date Format</option>
                                    <option value="d/m/Y" {{ $setting?->date_format == 'd/m/Y' ? 'selected' : '' }}>
                                        DD/MM/YYYY</option>
                                    <option value="m/d/Y" {{ $setting?->date_format == 'm/d/Y' ? 'selected' : '' }}>
                                        MM/DD/YYYY
                                    </option>
                                    <option value="Y/m/d" {{ $setting?->date_format == 'Y/m/d' ? 'selected' : '' }}>
                                        YYYY/MM/DD
                                    </option>
                                </select>
                                <span class="error" id="date_format_error"></span>
                            </div>
                        </div>
                    </div>

                </form>
                <div class="row">
                    <div class="col-md-12">
                        <button onclick="updateSystemSetting()"; class="btn btn-primary float-right"
                            id="submit_button">Update</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
