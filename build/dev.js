const entry = {
	"field-calculate": {
		import: './plg_radicalmart_shipping_apiship/es6/field/calculate.es6',
		filename: 'field-calculate.js',
	},
};

const webpackConfig = require('./webpack.config.js');
const publicPath = '../media';
const development = webpackConfig(entry, publicPath, 'development');

module.exports = [development]