import { __ } from '@wordpress/i18n';
import { useParams, useLocation } from 'react-router-dom';
import { ComingSoon } from '../../components/ui/ComingSoon';

interface StubPageProps {
	title: string;
	description?: string;
}

export function StubPage( { title, description }: StubPageProps ) {
	return <ComingSoon title={ title } description={ description } />;
}

export function EarnStub() {
	return (
		<StubPage
			title={ __( 'Earn', 'biolink-pro' ) }
			description={ __(
				'Monetization tools (tips, paywalled links, course bundles) will live here. The Donation and Product Card blocks already accept Stripe + PayPal payments today.',
				'biolink-pro'
			) }
		/>
	);
}

export function AudienceStub() {
	return (
		<StubPage
			title={ __( 'Audience', 'biolink-pro' ) }
			description={ __(
				'Subscriber list, opt-ins, and email exports land in v2.1. Newsletter and Contact Form submissions already feed your configured Mailchimp / MailerLite / Resend account.',
				'biolink-pro'
			) }
		/>
	);
}

export function ToolsStub() {
	const params = useParams();
	const location = useLocation();
	const tool = params.tool ?? location.pathname.split( '/' ).pop() ?? '';
	const map: Record< string, { title: string; description: string } > = {
		'social-planner': {
			title: __( 'Social planner', 'biolink-pro' ),
			description: __(
				'Schedule posts across IG / TikTok / YouTube directly from your BioLink dashboard. v2.2+.',
				'biolink-pro'
			),
		},
		'auto-reply': {
			title: __( 'Instagram auto-reply', 'biolink-pro' ),
			description: __(
				'Automatically reply to Instagram DMs that mention specific keywords with your bio link. v2.2+.',
				'biolink-pro'
			),
		},
		shortener: {
			title: __( 'Link shortener', 'biolink-pro' ),
			description: __(
				'Generate short go/biolink/* redirects for tracking individual campaigns. v2.2+.',
				'biolink-pro'
			),
		},
		'post-ideas': {
			title: __( 'Post ideas', 'biolink-pro' ),
			description: __(
				'AI-generated content prompts based on your bio + audience. v2.2+.',
				'biolink-pro'
			),
		},
	};
	const cfg = map[ tool ] ?? {
		title: __( 'Coming soon', 'biolink-pro' ),
		description: __( 'This area is on the BioLink Pro roadmap.', 'biolink-pro' ),
	};
	return <StubPage title={ cfg.title } description={ cfg.description } />;
}
