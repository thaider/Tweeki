'use strict'; // eslint-disable-line

const path = require('path');
const webpack = require('webpack');
const ExtractTextPlugin = require('extract-text-webpack-plugin');



const config = {
  open: true,
  copy: 'images/**/*',
  proxyUrl: 'http://localhost:3000',
  cacheBusting: '[name]_[hash]',
  paths: {
    assets: path.join(process.cwd(), 'src/defaults')
  },
  enabled: {
    watcher: false
  }
}


module.exports = {
  entry: './src/index.js',
  output: {
    filename: 'main.js',
    path: path.resolve(__dirname, 'dist')
  },
  module: {
    rules: [
      {
        test: /\.(scss)$/,
        include: config.paths.assets,
        // use: [
        //   {
        //     loader: 'style-loader', // inject CSS to page
        //   }, {
        //     loader: 'css-loader', // translates CSS into CommonJS modules
        //   }, {
        //     loader: 'postcss-loader', // Run post css actions
        //     options: {
        //       plugins: function () { // post css plugins, can be exported to postcss.config.js
        //         return [
        //           require('precss'),
        //           require('autoprefixer')
        //         ];
        //       }
        //     }
        //   }, {
        //     loader: 'sass-loader' // compiles Sass to CSS
        //   }
        // ]
        use: ExtractTextPlugin.extract({
          fallback: 'style',
          use: [
            { loader: 'cache' },
            { loader: 'css', options: { sourceMap: false } },
            {
              loader: 'postcss', options: {
                config: { path: __dirname, ctx: config },
                sourceMap: false,
              },
            },
            { loader: 'resolve-url', options: { sourceMap: false } },
            { loader: 'sass', options: { sourceMap: false } },
          ],
        }),
      },
      {
        test: /\.(png|svg|jpg|gif)$/,
        use: [
          'file-loader'
        ]
      }
    ]
  },
  externals: {
    jquery: 'jQuery',
  },
  plugins: [
    new ExtractTextPlugin({
      filename: `styles/[name].css`,
      disable: (config.enabled.watcher),
    }),
    new webpack.ProvidePlugin({
      $: 'jquery',
      jQuery: 'jquery',
      'window.jQuery': 'jquery',
      Popper: 'popper.js/dist/umd/popper.js',
    }),
  ]
};
