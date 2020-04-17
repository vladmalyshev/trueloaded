/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

const MODE_ST = process.env.npm_lifecycle_event == 'build' ? 'build' : 'dev';
const webpack = require('webpack');

const MiniCssExtractPlugin = require("mini-css-extract-plugin");
const OptimizeCSSAssetsPlugin = require("optimize-css-assets-webpack-plugin");
const VueLoaderPlugin = require('vue-loader/lib/plugin');

webpackConfig = {
    context: __dirname + '/entry',
    entry: {
        //mainCss:  "./backend/main-css",
        //information:  "./backend/information",
        //imageMapAdmin:  "./backend/image-maps/edit",
        imageMap:  "./frontend/boxes/image-map",
        //emailEditor:  "./backend/email-editor/edit",
        //svgEditor:  "./backend/svg-editor/editor",
        //svgEditorImage:  "./backend/svg-editor/ext-image",
        //svgEditorSize:  "./backend/svg-editor/ext-size",
        //editData:  "./frontend/edit-data/index",
    },

    output: {
        path:     __dirname + '/../../../../',
        filename: (obj) => {
            switch (obj.chunk.name) {
                //case 'information': return 'admin/themes/basic/js/information.js';
                //case 'imageMapAdmin': return 'admin/themes/basic/js/image-map/edit.js';
                case 'imageMap': return 'themes/basic/js/image-map.js';
                //case 'emailEditor': return 'admin/themes/basic/js/email-editor/edit.js';
                //case 'svgEditor': return 'admin/themes/basic/svg-editor/editor.js';
                //case 'svgEditorImage': return 'admin/themes/basic/svg-editor/extensions/ext-image.js';
                //case 'svgEditorSize': return 'admin/themes/basic/svg-editor/extensions/ext-size.js';
                //case 'editData': return 'themes/basic/js/edit-data.js';
            }
            return obj.chunk.id + ".js"
        },
        chunkFilename: 'admin/themes/basic/js/chunks/[name].js',
        library: '[name]',
    },

    watch: MODE_ST == 'dev',

    devtool: MODE_ST == 'dev' ? "source-map" : false,

    plugins: [
        new webpack.DefinePlugin({
            MODE_ST: JSON.stringify(MODE_ST)
        }),
        new MiniCssExtractPlugin({
            filename: (obj) => {
                switch (obj.chunk.name) {
                    //case 'mainCss': return 'admin/themes/basic/css/css.css';
                    //case 'information': return 'admin/themes/basic/css/information.css';
                    //case 'imageMapAdmin': return 'admin/themes/basic/css/image-map/edit.css';
                    //case 'imageMap': return 'themes/basic/css/image-map.css';
                    //case 'emailEditor': return 'admin/themes/basic/css/email-editor/edit.css';
                    //case 'svgEditor': return 'admin/themes/basic/svg-editor/editor.css';
                    //case 'bannerEditor': return 'admin/themes/basic/css/banner-editor.css';
                    //case 'editData': return 'themes/basic/css/edit-data.css';
                }
                return obj.chunk.id + ".css"
            },
            chunkFilename: "[id].css"
        }),
        new VueLoaderPlugin(),
    ],

    module: {
        rules: [
            {
                test: /\.vue$/,
                loader: 'vue-loader',
            },
            {
                test: /\.m?js$/,
                exclude: /node_modules(?!\/svgedit)/,
                loader: "babel-loader",
                options: {
                    presets: [
                        [
                            "@babel/preset-env",
                            {
                                "useBuiltIns": "entry"
                            }
                        ]
                    ],
                    plugins: [
                        "@babel/plugin-syntax-dynamic-import",
                        '@babel/plugin-transform-runtime'
                    ]
                }
            },
            {
                test: /\.(css|scss)$/,
                use: [
                    {
                        loader: MiniCssExtractPlugin.loader,
                        options: {
                            // you can specify a publicPath here
                            // by default it use publicPath in webpackOptions.output
                            publicPath:  __dirname + '/../../../../'
                        }
                    },
                    {
                        loader: "css-loader", // translates CSS into CommonJS
                        options: {
                            sourceMap: true
                        }
                    },
                    {
                        loader: "sass-loader", // compiles Sass to CSS
                        options: {
                            data: '@import "modules/admin-theme-variables";',
                            sourceMap: true,
                        }
                    }
                ]
            }
        ]
    },

    resolve: {
        alias: {
            src: __dirname + '/modules/',
            'vue$': 'vue/dist/vue.esm.js' // 'vue/dist/vue.common.js' for webpack
        }
    }
};

if (MODE_ST != 'dev') {
    webpackConfig.plugins.push(new OptimizeCSSAssetsPlugin({}))
}

module.exports = webpackConfig