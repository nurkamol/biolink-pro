import { HashRouter, Navigate, Route, Routes } from 'react-router-dom';
import { OnboardingOverlay } from './components/onboarding/OnboardingOverlay';
import { AppShell } from './components/ui/AppShell';
import { Analytics } from './pages/Analytics';
import { Changelog } from './pages/Changelog';
import { Dashboard } from './pages/Dashboard';
import { Pages } from './pages/Pages';
import { PageDetail } from './pages/PageDetail';
import { Settings } from './pages/Settings';

export function App() {
	return (
		<HashRouter>
			<AppShell>
				<OnboardingOverlay />
				<Routes>
					<Route path="/" element={ <Dashboard /> } />
					<Route path="/pages" element={ <Pages /> } />
					<Route path="/pages/:id" element={ <PageDetail /> } />
					<Route path="/analytics" element={ <Analytics /> } />
					<Route path="/changelog" element={ <Changelog /> } />
					<Route path="/settings" element={ <Settings /> } />
					<Route path="*" element={ <Navigate to="/" replace /> } />
				</Routes>
			</AppShell>
		</HashRouter>
	);
}
