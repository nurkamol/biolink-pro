import { HashRouter, Navigate, Route, Routes } from 'react-router-dom';
import { OnboardingOverlay } from './components/onboarding/OnboardingOverlay';
import { AppShell } from './components/ui/AppShell';
import { Analytics } from './pages/Analytics';
import { Audience } from './pages/Audience';
import { Changelog } from './pages/Changelog';
import { Dashboard } from './pages/Dashboard';
import { Pages } from './pages/Pages';
import { Settings } from './pages/Settings';
import { BuilderShell } from './pages/builder/BuilderShell';
import { DesignPage } from './pages/builder/DesignPage';
import { InsightsPage } from './pages/builder/InsightsPage';
import { LinksPage } from './pages/builder/LinksPage';
import { ShopPage } from './pages/builder/ShopPage';

export function App() {
	return (
		<HashRouter>
			<AppShell>
				<OnboardingOverlay />
				<Routes>
					<Route path="/" element={ <Dashboard /> } />
					<Route path="/pages" element={ <Pages /> } />
					<Route path="/pages/:id" element={ <BuilderShell /> }>
						<Route index element={ <Navigate to="links" replace /> } />
						<Route path="links" element={ <LinksPage /> } />
						<Route path="design" element={ <DesignPage /> } />
						<Route path="shop" element={ <ShopPage /> } />
						<Route path="insights" element={ <InsightsPage /> } />
					</Route>
					<Route path="/analytics" element={ <Analytics /> } />
					<Route path="/audience" element={ <Audience /> } />
					<Route path="/changelog" element={ <Changelog /> } />
					<Route path="/settings" element={ <Settings /> } />
					<Route path="*" element={ <Navigate to="/" replace /> } />
				</Routes>
			</AppShell>
		</HashRouter>
	);
}
