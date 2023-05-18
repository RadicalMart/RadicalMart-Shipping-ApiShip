const entry = {
	"radicalmart": {
		import: './plg_radicalmart_shipping_apiship/es6/radicalmart.es6',
		filename: 'radicalmart.js',
	},
	"fields/places": {
		import: ['./plg_radicalmart_shipping_apiship/es6/fields/places.es6', './plg_radicalmart_shipping_apiship/scss/fields/places.scss'],
		filename: 'fields/places.js',
	},
	"fields/points": {
		import: './plg_radicalmart_shipping_apiship/es6/fields/points.es6',
		filename: 'fields/points.js',
	},
	"fields/sender": {
		import: './plg_radicalmart_shipping_apiship/es6/fields/sender.es6',
		filename: 'fields/sender.js',
	},
	"fields/recipient": {
		import: './plg_radicalmart_shipping_apiship/es6/fields/recipient.es6',
		filename: 'fields/recipient.js',
	},
};

const webpackConfig = require('./webpack.config.js');
const publicPath = '../media';
const production = webpackConfig(entry, publicPath);
const development = webpackConfig(entry, publicPath, 'development');

module.exports = [production, development]