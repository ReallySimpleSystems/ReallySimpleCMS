<?php
/**
 * The <button> DOMtag.
 * @since 1.0.0
 *
 * @author Jace Fincham
 * @package DomTags
 */
namespace DomTags;

class ButtonTag extends \DomTags implements DomTagInterface {
	/**
	 * Types of inputs.
	 * @since 1.2.0
	 *
	 * @access private
	 * @var array
	 */
	private const TYPES = array(
		'button', 'reset', 'submit'
	);
	
	/**
	 * The current type.
	 * @since 1.2.0
	 *
	 * @access private
	 * @var string
	 */
	private static $type;
	
	/**
	 * Construct the DOMtag.
	 * @since 1.0.0
	 *
	 * @access public
	 * @param array|null $args (optional) -- The list of arguments.
	 * @return string
	 */
	public static function tag(?array $args = null): string {
		if(isset($args['type'])) {
			if(!in_array($args['type'], self::TYPES, true))
				return 'Invalid button type!';
		} else {
			$args = array_merge(
				array(
					'type' => 'button'
				),
				$args
			);
		}
		
		self::$type = $args['type'];
		
		return parent::constructTag('button', self::props(), $args);
	}
	
	/**
	 * The tag's props.
	 * @since 1.0.0
	 *
	 * @access public
	 * @return array
	 */
	public static function props(): array {
		return array_merge(
			array('type'),
			parent::ALWAYS_WL,
			array('name', 'value', 'disabled', 'autofocus')
		);
	}
}