const entry = {
	"field-sender": {
		import: ['./plg_radicalmart_shipping_apiship/es6/field/sender.es6'],
		filename: 'field-sender.js',
	},
	"field-recipient": {
		import: ['./plg_radicalmart_shipping_apiship/es6/field/recipient.es6'],
		filename: 'field-recipient.js',
	},
};

const webpackConfig = require('./webpack.config.js');
const publicPath = '../media';
const development = webpackConfig(entry, publicPath, 'development');

module.exports = [development]