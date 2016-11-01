/**
 * This is the JavaScripts
 *
 */
jQuery(function($) {

	// Let's make sure JS is loading...
	console.log('gtm4wp.js.init');

	// URL params
	var urlParams = new URLSearchParams(window.location.search);

	// Single item add to cart from Archive view
	if ( urlParams.has('add-to-cart') ) {
		console.log( 'add to cart triggered (query-param: ' + urlParams.get('add-to-cart') + ')' );
		var product = { id: urlParams.get('add-to-cart'), qty: 1 };
		$.ajax({
			url: ajax_object.ajaxurl,
			data: {
				'action'     : 'gtm4wp_add_to_cart',
				'product_id' : product.id,
				'product_qty': product.qty
			},
			success: function( data ) {
				var product = $.parseJSON( data );
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
								'category': product.category,
								'quantity': product.quantity
							}]
						}
					}
				});
				console.log( product );
			},
			error: function( error ) {
				console.log( error );
			}
		});
	};

	// Single item add to cart from Archive view
	$('.add_to_cart_button').on( 'click', function () {
		if ( ajax_object.woocommerce_enable_ajax_add_to_cart == 'yes' ) {
			console.log( 'AJAX add to cart triggered (click: ' + $(this).attr('data-product_id') + ')' );
			var product = { id: $(this).attr('data-product_id'), qty: $(this).attr('data-quantity') };
			$.ajax({
				url: ajax_object.ajaxurl,
				data: {
					'action'     : 'gtm4wp_add_to_cart',
					'product_id' : product.id,
					'product_qty': product.qty
				},
				success: function( data ) {
					var product = $.parseJSON( data );
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
									'category': product.category,
									'quantity': product.quantity
								}]
							}
						}
					});
					console.log( product );
				},
				error: function( error ) {
					console.log( error );
				}
			});
		}
		else {
			console.log('WooCommerce AJAX add to cart not enabled. Redirecting...');
		}
	});


});

