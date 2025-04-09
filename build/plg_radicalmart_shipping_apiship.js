const fs = require('fs');
const imagesPath = './plg_radicalmart_shipping_apiship/images';
const images = fs.readdirSync(imagesPath)
	.map(file => `${imagesPath}/${file}`);

const entry = {
	"checkout": {
		import: './plg_radicalmart_shipping_apiship/es6/site/checkout.es6',
		filename: 'checkout.js',
	},
	"fields/points": {
		import: './plg_radicalmart_shipping_apiship/es6/fields/points.es6',
		filename: 'fields/points.js',
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