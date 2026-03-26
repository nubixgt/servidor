/**
 * @license
 * SPDX-License-Identifier: Apache-2.0
 */

import { ReactNode } from 'react';
import { BrowserRouter, Route, Routes, Navigate } from 'react-router-dom';
import Layout from './components/Layout';
import Home from './pages/Home';
import Report from './pages/Report';
import News from './pages/News';
import Resources from './pages/Resources';
import Welcome from './pages/Welcome';

const RequireOnboarding = ({ children }: { children: ReactNode }) => {
  const hasSeen = localStorage.getItem('onboardingComplete');
  if (!hasSeen) return <Navigate to="/welcome" replace />;
  return <>{children}</>;
};

export default function App() {
  return (
    <BrowserRouter>
      <Routes>
        <Route path="/welcome" element={<Welcome />} />
        <Route path="/" element={<RequireOnboarding><Layout /></RequireOnboarding>}>
          <Route index element={<Home />} />
          <Route path="report" element={<Report />} />
          <Route path="news" element={<News />} />
          <Route path="resources" element={<Resources />} />
        </Route>
      </Routes>
    </BrowserRouter>
  );
}

