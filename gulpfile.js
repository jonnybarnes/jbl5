var gulp = require('gulp');
var sass = require('gulp-ruby-sass');
var prefix = require('gulp-autoprefixer');
var minifycss = require('gulp-minify-css');
var rename = require('gulp-rename');
var gzip = require('gulp-gzip');

gulp.task('sass', function() {
	return gulp.src('scss/global.scss')
		.pipe(sass({ style: 'expanded'}))
		.on('error', function (err) { console.log(err.message); })
		.pipe(gulp.dest('public/assets/css'))
});

gulp.task('process-sass', ['sass'], function() {
	return gulp.src('public/assets/css/global.css')
		.pipe(prefix('last 2 versions'))
		.pipe(minifycss({processImport:false}))
		.pipe(rename('style.css'))
		.pipe(gulp.dest('public/assets/css'))
});

gulp.task('gzip-css', ['process-sass'], function() {
	return gulp.src('public/assets/css/*.css')
		.pipe(gzip({ gzipOptions: { level: 9 }, append: true }))
		.pipe(gulp.dest('public/assets/css'))
});

gulp.task('gzip-js', function() {
	gulp.src('public/assets/js/*.js')
		.pipe(gzip({ gzipOptions: { level: 9 }, append: true }))
		.pipe(gulp.dest('public/assets/js'))
});

gulp.task('gzip-fa-css', function() {
	return gulp.src('public/assets/font-awesome/css/font-awesome.min.css')
		.pipe(gzip({ gzipOptions: { level: 9 }, append: true }))
		.pipe(gulp.dest('public/assets/font-awesome/css'))
});

gulp.task('gzip-fa-fonts', function() {
	return gulp.src('public/assets/font-awesome/fonts/*')
		.pipe(gzip({ gzipOptions: { level: 9 }, append: true }))
		.pipe(gulp.dest('public/assets/font-awesome/fonts'))
});

gulp.task('default', [
	'gzip-css',
	'gzip-js',
	'gzip-fa-css'
]);
