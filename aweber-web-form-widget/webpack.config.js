module.exports = {
	entry: './src/js/aweber_gutenberg_webform_block.js',
	output: {
		path: __dirname,
		filename: 'src/js/aweber_gutenberg_webform_block-react.js',
	},
	module: {
		loaders: [
			{
				test: /.js$/,
				loader: 'babel-loader',
				exclude: /node_modules/,
			},
			{
				test: /\.css$/,
				use: [
					'style-loader',
				    'css-loader'
			    ]
			}
		],
	},
};