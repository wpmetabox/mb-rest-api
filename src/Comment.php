<?php
namespace MetaBox\RestApi;

use WP_Comment;

class Comment extends Base {
	public function update( $data, WP_Comment $comment ) {
		$this->update_values( $data, $comment->comment_ID, 'comment' );
	}
}
