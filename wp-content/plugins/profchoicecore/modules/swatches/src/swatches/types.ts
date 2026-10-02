export type SwatchesAttributes = {
	attribute: string;
	productId?: number;
	selected?: string;
	name?: string;
	showLabel: boolean;
	showValue: boolean;
	sizeGuideUrl: string;
	sizeGuideText?: string;
	[ key: string ]: unknown;
};

/** Context of the block, plus `value` on each swatch. */
export type SwatchesContext = {
	/** Field name in the variations form (`attribute_pa_color`). */
	name: string;
	selected: string;
	first: string;
	labels: Record< string, string >;
	disabled: string[];
	value?: string;
};

/** A variation as WooCommerce prints it in `data-product_variations`. */
export type Variation = {
	attributes: Record< string, string >;
	is_in_stock?: boolean;
	variation_is_active?: boolean;
};
