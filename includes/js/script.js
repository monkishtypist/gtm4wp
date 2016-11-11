/**
 * This is my JavaScript
 *
 * There are many like it, but this one is mine.
 *
 */
jQuery( function( $ ) {

	// Let's make sure JS is loading...
	console.log('gtm4wp.js.init');

	// URL params
	function getUrlParameter(name) {
		name = name.replace(/[\[]/, '\\[').replace(/[\]]/, '\\]');
		var regex = new RegExp('[\\?&]' + name + '=([^&#]*)');
		var results = regex.exec(location.search);
		return results === null ? '' : decodeURIComponent(results[1].replace(/\+/g, ' '));
	};


	// ===================================
	// 
	// Add to Cart triggers
	//
	// ===================================

	// Single item add to cart on page reload with query parameter add_to_cart=X
	var q = getUrlParameter('add-to-cart');
	if ( typeof q !== 'undefined' && q > 0 ) {
		console.log( 'add to cart triggered (query-param: ' + q + ')' );
		var product = {
			id: getUrlParameter('add-to-cart'), // urlParams.get('add-to-cart'),
			qty: 1
		};
		$.ajax({
			url: ajax_object.ajaxurl,
			data: {
				'action'     : 'gtm4wp_get_product',
				'product_id' : product.id,
				'product_qty': product.qty
			},
			success: function( data ) {
				var product = $.parseJSON( data );
				dataLayer.push({
					'event': 'addToCart', 
					'ecommerce': { 
						'add': { 
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

	// Single item AJAX add to cart from archive page view
	$('.add_to_cart_button').on( 'click', function () {
		if ( ajax_object.woocommerce_enable_ajax_add_to_cart == 'yes' ) {
			console.log( 'AJAX add to cart triggered (click: ' + $(this).attr('data-product_id') + ')' );
			var product = {
				id: $(this).attr('data-product_id'),
				qty: $(this).attr('data-quantity')
			};
			$.ajax({
				url: ajax_object.ajaxurl,
				data: {
					'action'     : 'gtm4wp_get_product',
					'product_id' : product.id,
					'product_qty': product.qty
				},
				success: function( data ) {
					var product = $.parseJSON( data );
					dataLayer.push({
						'event': 'addToCart', 
						'ecommerce': { 
							'add': { 
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

	// Add to cart from product details page with page redirect detect and no query param
	if ( $('body').hasClass('single-product') && $('div.woocommerce-message').length ) {
		var msgstr = $('div.woocommerce-message').text();
		if ( /added/i.test( msgstr ) ) {
			console.log( 'add to cart triggered (add to cart message detected)' );
			var product = {
				id           : $('input[name="add-to-cart"]').attr('value'),
				product_id   : $('input[name="product_id"]').attr('value'),
				variation_id : $('input[name="variation_id"]').attr('value'),
				variations   : $.parseJSON( $('form.cart').attr('data-product_variations') ),
				qty          : $('input[name="quantity"]').attr('value')
			}
			if (product.variations) {
				console.log('variations detected');
				product.variation_attribute = $('.variations select').attr('data-attribute_name');
				product.variant = $('.variations select').val();
			}
			$.ajax({
				url: ajax_object.ajaxurl,
				data: {
					'action'          : 'gtm4wp_get_product',
					'product_id'      : product.id,
					'product_qty'     : product.qty,
					'product_variant' : product.variant
				},
				success: function( data ) {
					var product = $.parseJSON( data );
					dataLayer.push({
						'event': 'addToCart', 
						'ecommerce': { 
							'add': { 
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
	}


	// ===================================
	// 
	// Remove from Cart triggers
	//
	// ===================================

	// Remove from cart click
	$('body.woocommerce-cart a.remove').on( 'click', function () {
		console.log( 'Remove from cart triggered (click: ' + $(this).attr('data-product_id') + ')' );
		var product = {
			id: $(this).attr('data-product_id'),
			quantity: $(this).parents('.cart_item').find('input.qty').val(),
			variant: $(this).parents('.cart_item').find('dl.variation dd[class^="variation"]').text()
		};
		$.ajax({
			url: ajax_object.ajaxurl,
			data: {
				'action'          : 'gtm4wp_get_product',
				'product_id'      : product.id,
				'product_qty'     : product.quantity,
				'product_variant' : product.variant
			},
			success: function( data ) {
				var product = $.parseJSON( data );
				dataLayer.push({
					'event': 'removeFromCart', 
					'ecommerce': { 
						'remove': { 
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
	});
	
	$('.product_cat_each').on( 'click', function (e) {
		e.preventDefault();
		var href= $(this).attr('href');
		var product_id = $(this).attr('data-product-id');
		var product_name = $(this).attr('data-product-name');
		//var product_category = $(this).attr('data-product-cat');
		
		dataLayer.push({
			'event': 'enhanceEcom Product Click',
			'ecommerce': {
			  	'click': {
					//'actionField': {'list': 'Comfort'},
					'products': [{
						'name': product_name,	   // Name or ID is required.
						'id': product_id,
						'brand': 'Cabeau',
						//'category': product_category
					}]
				}
			}
		});
		console.log( dataLayer );
		location.href = href;
		return false;
	});
});
//EOF