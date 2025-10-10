const fs = require('fs');
const imagesPath = './plg_radicalmart_shipping_apiship/images';
const images = fs.readdirSync(imagesPath)
	.map(file => `${imagesPath}/${file}`);

const entry = {
	"administrator/order": {
		import: './plg_radicalmart_shipping_apiship/es6/administrator/order.es6',
		filename: 'administrator/order.js',
	},
	"administrator/order-actions": {
		import: './plg_radicalmart_shipping_apiship/es6/administrator/order-actions.es6',
		filename: 'administrator/order-actions.js',
	},
	"site/checkout": {
		import: './plg_radicalmart_shipping_apiship/es6/site/checkout.es6',
		filename: 'checkout.js',
	},
	"fields/address": {
		import: './plg_radicalmart_shipping_apiship/es6/fields/address.es6',
		filename: 'fields/address.js',
	},
	"fields/addresses": {
		import: './plg_radicalmart_shipping_apiship/es6/fields/addresses.es6',
		filename: 'fields/addresses.js',
	},
	"fields/points": {
		import: './plg_radicalmart_shipping_apiship/es6/fields/points.es6',
		filename: 'fields/points.js',
	},
	"fields/points-files": {
		import: './plg_radicalmart_shipping_apiship/es6/fields/points-files.es6',
		filename: 'fields/points-files.js',
	},
	"fields/tariffs": {
		import: './plg_radicalmart_shipping_apiship/es6/fields/tariffs.es6',
		filename: 'fields/tariffs.js',
	},
	"fields/webhook": {
		import: './plg_radicalmart_shipping_apiship/es6/fields/webhook.es6',
		filename: 'fields/webhook.js',
	},
	"images": {
		import: images,
		filename: 'images.clean',
	},
};

const webpackConfig = require('./webpack.config.js');
const publicPath = '../media';
const production = webpackConfig(entry, publicPath);
const development = webpackConfig(entry, publicPath, 'development');

module.exports = [production, development]