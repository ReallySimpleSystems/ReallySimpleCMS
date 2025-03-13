<?php
/**
 * Interface for building DOMtags.
 * @since 1.0.0
 *
 * @author Jace Fincham
 * @package DomTags
 */
namespace DomTags;

interface DomTagInterface {
	/**
	 * The tag types (optional).
	 * @since 1.1.2
	 *
	 * @access private
	 * @var array
	 */
	# private const TAG_TYPES = array('h1', 'h2', 'h3', 'h4', 'h5', 'h6');
	
	/**
	 * Construct the DOMtag.
	 * @since 1.0.0
	 *
	 * @access public
	 * @param array|null $args (optional) -- The list of arguments.
	 * @return string
	 */
	public static function tag(?array $args = null): string;
	
	/**
	 * The tag's props.
	 * @since 1.0.0
	 *
	 * @access public
	 * @return array
	 */
	public static function props(): array;
}