const { src, dest, watch } = require("gulp");

// Import Gulp plugins.
const sass = require('gulp-sass');
const autoprefixer = require('gulp-autoprefixer');
const sourcemaps = require('gulp-sourcemaps');

// Compile SCSS
const scss = () => {
  sass.compiler = require('node-sass');
  return src('./_src/scss/**/*.scss')
    .pipe(sourcemaps.init())
    .pipe(sass({
      outputStyle: 'expanded',
      includePaths: ['./node_modules', '../themag/_src']
    }).on('error', sass.logError))
    .pipe(autoprefixer('last 2 version', 'ie 11'))
    .pipe(sourcemaps.write('./sourcemap/'))
    .pipe(dest('./assets/css'));
};

exports.default = () => {
  watch(['./_src/scss/**/*.scss'], scss);
};
