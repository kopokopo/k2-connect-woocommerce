
const defaultConfig = require('@wordpress/scripts/config/webpack.config');
const DependencyExtractionWebpackPlugin = require('@wordpress/dependency-extraction-webpack-plugin');

const plugins = defaultConfig.plugins.filter(
  plugin => plugin.constructor.name !== 'DependencyExtractionWebpackPlugin'
);

plugins.push(
  new DependencyExtractionWebpackPlugin({
    requestToExternal(request) {
      if (request === '@woocommerce/blocks-registry') return ['wc', 'blocksRegistry'];
      if (request === '@woocommerce/blocks-checkout') return ['wc', 'blocksCheckout'];
      if (request === '@woocommerce/settings') return ['wc', 'settings'];
      return undefined;
    },
    requestToHandle(request) {
      if (request === '@woocommerce/blocks-registry') return 'wc-blocks-registry';
      if (request === '@woocommerce/blocks-checkout') return 'wc-blocks-checkout';
      if (request === '@woocommerce/settings') return 'wc-settings';
      return undefined;
    },
  })
);

module.exports = {
  ...defaultConfig,
  plugins,
};

