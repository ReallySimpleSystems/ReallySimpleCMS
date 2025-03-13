<?php
/**
 * The <h1-h6> DOMtag.
 * @since 1.0.1
 *
 * @author Jace Fincham
 * @package DomTags
 */
namespace DomTags;

class HeadingTag extends \DomTags implements DomTagInterface {
	/**
	 * The tag types.
	 * @since 1.0.1
	 *
	 * @access private
	 * @var array
	 */
	private const TAG_TYPES = array('h1', 'h2', 'h3', 'h4', 'h5', 'h6');
	
	/**
	 * The current tag type.
	 * @since 1.2.0
	 *
	 * @access private
	 * @var string
	 */
	private static $tag_type;
	
	/**
	 * Construct the DOMtag.
	 * @since 1.0.1
	 *
	 * @access public
	 * @param array|null $args (optional) -- The list of arguments.
	 * @return string
	 */
	public static function tag(?array $args = null): string {
		self::$tag_type = self::TAG_TYPES[1];
		
		if(isset($args['tag_type']) && in_array($args['tag_type'], self::TAG_TYPES, true))
			self::$tag_type = $args['tag_type'];
		
		return parent::constructTag(self::$tag_type, self::props(), $args);
	}
	
	/**
	 * The tag's props.
	 * @since 1.0.1
	 *
	 * @access public
	 * @return array
	 */
	public static function props(): array {
		return parent::ALWAYS_WL;
	}
}