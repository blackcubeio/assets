import webpack from 'webpack';
import path from 'path';
import { readFileSync } from 'fs';
import AssetsWebpackPlugin from 'assets-webpack-plugin';
import CompressionWebpackPlugin from 'compression-webpack-plugin';
import MiniCssExtractPlugin from 'mini-css-extract-plugin';

// Load configuration
const config = JSON.parse(readFileSync('./assets-blackcube.json', 'utf-8'));

// Paths
const sourceDir = path.resolve(process.cwd(), config.sourceDir);
const outputDir = path.resolve(process.cwd(), `${config.outputBaseDir}/dist-webpack`);

// Build entries
const entries: Record<string, string> = {};
for (const [name, entry] of Object.entries(config.entry)) {
    entries[name] = path.resolve(sourceDir, entry as string);
}

const prodFlag = process.env.NODE_ENV === 'production'
    || process.argv.includes('-p')
    || process.argv.includes('production');

const webpackConfig: webpack.Configuration = {
    entry: entries,
    mode: prodFlag ? 'production' : 'development',
    context: sourceDir,
    output: {
        clean: false, // Must be false - AssetsWebpackPlugin writes catalog after build
        path: outputDir,
        filename: prodFlag
            ? `${config.subDirectories.js}/[name].[chunkhash:8].js`
            : `${config.subDirectories.js}/[name].js`,
        chunkFilename: prodFlag
            ? `${config.subDirectories.js}/[name].[chunkhash:8].js`
            : `${config.subDirectories.js}/[name].js`,
    },
    plugins: [
        new webpack.DefinePlugin({
            PRODUCTION: JSON.stringify(prodFlag),
        }),
        new MiniCssExtractPlugin({
            filename: prodFlag
                ? `${config.subDirectories.css}/[name].[chunkhash:8].css`
                : `${config.subDirectories.css}/[name].css`,
            chunkFilename: prodFlag
                ? `${config.subDirectories.css}/[name].[chunkhash:8].css`
                : `${config.subDirectories.css}/[name].css`,
        }),
        new CompressionWebpackPlugin({
            filename: '[path][base].gz[query]',
            algorithm: 'gzip',
            test: /\.(js|css|map)$/,
            threshold: 10,
            minRatio: 1,
        }),
        new AssetsWebpackPlugin({
            prettyPrint: true,
            filename: config.catalog,
            path: outputDir,
            fullPath: false,
            removeFullPathAutoPrefix: true,
            processOutput: function (assets: Record<string, Record<string, string>>) {
                const finalAssets: Record<string, Record<string, string>> = {};
                for (const a in assets) {
                    if (a.length > 0) {
                        for (const b in assets[a]) {
                            assets[a][b] = assets[a][b].replace('auto/', '');
                        }
                        finalAssets[a] = assets[a];
                    }
                }
                return JSON.stringify(finalAssets, null, 2);
            },
        }),
    ],
    module: {
        rules: [
            {
                enforce: 'pre',
                test: /\.js$/,
                loader: 'source-map-loader',
            },
            {
                enforce: 'pre',
                test: /\.tsx?$/,
                use: 'source-map-loader',
            },
            {
                test: /\.tsx?$/,
                loader: 'ts-loader',
                exclude: /node_modules/,
                options: {
                    configFile: path.resolve(sourceDir, 'tsconfig.json'),
                },
            },
            {
                test: /\.(ttf|eot|svg|woff|woff2)([?#][a-z0-9]+)?$/,
                type: 'asset/resource',
                generator: {
                    filename: 'assets/[name][ext]',
                },
            },
            {
                test: /\.(jpe?g|png|gif)$/,
                type: 'asset/resource',
                generator: {
                    filename: 'assets/[name][ext]',
                },
            },
            {
                test: /\.s[ac]ss$/,
                use: [
                    {
                        loader: MiniCssExtractPlugin.loader,
                        options: {
                            publicPath: (resourcePath: string, context: string) => {
                                return path.relative(path.dirname(resourcePath), context) + '/';
                            },
                        },
                    },
                    {
                        loader: 'css-loader',
                        options: {
                            importLoaders: 2,
                            esModule: false,
                            modules: false,
                        },
                    },
                    'postcss-loader',
                    'sass-loader',
                ],
            },
            {
                test: /\.css$/,
                use: [
                    {
                        loader: MiniCssExtractPlugin.loader,
                        options: {
                            publicPath: (resourcePath: string, context: string) => {
                                return path.relative(path.dirname(resourcePath), context) + '/';
                            },
                        },
                    },
                    {
                        loader: 'css-loader',
                        options: {
                            importLoaders: 2,
                            esModule: false,
                        },
                    },
                    'postcss-loader',
                ],
            },
        ],
    },
    optimization: {
        removeEmptyChunks: true,
        runtimeChunk: {
            name: 'manifest',
        },
        splitChunks: {
            hidePathInfo: true,
            chunks: 'initial',
            cacheGroups: {
                default: false,
                vendors: {
                    test: /[\\/]node_modules[\\/]/,
                    name: 'vendors',
                    priority: 19,
                    enforce: true,
                    minSize: 100,
                },
                vendorsAsync: {
                    test: /[\\/]node_modules[\\/]/,
                    name: 'vendors.async',
                    chunks: 'async',
                    priority: 9,
                    reuseExistingChunk: true,
                    minSize: 1000,
                },
                commonsAsync: {
                    name: 'commons.async',
                    minChunks: 2,
                    chunks: 'async',
                    priority: 0,
                    reuseExistingChunk: true,
                    minSize: 1000,
                },
            },
        },
    },
    resolve: {
        extensions: ['.ts', '.tsx', '.js'],
        modules: [sourceDir, 'node_modules'],
    },
    target: 'web',
    devtool: prodFlag ? false : 'source-map',
    performance: {
        hints: prodFlag ? false : 'warning',
        maxEntrypointSize: 512000,
        maxAssetSize: 512000,
    },
};

export default webpackConfig;
