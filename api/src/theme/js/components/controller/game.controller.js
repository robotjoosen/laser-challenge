'use strict';

import Controller from '../controller';
import Axios from "axios";

/**
 * Games Class
 */
class GameController extends Controller {

	/**
	 * Games Constructor
	 */
	constructor() {
		super();
		this.devices = [];
		this.games = [];
		this.current_game = 0;
		this.timer = 0;
		this.interval = {};
		this.getDevices();
		this.initGame();
		this.eventListeners();
	}

	initGame() {
		let template = document.getElementById('game-panel-begin-tmpl');
		let target = document.getElementById('game-panel');
		this.renderTmpl(target, template);
	}

	/**
	 * Get devices
	 */
	getDevices() {
		Axios.get('/api/device')
			.then(response => {
				for (let key in response.data.devices) {
					if (response.data.devices.hasOwnProperty(key)) {
						let device = response.data.devices[key];
						this.devices.push({
							'name': device.name,
							'ip': device.ip,
							'hits': device.stats.length,
							'state': 'inactive',
							'createdon': this.formatDate(device.createdon)
						});
					}
				}
			})
			.catch(error => {
				console.warn(error);
			});
	}

	/**
	 * Start game
	 */
	startGame() {

		this.interval.timer = window.setInterval(this.gameTimer.bind(this), 10);
		this.interval.score = window.setInterval(this.checkGameScore().bind(this), 2000);
	}

	/**
	 * Game Count Down
	 */
	gameCountDown() {

	}

	/**
	 * Timer
	 */
	gameTimer() {
		let template = document.getElementById('game-panel-play-tmpl');
		let target = document.getElementById('game-panel');
		let data = {timer: this.timer.toFixed(2)};
		this.renderTmpl(target, template, data);
		this.timer += 0.01;
	}

	/**
	 * Check device status
	 */
	checkGameScore() {
		if (this.current_game.hasOwnProperty('score')) {
			let id = this.current_game.id;
			Axios.get(`/api/game/${id}/score`)
				.then(response => {
					console.log(response);
				})
				.catch(error => {
					console.warn(error);
				});
		}
	}

	/**
	 * Restart Device Promise
	 * @param {string} ip
	 * @return {*}
	 */
	restartDevice(ip) {
		return Axios.get('http://' + ip + '/restart');
	}

	/**
	 * Add listeners
	 */
	eventListeners() {
		document.addEventListener('click', event => {
			if (event.target.id === 'game-start') {
				let restart_promises = [];
				for (let key in this.devices) {
					if (this.devices.hasOwnProperty(key)) {
						restart_promises.push(this.restartDevice(this.devices[key].ip));
					}
				}
				Promise.all(restart_promises)
					.then(response => {
						console.log(response);
						this.startGame();
					})
					.catch(error => {
						console.warn(error);
					});
			}
		});
	}

}

new GameController();