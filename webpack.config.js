const path = require('path');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const CssMinimizerPlugin = require('css-minimizer-webpack-plugin');
const { CleanWebpackPlugin } = require('clean-webpack-plugin');

module.exports = (env, argv) => {
  const isProduction = argv.mode === 'production';
  
  return {
    entry: {
      'floor-template': './src/scss/floor-template.scss',
      // Add more entry points here if you have multiple SCSS files
    },
    output: {
      filename: 'js/[name].js', // We won't use this but webpack requires it
      path: path.resolve(__dirname, 'dist'),
    },
    plugins: [
      new CleanWebpackPlugin({
        cleanStaleWebpackAssets: false, // Only clean on build, not on watch
      }),
      new MiniCssExtractPlugin({
        filename: 'css/[name].css',
      }),
    ],
    module: {
      rules: [
        {
          test: /\.scss$/,
          use: [
            MiniCssExtractPlugin.loader,
            'css-loader',
            {
              loader: 'postcss-loader',
              options: {
                postcssOptions: {
                  plugins: [
                    ['autoprefixer'],
                  ],
                },
              },
            },
            'sass-loader',
          ],
        },
      ],
    },
    optimization: {
      minimizer: [
        `...`,
        new CssMinimizerPlugin(),
      ],
      minimize: isProduction,
    },
    devtool: isProduction ? false : 'source-map',
  };
};