/**
 * Newsletter form store: submits to the REST endpoint without a reload and
 * toggles the success/error messages.
 */
import { store, getContext } from '@wordpress/interactivity';
import type { NewsletterContext } from './types';

type SubscribeResponse = { ok?: boolean; pending?: boolean; message?: string };

/**
 * restUrl is provided by render.php (wp_interactivity_state). It is not
 * declared in the store below: a client-side value would override it.
 */
const serverState = (): { restUrl: string } =>
	state as unknown as { restUrl: string };

const { state } = store( 'profchoice/newsletter', {
	state: {
		get isSubmitting(): boolean {
			return getContext< NewsletterContext >().status === 'submitting';
		},
		get isSuccess(): boolean {
			const { status, hasPending } = getContext< NewsletterContext >();
			return (
				status === 'success' || ( status === 'pending' && ! hasPending )
			);
		},
		// Subscribed, waiting for the email confirmation.
		get isPending(): boolean {
			const { status, hasPending } = getContext< NewsletterContext >();
			return status === 'pending' && hasPending;
		},
		// Success or pending: the field and button are no longer needed.
		get isDone(): boolean {
			const { status } = getContext< NewsletterContext >();
			return status === 'success' || status === 'pending';
		},
		get isError(): boolean {
			return getContext< NewsletterContext >().status === 'error';
		},
	},
	actions: {
		*submit( event: SubmitEvent ): Generator< unknown, void, unknown > {
			event.preventDefault();

			const form = event.currentTarget as HTMLFormElement;
			const context = getContext< NewsletterContext >();

			if ( context.status === 'submitting' || ! form.reportValidity() ) {
				return;
			}

			context.status = 'submitting';

			const data = new FormData( form );
			const body = {
				email: String( data.get( 'email' ) ?? '' ),
				website: String( data.get( 'website' ) ?? '' ),
			};

			try {
				const response = ( yield fetch( serverState().restUrl, {
					method: 'POST',
					headers: { 'Content-Type': 'application/json' },
					body: JSON.stringify( body ),
				} ) ) as Response;
				const json = ( yield response
					.json()
					.catch( () => ( {} ) ) ) as SubscribeResponse;

				if ( ! response.ok || ! json.ok ) {
					throw new Error( json.message ?? response.statusText );
				}

				context.status = json.pending ? 'pending' : 'success';
				form.reset();
			} catch {
				context.status = 'error';
			}
		},
	},
} );

export { state };
