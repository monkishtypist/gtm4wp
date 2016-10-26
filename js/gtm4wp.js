(function( $ ) {
	$('.single_add_to_cart_button').on('click', function() {
		dataLayer.push({
			'event': 'enhanceEcom Product Click', 
			'ecommerce': { 
				'click': { 
					'actionField': {
						'list': product.list
					}, 
					'products': [{ 
						'id': product.id, 
						'name': product.name, 
						'price': product.price, 
						'brand': product.brand, 
						'variant': product.variant, 
						'category': product.category
					}]
				}
			}
		});
	});
	console.log(dataLayer);
})(jQuery);