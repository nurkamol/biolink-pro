import { useBuilder } from './BuilderContext';
import { Analytics } from '../Analytics';

export function InsightsPage() {
	// For v2.0.0 we reuse the existing Analytics page scoped to the current page.
	// v2.1 will ship the cards-first Insights layout from the Linktree mockups.
	const { page } = useBuilder();
	return <Analytics initialPageId={ page.id } />;
}
