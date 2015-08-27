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
		'assets/js/form-save.js',
		'assets/js/links.js',
		'assets/js/maps.js'
	]);
});
