const { src, dest, task, series, watch } = require("gulp");

// Import Gulp plugins.
const sass = require('gulp-sass');
const autoprefixer = require('gulp-autoprefixer');
const sourcemaps = require('gulp-sourcemaps');
const babel = require('gulp-babel');
const plumber = require('gulp-plumber');
const uglify = require('gulp-uglify');
const run = require('gulp-run');

// Transpile JavaScripts
const javascript = () => {
  return src('./_src/themag/js/**/*.js')
    // Init source maps
    // .pipe(sourcemaps.init())
    // Stop the process if an error is thrown.
    .pipe(plumber())
    // Transpile the JS code using Babel's preset-env.
    .pipe(babel({
      presets: [
        ['@babel/env', {
          modules: false
        }]
      ]
    }))
    // .pipe(sourcemaps.write())
    // Save each component as a separate file in dist.
    .pipe(uglify())
    .pipe(dest('./assets/js'))
};


// Compile SCSS
const scss = () => {
  sass.compiler = require('node-sass');
  return src('./_src/themag/scss/**/*.scss')
    .pipe(sourcemaps.init())
    .pipe(sass({
      outputStyle: 'expanded',
      includePaths: ['./node_modules']
    }).on('error', sass.logError))
    .pipe(autoprefixer('last 2 version', 'ie 11'))
    .pipe(sourcemaps.write('./sourcemap/'))
    .pipe(dest('./assets/css'));
};

exports.default = () => {
  watch(['./_src/themag/scss/**/*.scss'], scss);
  watch(['./_src/themag/js/**/*.js'], javascript);
};
