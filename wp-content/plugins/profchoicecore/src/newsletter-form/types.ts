export type NewsletterFormAttributes = {
	anchor?: string;
	[ key: string ]: unknown;
};

export type NewsletterStatus =
	'idle' | 'submitting' | 'success' | 'pending' | 'error';

export type NewsletterContext = {
	status: NewsletterStatus;
	/** Whether the form has a Pending message (else pending shows Success). */
	hasPending: boolean;
};

export type NewsletterState = {
	restUrl: string;
	readonly isSubmitting: boolean;
	readonly isSuccess: boolean;
	readonly isPending: boolean;
	readonly isDone: boolean;
	readonly isError: boolean;
};
