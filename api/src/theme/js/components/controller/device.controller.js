'use strict';

import Controller from '../controller';
import Axios from "axios";
import Mustache from 'mustache';

/**
 * Devices Class
 */
class DeviceController extends Controller {

	/**
	 * Devices Constructor
	 */
	constructor() {
		super();
		this.delay = 30000;
		this.counter = 0;
		this.interval = {
			refresh: setInterval(this.getDevices.bind(this), this.delay),
			counter: setInterval(this.updateCounter.bind(this), 1000)
		};
		this.getDevices();
		this.eventListeners();
	}

	/**
	 * Get Device List
	 */
	getDevices() {
		Axios.get('/api/device')
			.then(response => {
				// parse data
				let data = {devices: []};
				for (let key in response.data.devices) {
					if (response.data.devices.hasOwnProperty(key)) {
						let device = response.data.devices[key];
						data.devices.push({
							'name': device.name,
							'ip': device.ip,
							'hits': device.stats.length,
							'state': 'inactive',
							'createdon': this.formatDate(device.createdon)
						});
					}
				}

				// render template
				let devices_template = document.getElementById('devices-row-tmpl');
				let target = document.getElementById('devices-row');
				if (devices_template && target) {
					Mustache.parse(devices_template.innerHTML);
					target.innerHTML = Mustache.render(devices_template.innerHTML, data);
				}
			})
			.then(() => {
				this.resetCounter();
			})
			.catch(error => {
				console.warn(error);
			});
	}

	/**
	 * Update Refresh Counter
	 */
	updateCounter() {
		let refresh_button = document.getElementById('devices-refresh');
		refresh_button.innerHTML = this.counter + 's &#8635;';
		this.counter--;
	}

	/**
	 * Reset Refresh Counter
	 */
	resetCounter() {
		this.counter = this.delay / 1000;
	}

	/**
	 * Listeners
	 */
	eventListeners() {

		// click events
		document.addEventListener('click', event => {
			if (event.target.id === 'devices-refresh') {
				this.resetCounter();
				this.getDevices();
				this.updateCounter();
				clearInterval(this.interval.refresh);
				this.interval.refresh = setInterval(this.getDevices.bind(this), this.delay);
			}
		});
	}

	/**
	 * Format Date
	 * @link https://stackoverflow.com/questions/15522036/how-to-format-mysql-timestamp-into-mm-dd-yyyy-his-in-javascript#15522294
	 * @param {string} value
	 * @return {string}
	 */
	formatDate(value) {
		if (value) {
			Number.prototype.padLeft = function (base, chr) {
				let len = (String(base || 10).length - String(this).length) + 1;
				return len > 0 ? new Array(len).join(chr || '0') + this : this;
			};
			let d = new Date(value);
			return [(d.getMonth() + 1).padLeft(),
					d.getDate().padLeft(),
					d.getFullYear()].join('/') +
				' ' +
				[d.getHours().padLeft(),
					d.getMinutes().padLeft(),
					d.getSeconds().padLeft()].join(':');
		}
	}

}

new DeviceController();