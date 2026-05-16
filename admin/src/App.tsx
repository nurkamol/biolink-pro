import { HashRouter, Navigate, Route, Routes } from 'react-router-dom';
import { AppShell } from './components/ui/AppShell';
import { Dashboard } from './pages/Dashboard';
import { Pages } from './pages/Pages';
import { PageDetail } from './pages/PageDetail';
import { Settings } from './pages/Settings';

export function App() {
	return (
		<HashRouter>
			<AppShell>
				<Routes>
					<Route path="/" element={ <Dashboard /> } />
					<Route path="/pages" element={ <Pages /> } />
					<Route path="/pages/:id" element={ <PageDetail /> } />
					<Route path="/settings" element={ <Settings /> } />
					<Route path="*" element={ <Navigate to="/" replace /> } />
				</Routes>
			</AppShell>
		</HashRouter>
	);
}
