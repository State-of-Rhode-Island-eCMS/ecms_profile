const babel = require('gulp-babel');
const eslint = require('gulp-eslint');
const gulp = require('gulp');
const rename = require('gulp-rename');
const sass = require('gulp-sass');
const stylelint = require('gulp-stylelint');
const sourcemaps = require('gulp-sourcemaps');
const autoprefixer = require('gulp-autoprefixer');

// Directories to search ES6 JavaScript files to compile. Files will be compiled
// to a .js file extension.
const javascriptFilePaths = [
  "docroot/modules/custom/**/*.es6.js",
  "docroot/themes/custom/**/*.es6.js",
  "docroot/profiles/contrib/ecms_profile/*/modules/custom/**/*.es6.js",
  "docroot/profiles/contrib/ecms_profile/*/themes/custom/**/*.es6.js",
];

// Directories to search SCSS files to compile. By default, node-sass does not
// compile files that begin with _.
const scssFilePaths = [
  "docroot/modules/custom/**/*.scss",
  "docroot/themes/custom/**/*.scss",
  "docroot/profiles/contrib/ecms_profile/*/modules/custom/**/*.scss",
  "docroot/profiles/contrib/ecms_profile/*/themes/custom/**/*.scss",
];

// Build tasks.
gulp.task('build:js', () => {
  return gulp
    .src(javascriptFilePaths)
    .pipe(eslint({
      parserOptions: {
        ecmaVersion: "6",
      }
    }))
    .pipe(eslint.format())
    .pipe(eslint.failAfterError())
    .pipe(babel({
      presets: ['env']
    }))
    .pipe(rename((path) => {
      path.basename = path.basename.replace('.es6', '');
    }))
    .pipe(gulp.dest((file) => {
      return file.base;
    }));
});

gulp.task('build:sass', () => {
  return gulp
    .src(scssFilePaths)
    .pipe(sourcemaps.init())
    .pipe(sass({
      includePaths: [
        "node_modules",
        "web/libraries",
      ]
    }))
    .pipe(sass().on('error', sass.logError))
    .pipe(autoprefixer({
      cascade: false
    }))
    .pipe(sourcemaps.write('.'))
    .pipe(gulp.dest((file) => {
      return file.base;
    }));
});

gulp.task('build', gulp.parallel('build:js', 'build:sass'));

// Watch tasks.
gulp.task('watch:js', () => {
  return gulp.watch(javascriptFilePaths, gulp.series('build:js'));
});

gulp.task('watch:sass', () => {
  return gulp.watch(scssFilePaths, gulp.series('build:sass'));
});

gulp.task('watch', gulp.parallel('watch:js', 'watch:sass'));

// Validation tasks
gulp.task('validate:js', () => {
  return gulp
    .src(javascriptFilePaths)
    .pipe(eslint({
      parserOptions: {
        ecmaVersion: "6",
      }
    }))
    .pipe(eslint.format())
    .pipe(eslint.failAfterError());
});

gulp.task('validate:sass', () => {
  return gulp
    .src(scssFilePaths)
    .pipe(stylelint({
      reporters: [
        {
          formatter: 'verbose',
          console: true,
        }
      ],
      debug: true,
    }))
});

gulp.task('validate', gulp.parallel('validate:js', 'validate:sass'));

// Syntax fixer tasks.
gulp.task('fix:js', () => {
  return gulp
    .src(javascriptFilePaths)
    .pipe(eslint({ fix: true }))
    .pipe(gulp.dest((file) => {
      return file.base;
    }))
});

gulp.task('fix:sass', () => {
  return gulp
    .src(scssFilePaths)
    .pipe(stylelint({ fix: true }))
    .pipe(gulp.dest((file) => {
      return file.base;
    }));
});

gulp.task('fix', gulp.parallel('fix:js', 'fix:sass'));
