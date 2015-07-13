var gulp = require('gulp');
var sass = require('gulp-ruby-sass');
var prefix = require('gulp-autoprefixer');
var minifycss = require('gulp-minify-css');
var rename = require('gulp-rename');

/*
 |--------------------------------------------------------------------------
 | Elixir Asset Management
 |--------------------------------------------------------------------------
 |
 | Elixir provides a clean, fluent API for defining some basic Gulp tasks
 | for your Laravel application. By default, we are compiling the Sass
 | file for our application, as well as publishing vendor resources.
 |
 */

elixir(function(mix) {
    mix.sass('app.scss');
});

gulp.task('sass', function() {
	return gulp.src('scss/global.scss')
		.pipe(sass({ style: 'expanded'}))
		.on('error', function (err) { console.log(err.message); })
		.pipe(gulp.dest('public/assets/css'))
});

gulp.task('default', ['sass'], function() {
	return gulp.src('public/assets/css/global.css')
		.pipe(prefix('last 2 versions'))
		.pipe(minifycss({processImport:false}))
		.pipe(rename('style.css'))
		.pipe(gulp.dest('public/assets/css'))
});
