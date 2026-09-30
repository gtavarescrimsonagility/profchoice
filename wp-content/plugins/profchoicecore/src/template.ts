/**
 * Inner blocks template: [ blockName, attributes?, innerBlocks? ].
 */
export type TemplateArray = Array<
	[ string, Record< string, unknown >?, TemplateArray? ]
>;
