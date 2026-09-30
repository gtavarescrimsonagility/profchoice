export type NewsletterFormAttributes = {
	anchor?: string;
	[ key: string ]: unknown;
};

export type NewsletterStatus = 'idle' | 'submitting' | 'success' | 'error';

export type NewsletterContext = {
	status: NewsletterStatus;
};

export type NewsletterState = {
	restUrl: string;
	readonly isSubmitting: boolean;
	readonly isSuccess: boolean;
	readonly isError: boolean;
};
