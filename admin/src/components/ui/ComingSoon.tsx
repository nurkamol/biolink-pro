import { __ } from '@wordpress/i18n';
import styles from './ComingSoon.module.css';

interface Props {
	title: string;
	description?: string;
}

export function ComingSoon( { title, description }: Props ) {
	return (
		<div className={ styles.wrap }>
			<div className={ styles.card }>
				<div className={ styles.icon }>✨</div>
				<h2 className={ styles.title }>{ title }</h2>
				<p className={ styles.body }>
					{ description ??
						__(
							'This area is on the BioLink Pro roadmap. Watch the What’s New tab for release notes.',
							'biolink-pro'
						) }
				</p>
			</div>
		</div>
	);
}
