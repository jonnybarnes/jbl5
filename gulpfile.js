var gulp = require('gulp');
var gzip = require('gulp-gzip');
var elixir = require('laravel-elixir');

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
    mix.sass('global.scss', 'public/assets/css');
    mix.version([
        'assets/css/global.css',
        'assets/css/projects.css',
        'assets/css/alertify.css',
        'assets/css/normalize.css',
        'assets/js/form-save.js',
        'assets/js/links.js',
        'assets/js/maps.js',
        'assets/js/newplace.js',
        'assets/js/newnote.js',
        'assets/js/fetch.js',
        'assets/js/alertify.js',
        'assets/js/store2.min.js',
    ]);
});

gulp.task('gzip-built-css', function() {
    return gulp.src('public/build/assets/css/*.css')
        .pipe(gzip({ gzipOptions: { level: 9 }, append: true }))
        .pipe(gulp.dest('public/build/assets/css/'));
});

gulp.task('gzip-built-js', function() {
    return gulp.src('public/build/assets/js/*.js')
        .pipe(gzip({ gzipOptions: { level: 9 }, append: true }))
        .pipe(gulp.dest('public/build/assets/js/'));
});

gulp.task('bower', function() {
    //copy JS files
    gulp.src([
            'bower_components/fetch/fetch.js',
            'bower_components/alertify.js/dist/js/alertify.js',
            'bower_components/store2/dist/store2.min.js',
        ])
        .pipe(gulp.dest('public/assets/js/'));
    //copy CSS files
    gulp.src([
            'bower_components/alertify.js/dist/css/alertify.css',
            'bower_components/normalize-css/normalize.css',
        ])
        .pipe(gulp.dest('public/assets/css/'));
});
