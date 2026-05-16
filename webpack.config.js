const path = require('path');
const defaultConfig = require('@wordpress/scripts/config/webpack.config');

module.exports = {
    ...defaultConfig,
    entry: {
        main: path.resolve(__dirname, 'admin/src/main.tsx'),
    },
    output: {
        ...defaultConfig.output,
        path: path.resolve(__dirname, 'assets/admin'),
        filename: '[name].js',
    },
    resolve: {
        ...defaultConfig.resolve,
        extensions: ['.ts', '.tsx', '.js', '.jsx', '.json'],
    },
};
