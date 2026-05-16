import { __ } from '@wordpress/i18n';
import { ComingSoon } from '../../components/ui/ComingSoon';

export function ShopPage() {
	return (
		<ComingSoon
			title={ __( 'Shop', 'biolink-pro' ) }
			description={ __(
				'The dedicated Shop view will let you curate products, sync with WooCommerce, and publish a separate Shop tab on your bio page. Until then, use the Product Card block on the Links tab.',
				'biolink-pro'
			) }
		/>
	);
}
