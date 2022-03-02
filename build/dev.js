const entry = {
	"field-to": {
		import: ['./plg_radicalmart_shipping_apiship/es6/field/to.es6'],
		filename: 'field-to.js',
	},
};

const webpackConfig = require('./webpack.config.js');
const publicPath = '../media';
const development = webpackConfig(entry, publicPath, 'development');

module.exports = [development]