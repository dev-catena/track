# Roboflex

Roboflex is a comprehensive Device and Operator Management System built with Laravel. It facilitates the management of robotic devices, docks, and operators, ensuring secure access via facial recognition and providing real-time tracking and reporting.

## Key Features

-   **Role-Based Access Control**:
    -   **SuperAdmin**: Full system control, company and department management.
    -   **Admin**: Organization-level management (Devices, Users, Docks).
    -   **Manager**: Department-level oversight.
    -   **Operator**: Device interactions (Check-in/Check-out, Reporting).
-   **Device Management**: Track device status, location, and usage history.
-   **Operator Authentication**:
    -   Standard Login (Email/Username & Password).
    -   QR Code Login.
    -   **Face Login**: Secure authentication using facial recognition (integrated with Luxand/DeepFace).
-   **Dock Management**: MQTT-based integration for dock control and status monitoring.
-   **Notifications**: Firebase Cloud Messaging (FCM) integration for real-time alerts.
-   **Reporting**: Detailed audit trails for device usage and operator activities.

## Requirements

-   PHP ^8.2
-   MySQL
-   Composer

## Installation

1. **Clone the repository:**

    ```bash
    git clone <repository-url>
    cd roboflex
    ```

2. **Install PHP dependencies:**

    ```bash
    composer install
    ```

3. **Environment Setup:**
   Copy the example environment file and configure your database and other credentials.

    ```bash
    cp .env.example .env
    ```

    Update `.env` with your settings:

    - `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
    - `FIREBASE_CREDENTIALS` (for FCM)

4. **Generate Application Key:**

    ```bash
    php artisan key:generate
    ```

5. **Run Migrations:**
   Set up the database tables.

    ```bash
    php artisan migrate
    ```

6. **Seed Initial Data:**
   Populate the database with initial plans.

    ```bash
    php artisan db:seed
    ```

7. **Install Node Dependencies & Build Assets:**
    ```bash
    npm install
    npm run build
    ```

## Creating an Admin User

Since there are no default users seeded, you will need to create a SuperAdmin user manually using Tinker.

1. Open Tinker:

    ```bash
    php artisan tinker
    ```

2. Run the following command to create a SuperAdmin:

    ```php
    use App\Models\User;
    use Illuminate\Support\Facades\Hash;

    User::create([
        'name' => 'Super Admin',
        'email' => 'admin@roboflex.com',
        'password' => Hash::make('password'),
        // Ensure you assign the 'superadmin' role here,
        // or manually set the role column if it exists on the users table.
    ]);
    ```

    _(Note: Adjust the fields based on your specific `users` table structure if different.)_

## Usage

### Web Interface

-   Access the web interface at `http://localhost:8000` (ou `php artisan serve --port=8000`).
-   Login with the credentials created above.
-   Dashboard provides an overview of organizations, devices, and system health.

### API Documentation

The API documentation is available in `api_documentation.md`.

-   **Operators** use the API for mobile/device interactions.
-   Base URL: `http://localhost/api`

### API Authentication (Operators)

Operators authenticate via:

-   `POST /api/auth/v2/login`

## Firmware ESP32

O firmware fica em `track/esp32-firmware/`. Comandos para compilar e fazer upload:

```bash
cd esp32-firmware
pio run
pio run -t upload
pio device monitor   # monitor serial
```

## Diagrama de conexão (ESP32 / Docas)

| GPIO | Função |
|------|--------|
| 32 | Sensor slot 1 |
| 33 | Sensor slot 2 |
| 34 | Sensor slot 3 |
| 35 | Sensor slot 4 |
| 26 | Sensor slot 5 |
| 27 | Sensor slot 6 |
| 18 | LED locker 1 |
| 5 | LED locker 2 |
| 13 | LED locker 3 |
| 14 | LED locker 4 |
| 15 | LED locker 5 |
| 16 | LED locker 6 |

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
