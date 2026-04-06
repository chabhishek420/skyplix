import { BrowserRouter as Router, Routes, Route, Navigate, useLocation } from 'react-router-dom';
import { useEffect, useState } from 'react';
import { MainLayout } from './components/layout/main-layout';
import { Dashboard } from './pages/dashboard';
import { Campaigns } from './pages/campaigns';
import { CampaignEdit } from './pages/campaigns/edit';
import { Offers } from './pages/offers';
import { Landings } from './pages/landings';
import { Networks } from './pages/networks';
import { Sources } from './pages/sources';
import { Domains } from './pages/domains';
import { ClicksLog } from './pages/logs/clicks';
import { ConversionsLog } from './pages/logs/conversions';
import { Login } from './pages/login';
import { Stats } from './pages/stats';
import { getAuthToken } from './lib/api';

function ProtectedRoute({ children }: { children: React.ReactNode }) {
  const token = getAuthToken();
  const location = useLocation();

  if (!token) {
    return <Navigate to="/login" state={{ from: location }} replace />;
  }

  return children;
}

function App() {
  const [, setIsAuth] = useState(!!getAuthToken());

  useEffect(() => {
    const handleAuth = () => setIsAuth(!!getAuthToken());
    window.addEventListener('auth-unauthorized', handleAuth);
    return () => window.removeEventListener('auth-unauthorized', handleAuth);
  }, []);

  return (
    <Router basename="/admin">
      <Routes>
        <Route path="/login" element={<Login />} />
        <Route path="/" element={
          <ProtectedRoute>
            <MainLayout />
          </ProtectedRoute>
        }>
          <Route index element={<Dashboard />} />
          <Route path="campaigns">
            <Route index element={<Campaigns />} />
            <Route path="new" element={<CampaignEdit />} />
            <Route path=":id" element={<CampaignEdit />} />
          </Route>
          <Route path="offers" element={<Offers />} />
          <Route path="landings" element={<Landings />} />
          <Route path="networks" element={<Networks />} />
          <Route path="sources" element={<Sources />} />
          <Route path="domains" element={<Domains />} />
          <Route path="stats" element={<Stats />} />
          <Route path="logs">
            <Route path="clicks" element={<ClicksLog />} />
            <Route path="conversions" element={<ConversionsLog />} />
          </Route>
          <Route path="*" element={<Navigate to="/" replace />} />
        </Route>
      </Routes>
    </Router>
  );
}

export default App;
