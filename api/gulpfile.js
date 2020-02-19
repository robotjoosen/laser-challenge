const gulp = require('gulp');
const sass = require('gulp-sass');
const minify = require('gulp-clean-css');
const sourcemaps = require('gulp-sourcemaps');
const autoprefixer = require('gulp-autoprefixer');
const rename = require('gulp-rename');
const uglify = require('gulp-uglify');
const concat = require('gulp-concat');
const browserify = require("browserify");
const source = require('vinyl-source-stream');
const buffer = require('vinyl-buffer');
const env = require('dotenv').config();
const globify = require('require-globify');
const babelify = require('babelify');
const stringify = require('stringify');

let path = {
	js: {
		watch: './src/theme/js/**/*.js',
		src: 'src/theme/js/**/*.js',
		output: 'script.js',
		dest: 'public_html/assets/theme/js'
	},
	scss: {
		watch: './src/theme/scss/**/*.scss',
		src: 'src/theme/scss/*.scss',
		output: 'style.css',
		dest: 'public_html/assets/theme/css'
	}
};

/**
 * Compile SCSS to CSS
 * @return {*}
 */
function compileSCSS() {
	return (
		gulp
			.src([path.scss.src])
			.pipe(concat(path.scss.output))
			.pipe(sass({
				includePaths: ['node_modules']
			}))
			.on('error', sass.logError)
			.pipe(autoprefixer())
			.pipe(gulp.dest(path.scss.dest))
			.pipe(minify({compatibility: 'ie11'}))
			.pipe(rename({suffix: '.min'}))
			.pipe(sourcemaps.write())
			.pipe(gulp.dest(path.scss.dest))
	);
}

/**
 * Compile Javascript (ES6 to ES5)
 * @return {*}
 */
function compileJavascript() {
	try {
		return (
			browserify({
				entries: './src/theme/js/index.js',
				debug: true,
				transform: [babelify, globify, stringify]
			})
				.bundle()
				.pipe(source(path.js.output))
				.pipe(gulp.dest(path.js.dest))
				.pipe(buffer())
				.pipe(sourcemaps.init())
				.pipe(uglify({
					compress: {
						drop_console: (env.parsed.DEBUG !== '1')
					}
				}))
				.pipe(rename({suffix: '.min'}))
				.pipe(sourcemaps.write('./maps'))
				.pipe(gulp.dest(path.js.dest))
		);
	} catch (e) {
		console.warn(e);
	}
}

/**
 * Watch build files
 */
function watchFiles() {
	console.log({
		'scss': path.scss.watch,
		'js': path.js.watch,
		'env': env.parsed
	});
	gulp.watch(path.scss.watch, compileSCSS);
	gulp.watch(path.js.watch, compileJavascript);
}

const watch = gulp.parallel(watchFiles);
const build = gulp.parallel(compileSCSS, compileJavascript);
const js = gulp.parallel(compileJavascript);
const css = gulp.parallel(compileSCSS);

exports.default = watch;
exports.css = css;
exports.js = js;
exports.build = build;