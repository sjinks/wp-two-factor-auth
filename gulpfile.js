'use strict';

var gulp         = require('gulp');
var del          = require('del');
var autoprefixer = require('gulp-autoprefixer');
var rename       = require('gulp-rename');
var postcss      = require('gulp-postcss');
var sourcemaps   = require('gulp-sourcemaps');
var uglify       = require('gulp-uglify');
var prune        = require('gulp-prune');
var newer        = require('gulp-newer');
var imagemin     = require('gulp-imagemin');

gulp.task('clean:css', function() {
	return del(['assets/*.css', 'assets/*.css.map']);
})

gulp.task('clean:js', function() {
	return del(['assets/*.js', 'assets/*.js.map']);
});

gulp.task('clean:img', function() {
	return del(['assets/*.png']);
});

gulp.task('clean', gulp.series(['clean:js', 'clean:css', 'clean:img']));

gulp.task('img', function() {
	var dest = 'assets/';
	return gulp.src(['assets-dev/*.png'])
		.pipe(prune({ dest: dest, ext: ['.png'] }))
		.pipe(newer({ dest: dest }))
		.pipe(imagemin([
			imagemin.optipng({ optimizationLevel: 9 })
		]))
		.pipe(gulp.dest('assets'))
	;
});

gulp.task('css', function() {
	var src  = ['assets-dev/*.css'];
	var dest = 'assets/';
	return gulp.src(src)
		.pipe(prune({
			dest: dest,
			ext: ['.min.css.map', '.min.css']
		}))
		.pipe(newer({
			dest: dest,
			ext: '.min.css'
		}))
		.pipe(sourcemaps.init())
		.pipe(postcss([
			require('autoprefixer')({browsers: '> 5%'})
		]))
		.pipe(postcss([
			require('cssnano')()
		]))
		.pipe(rename({suffix: '.min'}))
		.pipe(sourcemaps.write('.'))
		.pipe(gulp.dest(dest))
	;
});

gulp.task('js', function() {
	var src  = ['assets-dev/*.js'];
	var dest = 'assets/';
	return gulp.src(src)
		.pipe(prune({
			dest: dest,
			ext: ['.min.js.map', '.min.js']
		}))
		.pipe(newer({
			dest: dest,
			ext: '.min.js'
		}))
		.pipe(sourcemaps.init())
		.pipe(uglify())
		.pipe(rename({suffix: '.min'}))
		.pipe(sourcemaps.write('.'))
		.pipe(gulp.dest(dest))
	;
});

gulp.task('default', gulp.parallel(['img', 'css', 'js']));
