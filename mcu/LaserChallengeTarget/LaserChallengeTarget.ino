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

// WIFI stuff
ESP8266WiFiMulti WiFiMulti;
ESP8266WebServer server(80);

// Change these
const String device_name = "ESP8266-02";
const String api_url = "http://192.168.178.82:8080/api/device/" + device_name;

// IR Sensor decoding
#define LEGACY_TIMING_INFO false
const uint16_t kRecvPin = 14;
const uint16_t kCaptureBufferSize = 1024;
const uint8_t kTimeout = 50;
const uint16_t kMinUnknownSize = 12;
IRrecv irrecv(kRecvPin, kCaptureBufferSize, kTimeout, true);
decode_results results;  // Somewhere to store the results

// Variables
#define WIFI_LED 5
#define HIT_LED 4
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
    digitalWrite(HIT_LED, 0);
    server.sendHeader("Access-Control-Allow-Origin", "*");
    server.send(200, "text/plain", "{\"success\" : 1, \"message\" : \"Restart is done\"}");
    led_confirm();
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

    // Wifi LED
    pinMode(WIFI_LED, OUTPUT);
    digitalWrite(WIFI_LED, 0);

    // Hit LED
    pinMode(HIT_LED, OUTPUT);
    digitalWrite(HIT_LED, 0);

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

// holding led signals
void led_confirm() {
    for(int i=0; i<4; i++) {
      delay(25);
      digitalWrite(HIT_LED, 1);
      delay(25);
      digitalWrite(HIT_LED, 0);
    }
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
                    led_confirm();
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
                switch(results.value) {
                    case 0x2AAAAAAAA : // Hit code detected

                        // Send hit notice to server
                        if (http.begin(client, api_url + "/hit")) {
                            int httpCode = http.GET();
                            if (httpCode == 200) {

                                // debug info
                                Serial.println("Device hit is registered.");
                                String payload = http.getString();
                                Serial.println(payload);

                                // show hit
                                hit = true;
                                digitalWrite(HIT_LED, 1);
                                
                                // pause program
                                delay(5000);
                            }
                        } else {
                            Serial.printf("[HTTP} Unable to connect\n");
                        }
                        Serial.println(resultToSourceCode(&results));
                      break;
                    case 0xB94AF916 : // Reset code detected
                        hit = false;
                        digitalWrite(HIT_LED, 0);
                        Serial.println(resultToSourceCode(&results));
                        Serial.println("Game Reset");
                        break;
                    default : // Not sure what happen

                        // debug info
                        Serial.println("Something unknown happened");
                        Serial.printf(D_STR_TIMESTAMP " : %06u.%03u\n", now / 1000, now % 1000);
                        Serial.println(D_STR_LIBRARY "   : v" _IRREMOTEESP8266_VERSION_ "\n");
                        Serial.print(resultToHumanReadableBasic(&results));
                        String description = IRAcUtils::resultAcToString(&results);
                        if (description.length()) {
                            Serial.println(D_STR_MESGDESC ": " + description);
                        }
                        yield();
                        Serial.println(getCorrectedRawLength(&results));
                        Serial.println(resultToSourceCode(&results));
                }
                yield();
            }
        }
      digitalWrite(WIFI_LED, 1);
    } else {
      digitalWrite(WIFI_LED, 0);
    }
}
