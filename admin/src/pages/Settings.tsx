import { __ } from '@wordpress/i18n';

export function Settings() {
	return (
		<section>
			<h1>{ __( 'Settings', 'biolink-pro' ) }</h1>
			<p>
				{ __(
					'Plugin settings UI lands in Phase 7. This page is a placeholder for the admin shell skeleton.',
					'biolink-pro'
				) }
			</p>
		</section>
	);
}
