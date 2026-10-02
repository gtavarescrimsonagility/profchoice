export type WishlistList = {
	id: number;
	name: string;
	isDefault: boolean;
	count: number;
};

/** The visitor's data, from the server render or a REST response. */
export type WishlistData = {
	saved: number[];
	count: number;
	lists: WishlistList[];
	/** Product ID => the list it is in. */
	where: Record< string, number >;
};

export type WishlistState = WishlistData & {
	restUrl: string;
	nonce: string;
	canUse: boolean;
	loginUrl: string;
	pageUrl: string;
	multiple: boolean;
	showNotice: boolean;
	dialog: { productId: number; productName: string; listId: number };
	notices: { added: string; removed: string; error: string };
	i18n: { add: string; remove: string; link: string; confirm: string };
	readonly isSaved: boolean;
	readonly buttonLabel: string;
	readonly linkLabel: string;
	readonly countDisplay: string;
	readonly outlineDisplay: string;
	readonly filledDisplay: string;
	readonly dialogListName: string;
	readonly isDialogList: boolean;
};

/** Context of a heart button or a wishlist item. */
export type ItemContext = {
	productId: number;
	productName?: string;
	listId?: number;
};

/** Context of a list (wishlist block head, dialog options). */
export type ListContext = {
	listId?: number;
	renaming?: boolean;
	list?: WishlistList;
};
