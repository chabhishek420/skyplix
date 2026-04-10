import { BrowserRouter as Router, Routes, Route, Navigate } from 'react-router-dom';
import { MainLayout } from './components/layout/main-layout';
import { Dashboard } from './pages/dashboard';
import { Campaigns } from './pages/campaigns';
import { CampaignEdit } from './pages/campaigns/edit';
import { Offers } from './pages/offers';
import { OfferEdit } from './pages/offers/edit';
import { Landings } from './pages/landings';
import { LandingEdit } from './pages/landings/edit';
import { Networks } from './pages/networks';
import { NetworkEdit } from './pages/networks/edit';
import { Sources } from './pages/sources';
import { SourceEdit } from './pages/sources/edit';
import { Domains } from './pages/domains';
import { DomainEdit } from './pages/domains/edit';
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
          <Route path="offers">
            <Route index element={<Offers />} />
            <Route path="new" element={<OfferEdit />} />
            <Route path=":id" element={<OfferEdit />} />
          </Route>
          <Route path="landings">
            <Route index element={<Landings />} />
            <Route path="new" element={<LandingEdit />} />
            <Route path=":id" element={<LandingEdit />} />
          </Route>
          <Route path="networks">
            <Route index element={<Networks />} />
            <Route path="new" element={<NetworkEdit />} />
            <Route path=":id" element={<NetworkEdit />} />
          </Route>
          <Route path="sources">
            <Route index element={<Sources />} />
            <Route path="new" element={<SourceEdit />} />
            <Route path=":id" element={<SourceEdit />} />
          </Route>
          <Route path="domains">
            <Route index element={<Domains />} />
            <Route path="new" element={<DomainEdit />} />
            <Route path=":id" element={<DomainEdit />} />
          </Route>
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
