<?php
/**
 * DOMtags basic setup.
 * @since 1.0.0
 *
 * @author Jace Fincham
 * @package DomTags
 */

// Current version
define('DOMTAGS_VERSION', '1.2.1');

/**
 * Construct a DOM tag.
 * @since 1.0.1
 *
 * @param string $tag_name -- The tag name.
 * @param array|null $args (optional) -- The args.
 * @return string
 */
function domTag(string $tag_name, ?array $args = null): string {
	$has_type = array(
		'audio', 'video',
		'br', 'hr',
		'em', 'i',
		'h1', 'h2',
		'h3', 'h4',
		'h5', 'h6',
		'ol', 'ul',
		'strong', 'b',
		'td', 'th'
	);
	
	if(in_array($tag_name, $has_type, true)) $args['tag_type'] = $tag_name;
	
	return match($tag_name) {
		'a' => \DomTags\AnchorTag::tag($args),
		'abbr' => \DomTags\AbbrTag::tag($args),
		'audio', 'video' => \DomTags\MediaTag::tag($args),
		'br', 'hr' => \DomTags\SeparatorTag::tag($args),
		'button' => \DomTags\ButtonTag::tag($args),
		'code' => \DomTags\CodeTag::tag($args),
		'div' => \DomTags\DivTag::tag($args),
		'em', 'i' => \DomTags\EmTag::tag($args),
		'fieldset' => \DomTags\FieldsetTag::tag($args),
		'form' => \DomTags\FormTag::tag($args),
		'h1', 'h2', 'h3', 'h4', 'h5', 'h6' => \DomTags\HeadingTag::tag($args),
		'iframe' => \DomTags\IframeTag::tag($args),
		'img' => \DomTags\ImageTag::tag($args),
		'input' => \DomTags\InputTag::tag($args),
		'label' => \DomTags\LabelTag::tag($args),
		'li' => \DomTags\ListItemTag::tag($args),
		'link' => \DomTags\LinkTag::tag($args),
		'meta' => \DomTags\MetaTag::tag($args),
		'ol', 'ul' => \DomTags\ListTag::tag($args),
		'option' => \DomTags\OptionTag::tag($args),
		'p' => \DomTags\ParagraphTag::tag($args),
		'script' => \DomTags\ScriptTag::tag($args),
		'section' => \DomTags\SectionTag::tag($args),
		'select' => \DomTags\SelectTag::tag($args),
		'small' => \DomTags\SmallTag::tag($args),
		'source' => \DomTags\SourceTag::tag($args),
		'span' => \DomTags\SpanTag::tag($args),
		'strong', 'b' => \DomTags\StrongTag::tag($args),
		'table' => \DomTags\TableTag::tag($args),
		'td', 'th' => \DomTags\TableCellTag::tag($args),
		'textarea' => \DomTags\TextareaTag::tag($args),
		'tr' => \DomTags\TableRowTag::tag($args),
		default => 'Invalid tag!'
	};
}

/**
 * Display a DOM tag.
 * @since 1.0.1
 *
 * @param string $tag_name -- The tag name.
 * @param array|null $args (optional) -- The args.
 */
function domTagPr(string $tag_name, ?array $args = null): void {
	echo domTag($tag_name, $args);
}

/**
 * Content parser (experimental).
 * @since 1.2.0
 *
 * @param array $data
 * @return string
 */
/* function runParse(array $data): string {
	$branches = explode($data['tree'], ';');
	$twigs = array();
	
	foreach($branches as $branch) {
		$twigs[] = explode($branch, '.');
		#$parsed .= domTag(a);
	}
	
	return $parsed;
} */

# $data['tree'] = 'div.p[id=cc,class=abc]:p:a;div.p.span~hr'; // the form should submit the elements in this format