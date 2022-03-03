const entry = {
	"field-places": {
		import: ['./plg_radicalmart_shipping_apiship/es6/field/places.es6',  './plg_radicalmart_shipping_apiship/scss/field/places.scss'],
		filename: 'field-places.js',
	},
	"field-from": {
		import: ['./plg_radicalmart_shipping_apiship/es6/field/from.es6'],
		filename: 'field-from.js',
	},
	"field-to": {
		import: ['./plg_radicalmart_shipping_apiship/es6/field/to.es6'],
		filename: 'field-to.js',
	},
	"field-calculate": {
		import: ['./plg_radicalmart_shipping_apiship/es6/field/calculate.es6'],
		filename: 'field-calculate.js',
	},
};

const webpackConfig = require('./webpack.config.js');
const publicPath = '../media';
const production = webpackConfig(entry, publicPath);
const development = webpackConfig(entry, publicPath, 'development');

module.exports = [production, development]