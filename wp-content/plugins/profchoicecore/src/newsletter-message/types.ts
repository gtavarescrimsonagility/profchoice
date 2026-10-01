export type NewsletterMessageAttributes = {
	type: 'success' | 'pending' | 'error';
	[ key: string ]: unknown;
};
