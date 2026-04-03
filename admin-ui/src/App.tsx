import { BrowserRouter as Router, Routes, Route, Navigate } from 'react-router-dom';
import { MainLayout } from './components/layout/main-layout';
import { Dashboard } from './pages/dashboard';

function App() {
  return (
    <Router basename="/admin">
      <Routes>
        <Route path="/" element={<MainLayout />}>
          <Route index element={<Dashboard />} />
          {/* Temporary stubs for remaining routes */}
          <Route path="campaigns" element={<div className="p-8">Campaigns UI soon...</div>} />
          <Route path="offers" element={<div className="p-8">Offers UI soon...</div>} />
          <Route path="landings" element={<div className="p-8">Landings UI soon...</div>} />
          <Route path="networks" element={<div className="p-8">Networks UI soon...</div>} />
          <Route path="sources" element={<div className="p-8">Traffic Sources UI soon...</div>} />
          <Route path="domains" element={<div className="p-8">Domains UI soon...</div>} />
          <Route path="logs/clicks" element={<div className="p-8">Clicks Log UI soon...</div>} />
          <Route path="logs/conversions" element={<div className="p-8">Postbacks UI soon...</div>} />
          <Route path="*" element={<Navigate to="/" replace />} />
        </Route>
      </Routes>
    </Router>
  );
}

export default App;
