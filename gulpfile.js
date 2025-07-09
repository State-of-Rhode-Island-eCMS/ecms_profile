/******************************************************
 * RI Gov Pattern Lab - Gulp File
 * Uses pattern-lab/edition-node-gulp as a scaffold
 * and then adds on CSS / JS compilation
 ******************************************************/

const gulp = require('gulp');
const babel = require('gulp-babel');
const argv = require('minimist')(process.argv.slice(2));
const rename = require('gulp-rename');
const sass = require('gulp-sass');
const sassGlob = require('gulp-sass-glob');
const concat = require('gulp-concat');
const minify = require('gulp-minify');
const autoprefixer = require('gulp-autoprefixer');
const stylelint = require('gulp-stylelint');
const sassImportJson = require('gulp-sass-import-json');
const jeditor = require('gulp-json-editor');
const streamify = require('gulp-streamify');
const Hjson = require('gulp-hjson');

/******************************************************
 * Custom Tasks - CSS / JS Compilation
 ******************************************************/

// Source directories to search for SCSS / JS files to compile.
// By default, node-sass does not compile files that begin with _.
const scssSourcePaths = [
  // "./assets/patterns/**/*.scss",
  "ecms_base/themes/custom/ecms/assets/styles/*.scss",
  "ecms_base/themes/custom/ecms/components/**/*.scss",
];

const javascriptSourcePaths = [
  "ecms_base/themes/custom/ecms/assets/scripts/*.js",
  "!ecms_base/themes/custom/ecms/assets/scripts/scripts-compiled*.js",
];

// Generate colors file
gulp.task('convert-hjson', function () {
  return gulp
    .src("ecms_base/themes/custom/ecms/assets/data/color-config.hjson")
    .pipe(Hjson({ to: 'json' }))
    .pipe(gulp.dest('ecms_base/themes/custom/ecms/assets/data/'));
});

gulp.task('generate-colors', function () {
  return gulp
    .src("ecms_base/themes/custom/ecms/assets/data/color-config.json")
    .pipe(streamify(jeditor(function (json) {
      const generatedJson = {}
      const colorArray = [];

      for (let [color, value] of Object.entries(json.colors)) {
        colorArray.push({
          name: color,
          hsl: value.hsl
        })
      }

      for (let [palette, value] of Object.entries(json.palettes)) {
        for (let [block, blockValue] of Object.entries(value.values)) {
          blockValue.map((modifier) => {
            const hslValue = colorArray.find((item) => item.name === modifier.colorName);
            if(hslValue) {
              generatedJson[`t__${palette}__${block}__${modifier.fnName}`] = hslValue.hsl;
            } else {
              console.error(palette + '-' + modifier.fnName + ': does not contain a color name in colors array.');
            }
          })
        };
      }

      return generatedJson;
    })))
    .pipe(rename('colors-generated.json'))
    .pipe(gulp.dest('ecms_base/themes/custom/ecms/assets/data/'));
});

// Build Tasks
gulp.task('build:js', () => {
  return gulp
    .src(javascriptSourcePaths)
    .pipe(babel({
      presets: ['@babel/env']
    }))
    .pipe(concat("scripts-compiled.js"))
    .pipe(minify())
    .pipe(gulp.dest('ecms_base/themes/custom/ecms/assets/scripts'));
});

gulp.task('build:sass', () => {
  return gulp
    .src(scssSourcePaths)
    .pipe(sassGlob())
    .pipe(sassImportJson({isScss: true}))
    .pipe(sass().on('error', sass.logError))
    .pipe(gulp.dest((file) => {
      return file.base;
    }));
});

// Build All
gulp.task('build', gulp.series('build:js', gulp.parallel('convert-hjson'), 'generate-colors', 'build:sass'));

// Build only SASS JS
gulp.task('build:no-patterns', gulp.parallel('build:js', 'build:sass'));

// Watch tasks
gulp.task('watch:js', () => {
  return gulp.watch(javascriptSourcePaths, gulp.series('build:js'));
});

gulp.task('watch:sass', () => {
  return gulp.watch(scssSourcePaths, gulp.series('build:sass'));
});

gulp.task('watch', gulp.parallel('watch:js', 'watch:sass'));

// Default task
gulp.task('default', gulp.series(gulp.parallel('convert-hjson'), 'generate-colors', 'build:no-patterns', 'watch'));

// Linting
gulp.task('validate:sass', () => {
  return gulp
    .src(scssSourcePaths)
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

gulp.task('fix:sass', () => {
  return gulp
    .src(scssSourcePaths)
    .pipe(stylelint({fix: true}))
    .pipe(gulp.dest((file) => {
      return file.base;
    }));
});
