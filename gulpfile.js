var gulp = require('gulp');
var concat = require('gulp-concat');
var sass = require('gulp-sass');
var cssmin = require('gulp-cssmin');
var postcss = require('gulp-postcss');
var autoprefixer = require('autoprefixer');

function reportChange(event) {
    console.log('File ' + event.path + ' was ' + event.type + ', running tasks...');
}

/**
 * Build the main css file.
 */
gulp.task('build-css', function () {
    gulp.src('src/assets/array.scss')
        .pipe(sass().on('error', sass.logError))
        .pipe(postcss([ autoprefixer({
            browsers: [
                'Firefox >= 5',
                'Chrome >= 10',
                'Explorer >= 8',
                'iOS >= 8',
                'ChromeAndroid >= 41',
                'FirefoxAndroid >= 40'
            ]
        }) ]))
        .pipe(cssmin())
        .pipe(concat('array.min.css'))
        .pipe(gulp.dest('src/assets'));
});

gulp.task('watch', ['default'], function () {
    gulp.watch('src/assets/**/*.scss', ['build-css']).on('change', reportChange);
});

/**
 * Execute all assets tasks.
 */
gulp.task('default', ['build-css'], function () {});
