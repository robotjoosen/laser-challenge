'use strict';

import Mustache from "mustache";

/**
 * Controller Class
 */
export default class Controller {

	/**
	 * Controller Constructor
	 */
	constructor() {

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

	/**
	 * Render Mustache Template
	 * @param {Element} target
	 * @param {Element} template
	 * @param {Object} data
	 */
	renderTmpl(target, template, data = {}) {
		if (template && target) {
			Mustache.parse(template.innerHTML);
			target.innerHTML = Mustache.render(template.innerHTML, data);
		}
	}

}