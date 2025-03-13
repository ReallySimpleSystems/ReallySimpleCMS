<?php
/**
 * The <input> DOMtag.
 * @since 1.0.0
 *
 * @author Jace Fincham
 * @package DomTags
 */
namespace DomTags;

class InputTag extends \DomTags implements DomTagInterface {
	/**
	 * Types of inputs.
	 * @since 1.2.0
	 *
	 * @access private
	 * @var array
	 */
	private const TYPES = array(
		'button', 'checkbox', 'color', 'date', 'datetime-local', 'email', 'file',
		'hidden', 'image', 'month', 'number', 'password', 'radio', 'range',
		'reset', 'search', 'submit', 'tel', 'text', 'time', 'url', 'week'
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
				return 'Invalid input type!';
		} else {
			$args = array_merge(
				array(
					'type' => 'text'
				),
				$args
			);
		}
		
		self::$type = $args['type'];
		
		return parent::constructTag('input', self::props(), $args);
	}
	
	/**
	 * The tag's props.
	 * @since 1.0.0
	 *
	 * @access public
	 * @return array
	 */
	public static function props(): array {
		$props = array_merge(
			array('type'),
			parent::ALWAYS_WL,
			array('name', 'value', 'autocomplete', 'list')
		);
		
		switch(self::$type) {
			case 'checkbox':
			case 'radio':
				$props = array_merge(
					$props,
					array('checked', 'required')
				);
				break;
			case 'date':
				$props = array_merge(
					$props,
					array('min', 'max', 'step', 'pattern', 'required')
				);
				break;
			case 'datetime-local':
			case 'month':
			case 'time':
			case 'week':
				$props = array_merge(
					$props,
					array('min', 'max', 'step', 'required')
				);
				break;
			case 'email':
				$props = array_merge(
					$props,
					array( 'placeholder', 'minlength', 'maxlength', 'pattern', 'multiple', 'required')
				);
				break;
			case 'file':
				$props = array_merge(
					$props,
					array('accept', 'multiple', 'required')
				);
				break;
			case 'image':
				$props = array_merge(
					$props,
					array('src', 'alt', 'width', 'height')
				);
				break;
			case 'number':
				$props = array_merge(
					$props,
					array('placeholder', 'min', 'max', 'step', 'required')
				);
				break;
			case 'password':
			case 'search':
			case 'tel':
			case 'text':
			case 'url':
				$props = array_merge(
					$props,
					array('placeholder', 'minlength', 'maxlength', 'pattern', 'required')
				);
				break;
			case 'range':
				$props = array_merge(
					$props,
					array('min', 'max', 'step')
				);
				break;
		}
		
		return array_merge(
			$props,
			array('readonly', 'disabled', 'autofocus')
		);
	}
}