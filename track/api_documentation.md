
# API Documentation

## Table of Contents
1. [Overview](#1-overview)
2. [Authentication](#2-authentication)
3. [Operator](#3-operator)
4. [Status Codes](#4-status-codes)
5. [Response Structure](#5-response-structure)

---

## 1. Overview
This document provides detailed information about the API endpoints for the Roboflex application. It covers authentication mechanisms and operator-related operations. All API requests should be made with the `Accept: application/json` header.

---

## 2. Authentication

### Login V2
**Endpoint:** `POST /api/auth/v2/login`

**Description:**
This endpoint authenticates an operator using various methods including email, username, QR code, or face recognition. It returns an access token and user details upon successful authentication.

**Request Body Parameters:**

| Parameter | Type | Required | Description |
| :--- | :--- | :--- | :--- |
| `type` | string | Yes | The login method. Allowed values: `email`, `username`, `qr_login`, `face_login`. |
| `fcm_token` | string | Yes | Firebase Cloud Messaging token for push notifications. |
| `email` | string | Conditional | Required if `type` is `email`. |
| `username` | string | Conditional | Required if `type` is `username`. |
| `password` | string | Conditional | Required if `type` is `email` or `username`. |
| `token` | string | Conditional | Required if `type` is `qr_login` (the QR token). |
| `image` | file | Conditional | Required if `type` is `face_login`. Max size 4096KB. formats: jpg, jpeg, png. |

**Success Response:**
```json
{
    "status": 1,
    "message": "Login success!",
    "data": {
        "token": "19|AbCdEfGhIdTk...",
        "operator": {
            "id": 1,
            "name": "John Doe",
            "email": "john@example.com",
            "username": "johndoe",
            "phone": "1234567890",
            "organization_id": 1,
            "department_id": 2,
            "last_picked_device_name": "Device-X1",
            "last_picked_device_datetime": "30/06/2025 09:21:17"
        }
    }
}
```

---

## 3. Operator

### Get Reports
**Endpoint:** `GET /api/reports`

**Description:**
Retrieves the checkout history and reports for the authenticated operator. It fetches records for the last 30 days, including device information and check-in/check-out statuses.

**Headers:**
- `Authorization`: `Bearer <token>`

**Success Response:**
```json
{
    "status": 1,
    "message": "Data retrieved successfully!",
    "data": {
        "organization": { "id": 1, "name": "Tech Corp" },
        "department": { "id": 2, "name": "Logistics" },
        "reports": [
            {
                "device_name": "Robot-A1",
                "last_pickup_date": "15/12/2025 10:00:00",
                "return_date": "15/12/2025 18:00:00",
                "status": 1,
                "status_message": "Delivered"
            }
        ]
    }
}
```

### Validate User (Face Auth)
**Endpoint:** `POST /api/user/validate`

**Description:**
Validates an operator's identity using facial recognition against a specific device. This is typically used before checking out a device to ensure the correct operator is unlocking it.

**Headers:**
- `Authorization`: `Bearer <token>`

**Request Body Parameters:**

| Parameter | Type | Required | Description |
| :--- | :--- | :--- | :--- |
| `image` | file | Yes | Face image for validation. Max 4MB. |
| `latitude` | numeric | Yes | Current latitude of the user (-90 to 90). |
| `longitude` | numeric | Yes | Current longitude of the user (-180 to 180). |
| `device_serial_number` | string | Yes | Serial/Build number of the device being accessed. |

**Success Response:**
```json
{
    "status": 1,
    "message": "User validated successfully, You can Checkout the device!",
    "data": { ...checkout_details... }
}
```

### Device Check-in
**Endpoint:** `POST /api/device/checkin`

**Description:**
Performs a check-in action for a device, marking it as returned by the operator.

**Headers:**
- `Authorization`: `Bearer <token>`

**Request Body Parameters:**

| Parameter | Type | Required | Description |
| :--- | :--- | :--- | :--- |
| `device_id` | integer | Yes | ID of the device to check in. |
| `latitude` | numeric | Yes | Latitude where check-in is occurring. |
| `longitude` | numeric | Yes | Longitude where check-in is occurring. |

**Success Response:**
```json
{
    "status": 1,
    "message": "Device checked in successfully!"
}
```

### Capture Device Location
**Endpoint:** `POST /api/device/location/capture`

**Description:**
Logs the current geolocation of a specific device.

**Headers:**
- `Authorization`: `Bearer <token>`

**Request Body Parameters:**

| Parameter | Type | Required | Description |
| :--- | :--- | :--- | :--- |
| `device_id` | integer | Yes | ID of the device. |
| `latitude` | numeric | Yes | Latitude (-90 to 90). |
| `longitude` | numeric | Yes | Longitude (-180 to 180). |
| `logged_at` | date | Optional | Custom timestamp for the log. |

**Success Response:**
```json
{
    "status": 1,
    "message": "Device location captured successfully!",
    "data": { ...location_record... }
}
```

---

## 4. Status Codes

The API uses a custom `status` field in the JSON response to indicate the result of the operation:

- **`1`**: **Success**. The operation completed successfully.
- **`0`**: **Failure**. The operation failed due to validation errors, invalid credentials, or server-side issues. The `message` field will contain the error details.

Example Error:
```json
{
    "status": 0,
    "message": "Invalid credentials!",
    "data": {}
}
```

---

## 5. Response Structure

All API responses follow a standard JSON envelope structure:

```json
{
    "status": <integer>,    // 1 for success, 0 for failure
    "message": <string>,    // A human-readable message describing the result
    "data": <mixed>         // The payload data, or an empty object if none
}
```
