/*
 * ESP32 - Teste sensores/LEDs
 * Sensor fechado (HIGH) -> LED aceso
 */

#include <Arduino.h>

#define NUM_SLOTS 6
#define SENSOR_PINS { 32, 33, 34, 35, 26, 27 }
#define LED_PINS { 18, 5, 13, 14, 15, 16 }

const int sensorPins[NUM_SLOTS] = SENSOR_PINS;
const int ledPins[NUM_SLOTS] = LED_PINS;

void setup() {
    Serial.begin(115200);
    Serial.println("\n\n=== Sensores + LEDs ===");
    Serial.println("Sensor HIGH (fechado) -> LED aceso\n");

    for (int i = 0; i < NUM_SLOTS; i++) {
        pinMode(sensorPins[i], INPUT);
        pinMode(ledPins[i], OUTPUT);
        digitalWrite(ledPins[i], LOW);
    }
}

void loop() {
    for (int i = 0; i < NUM_SLOTS; i++) {
        bool fechado = (digitalRead(sensorPins[i]) == HIGH);
        digitalWrite(ledPins[i], fechado ? HIGH : LOW);
    }
    delay(50);
}
