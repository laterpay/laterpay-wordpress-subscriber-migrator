var gulp    = require('gulp'),
    plugins = require('gulp-load-plugins')(),
    del     = require('del'),
    p       = {
                srcSCSS     : './asset_sources/scss/*.scss',
                srcJS       : './asset_sources/js/',
                distJS      : './built_assets/js/',
                distCSS     : './built_assets/css/',
            };


// TASKS ---------------------------------------------------------------------------------------------------------------
// clean up all files in the target directories
gulp.task('clean', function(cb) {
    del([p.distJS + '*.js', p.distCSS + '*.css'], cb);
});

// CSS-related tasks
gulp.task('css-watch', function() {
    gulp.src(p.srcSCSS)
        .pipe(plugins.sourcemaps.init())
        .pipe(plugins.sass({
            errLogToConsole : true,
            sourceComments  : 'normal'
        }))
        .pipe(plugins.autoprefixer('last 3 versions', '> 2%', 'ff > 23', 'ie > 8')) // vendorize properties for supported browsers
        .on('error', plugins.notify.onError())
        .pipe(plugins.sourcemaps.write('./maps'))                               // write sourcemaps
        .pipe(gulp.dest(p.distCSS));                                            // move to target folder
});

gulp.task('css-build', function() {
    gulp.src(p.srcSCSS)
        .pipe(plugins.sourcemaps.init())
        .pipe(plugins.sass({
            errLogToConsole : true,
            sourceComments  : 'normal'
        }))
        .on('error', plugins.notify.onError())
        .pipe(plugins.autoprefixer('last 3 versions', '> 2%', 'ff > 23', 'ie > 8')) // vendorize properties for supported browsers
        .pipe(gulp.dest(p.distCSS));                                            // move to target folder
});

// Javascript-related tasks
gulp.task('js-watch', function() {
    gulp.src(p.srcJS + '*.js')
        .pipe(plugins.cached('hinting'))                                        // only process modified files
        .pipe(plugins.jshint('.jshintrc'))
        .pipe(plugins.jshint.reporter(plugins.stylish))
        .pipe(plugins.sourcemaps.init())
        .pipe(plugins.sourcemaps.write('./maps'))                               // write sourcemaps
        .pipe(gulp.dest(p.distJS));                                             // move to target folder
});

gulp.task('js-build', function() {
    gulp.src(p.srcJS + '*.js')
        .pipe(plugins.jshint('.jshintrc'))
        .pipe(plugins.jshint.reporter(plugins.stylish))
        .pipe(plugins.uglify())                                                 // compress with uglify
        .pipe(gulp.dest(p.distJS));                                             // move to target folder
});

// Git-related tasks
gulp.task('updateSubmodules', function() {
    plugins.git.updateSubmodule({args: '--init'});
});


// COMMANDS ------------------------------------------------------------------------------------------------------------
gulp.task('default', ['clean', 'css-watch', 'js-watch'], function() {
    // watch for changes
    gulp.watch(p.srcSCSS,           ['css-watch']);
    gulp.watch(p.srcJS + '*.js',    ['js-watch']);
});

// build project for release
gulp.task('build', ['clean', 'updateSubmodules'], function() {
    gulp.start('css-build');
    gulp.start('js-build');
});
