import { BrowserRouter as Router, Routes, Route, Navigate } from 'react-router-dom';
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

function App() {
  return (
    <Router basename="/admin">
      <Routes>
        <Route path="/" element={<MainLayout />}>
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
