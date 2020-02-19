/**
* Laser Challenge Target
* @author Roald Joosen <robotjoosen@gmail.com>
*/

// Include libraries
#include <Arduino.h>
#include <ESP8266WiFi.h>
#include <ESP8266WiFiMulti.h>
#include <ESP8266HTTPClient.h>
#include <WiFiClient.h>
#include <ESP8266WebServer.h>
#include <ESP8266mDNS.h>
#include <IRrecv.h>
#include <IRremoteESP8266.h>
#include <IRac.h>
#include <IRtext.h>
#include <IRutils.h>

// Change these
const String SSID = "SSID_NAME";
const String PASSWORD = "SSID_PASSWORD";
const String device_name = "DEVICE_NAME";
const String api_url = "http://192.168.178.82:8080/api/device/" + device_name;

// WIFI stuff
ESP8266WiFiMulti WiFiMulti;
ESP8266WebServer server(80);

// IR Sensor decoding
#define LEGACY_TIMING_INFO false
const uint16_t kRecvPin = 14;
const uint16_t kCaptureBufferSize = 1024;
const uint8_t kTimeout = 50;
const uint16_t kMinUnknownSize = 12;
IRrecv irrecv(kRecvPin, kCaptureBufferSize, kTimeout, true);
decode_results results;  // Somewhere to store the results

// Variables
int registered = false;
int hit = false;

/**
 * Server
 */

// Handle Root - Mainly used for detection
void handleRoot() {
    digitalWrite(LED_BUILTIN, 1);
    server.sendHeader("Access-Control-Allow-Origin", "*");
    server.send(200, "text/plain", "{\"success\" : 1, \"message\" : \"Index\"}");
    digitalWrite(LED_BUILTIN, 0);
}

// Handle Restart Request
void handleRestart() {
    digitalWrite(LED_BUILTIN, 1);
    hit = false;
    server.sendHeader("Access-Control-Allow-Origin", "*");
    server.send(200, "text/plain", "{\"success\" : 1, \"message\" : \"Restart is done\"}");
    digitalWrite(LED_BUILTIN, 0);
}

// Serve 404
void handleNotFound() {
    digitalWrite(LED_BUILTIN, 1);
    server.sendHeader("Access-Control-Allow-Origin", "*");
    server.send(404, "text/plain", "{\"success\" : 0, \"message\" : \"Page not found\"}");
    digitalWrite(LED_BUILTIN, 0);
}

/**
 * Setup
 */
void setup() {

    // Begin serial communication
    Serial.begin(115200);

    // Built-in LED
    pinMode(LED_BUILTIN, OUTPUT);
    digitalWrite(LED_BUILTIN, 0);

    // Ignore messages with less than minimum on or off pulses.
    #if DECODE_HASH
        irrecv.setUnknownThreshold(kMinUnknownSize);
    #endif

    // DECODE_HASH
    irrecv.enableIRIn();

    // Add a pause
    for (uint8_t t = 4; t > 0; t--) {
        Serial.printf("[SETUP] WAIT %d...\n", t);
        Serial.flush();
        delay(1000);
    }

    // Setup Wifi
    WiFi.mode(WIFI_STA);
    WiFiMulti.addAP(SSID, PASSWORD);

    // Setup Server
    if (MDNS.begin("esp8266")) {
        Serial.println("MDNS responder started");
    }
    server.on("/", handleRoot);
    server.on("/restart", handleRestart);
    server.onNotFound(handleNotFound);
    server.begin();

}

/**
 * Loop
 */
void loop() {
    server.handleClient();
    MDNS.update();
    if ((WiFiMulti.run() == WL_CONNECTED)) {

        WiFiClient client;
        HTTPClient http;

        // Check if device is registered
        if(!registered) {
            Serial.print("[HTTP] REGISTER...\n");

            // Register device
            if (http.begin(client, api_url + "/register/" + WiFi.localIP().toString())) {
                int httpCode = http.GET();
                if (httpCode == 200) {
                    Serial.println("Device is registered.");
                    String payload = http.getString();
                    Serial.println(payload);
                    registered = true;
                } else {
                    registered = true;
                    Serial.print("[HTTP] Code ");
                    Serial.println(httpCode);
                }
            } else {
                Serial.printf("[HTTP} Unable to connect\n");
            }
        } else {

            // Decode IR signal
            if (irrecv.decode(&results)) {
                uint32_t now = millis();

                // Handle IR Signal result
                switch(results.bits) {
                    case 35 : // Hit code detected

                        // Send hit notice to server
                        if (http.begin(client, api_url + "/hit")) {
                            int httpCode = http.GET();
                            if (httpCode == 200) {
                                Serial.println("Device hit is registered.");
                                String payload = http.getString();
                                Serial.println(payload);
                                hit = true;
                                delay(5000);
                            }
                        } else {
                            Serial.printf("[HTTP} Unable to connect\n");
                        }
                      break;
                    case 46 : // Reset code detected
                        hit = false;
                        Serial.println("Game Reset");
                        break;
                    default : // Not sure what happen
                        Serial.println("Something unknown happened");
                        Serial.printf(D_STR_TIMESTAMP " : %06u.%03u\n", now / 1000, now % 1000);
                        Serial.println(D_STR_LIBRARY "   : v" _IRREMOTEESP8266_VERSION_ "\n");
                        Serial.print(resultToHumanReadableBasic(&results));
                        String description = IRAcUtils::resultAcToString(&results);
                        if (description.length()) {
                            Serial.println(D_STR_MESGDESC ": " + description);
                        }
                        yield();
                        Serial.println(resultToSourceCode(&results));
                }
                yield();
            }
        }
    }
}
