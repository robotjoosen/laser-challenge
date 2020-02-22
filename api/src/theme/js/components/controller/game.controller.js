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
		this.current_game = {
			id: 0,
			score: {}
		};
		this.stats = [];
		this.timer = 0;
		this.interval = {};
		this.getDevices();
		this.initGame();
		this.eventListeners();
	}

	initGame() {

		// restart timer
		this.timer = 0;

		// clear intervals
		for (let key in this.interval) {
			clearInterval(this.interval[key]);
		}

		// render view
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
		this.interval.score = window.setInterval(this.checkGameState.bind(this), 2000);
	}

	/**
	 * Timer
	 */
	gameTimer() {
		let template = document.getElementById('game-panel-play-tmpl');
		let target = document.getElementById('game-panel');
		let d = new Date(this.timer * 1000);
		let data = this.stats;
		data.timer = String(d.getMinutes()).padStart(2, '0') + ':' + String(d.getSeconds()).padStart(2, '0') + ':' + Math.floor(d.getMilliseconds() / 10).toFixed(0).padStart(2, '0');
		this.renderTmpl(target, template, data);
		this.timer += 0.01;
	}

	/**
	 * Check game status
	 */
	checkGameState() {
		if (this.current_game.hasOwnProperty('score')) {
			let id = this.current_game.id;
			Axios.get(`/api/game/${id}`)
				.then(response => {
					let data = response.data;
					let d = new Date(data.stats.total_time * 1000);
					data.stats.display_time = String(d.getMinutes()).padStart(2, '0') + ':' + String(d.getSeconds()).padStart(2, '0') + ':' + Math.floor(d.getMilliseconds() / 10).toFixed(0).padStart(2, '0');
					this.stats = data;
					if (response.data.stats.devices_hit === this.devices.length) {
						clearInterval(this.interval.timer);
						clearInterval(this.interval.score);
						let template = document.getElementById('game-panel-end-tmpl');
						let target = document.getElementById('game-panel');
						this.renderTmpl(target, template, data);
						Axios.post(`/api/game/${id}`, {'endtime': data.stats.last_hit})
							.then(response => {
								console.debug('End of game is set');
							})
							.catch(error => {
								console.warn(error);
							});

					}
				})
				.catch(error => {
					console.warn(error);
				});
		}
	}

	storeGame() {
		return Axios.get('/api/game/start');
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

			// start game
			if (event.target.id === 'game-start') {
				let setup_promises = [];
				for (let key in this.devices) {
					if (this.devices.hasOwnProperty(key)) {
						setup_promises.push(this.restartDevice(this.devices[key].ip));
					}
				}
				setup_promises.push(this.storeGame());
				Promise.all(setup_promises)
					.then(response => {
						Object.keys(response).forEach(key => {
							if (response[key].config.url === '/api/game/start') {
								this.current_game.id = response[key].data.id;
							}
						});
						this.startGame();
					})
					.catch(error => {
						console.warn(error);
					});
			}

			// restart
			if (event.target.id === 'game-restart') {
				this.initGame();
			}
		});
	}

}

new GameController();