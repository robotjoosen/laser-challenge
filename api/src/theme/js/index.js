/**
 * Lasertag
 */

/** Load polyfills */
require('custom-event-polyfill');
require('es6-promise/auto');

/** Theme */
require('./components/bootstrap');

/** Classes */
require('./components/controller/game.controller');
require('./components/controller/device.controller');
require('./components/controller/score.controller');