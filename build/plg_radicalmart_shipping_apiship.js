const entry = {
	"fields/places": {
		import: ['./plg_radicalmart_shipping_apiship/es6/fields/places.es6', './plg_radicalmart_shipping_apiship/scss/fields/places.scss'],
		filename: 'fields/places.js',
	},
};

const webpackConfig = require('./webpack.config.js');
const publicPath = '../media';
const production = webpackConfig(entry, publicPath);
const development = webpackConfig(entry, publicPath, 'development');

module.exports = [production, development]