<?php
add_filter( 'rwmb_meta_boxes', function( $meta_boxes ) {
	$meta_boxes[] = [
		'title' => 'In REST',
		'fields' => [
			'Name',
			[
				'id'             => 'email',
				'name'           => 'Email',
				'hide_from_rest' => true,
			],
		],
	];
	return $meta_boxes;
} );