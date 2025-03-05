const Encore = require('@symfony/webpack-encore');

// Manually configure the runtime environment if not already configured yet by the "encore" command.
// It's useful when you use tools that rely on webpack.config.js file.
if (!Encore.isRuntimeEnvironmentConfigured()) {
    Encore.configureRuntimeEnvironment(process.env.NODE_ENV || 'dev');
}

Encore
    // directory where compiled assets will be stored
    .setOutputPath('build/')

    // public path used by the web server to access the output path
    .setPublicPath('/build')

    .addEntry('form', './assets/js/form.js')

    .configureTerserPlugin(
      (options) => {
        options.terserOptions = {
          output: {
            // Comments in output breaks dawa-autocomplete2.
            comments: false,
          },
        };
      }
    )

    .disableSingleRuntimeChunk()
    .autoProvidejQuery()

    /*
     * FEATURE CONFIG
     *
     * Enable & configure other features below. For a full
     * list of features, see:
     * https://symfony.com/doc/current/frontend.html#adding-more-features
     */
    .cleanupOutputBeforeBuild()
    .enableSourceMaps(!Encore.isProduction())

.configureBabel((config) => {
        config.plugins.push('@babel/plugin-transform-class-properties');
    })

    // enables @babel/preset-env polyfills
    .configureBabelPresetEnv((config) => {
        config.useBuiltIns = 'usage';
        config.corejs = 3;
    })
;

module.exports = Encore.getWebpackConfig();
