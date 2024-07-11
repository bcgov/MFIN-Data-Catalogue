const { src, dest, series, parallel, watch} = require("gulp");
const sass = require('gulp-sass');
const sassLint = require('gulp-sass-lint');
const autoprefixer = require('gulp-autoprefixer');
const sourcemaps = require('gulp-sourcemaps');


function scssLint() {
    return src('./scss/**/*.s+(a|c)ss')
        .pipe(sassLint({
            configFile: 'sass-lint.yml'
        }))
        .pipe(sassLint.format())
        .pipe(sassLint.failOnError());
}


function scss() {
    sass.compiler = require('node-sass');
    return src('./assets/scss/**/*.scss')
        // .pipe(sourcemaps.init())
        .pipe(sass({
                outputStyle: 'compressed',
                includePaths: './node_modules'
            }).on('error', sass.logError))
        .pipe(autoprefixer('>1%','last 2 version','ie >= 11'))
        // .pipe(sourcemaps.write('./'))
        .pipe(dest('./assets/css'));
}


function watcher() {
    watch(['./assets/scss/**/*.scss'], scss);
}


exports.scss = scss;
exports.scssLint = scssLint;

exports.watcher = watcher;
exports.default = scss;
