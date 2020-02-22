'use strict';

import Controller from "../controller";
import Axios from "axios";

/**
 * Score Controller
 */
class ScoreController extends Controller {

	/**
	 * Score Controller constructor
	 * @param props
	 */
	constructor(props) {
		super(props);
		this.interval = {
			refresh: setInterval(this.refreshScore.bind(this), 20000)
		};
		this.refreshScore();
	}

	/**
	 * Refresh Score
	 */
	refreshScore() {
		Axios.get('/api/game')
			.then(response => {
				let template = document.getElementById('games-row-tmpl');
				let target = document.getElementById('games-row');
				let data = response.data;
				for(let key in data.games) {
					if (data.games.hasOwnProperty(key)) {
						let d = new Date(data.games[key].total_time * 1000);
						data.games[key].display_time = String(d.getMinutes()).padStart(2, '0') + ':' + String(d.getSeconds()).padStart(2, '0') + ':' + Math.floor(d.getMilliseconds() / 10).toFixed(0).padStart(2, '0');
					}
				}
				this.renderTmpl(target, template, data);
			})
			.catch(error => {
				console.warn(error);
			})
	}

}

new ScoreController();