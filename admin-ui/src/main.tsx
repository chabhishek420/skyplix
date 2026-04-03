import { StrictMode } from 'react'
import { createRoot } from 'react-dom/client'
import './index.css'
import App from './App.tsx'
import { QueryProvider } from './query-provider.tsx'
import { LoginGuard } from './components/auth/login-guard.tsx'

createRoot(document.getElementById('root')!).render(
  <StrictMode>
    <QueryProvider>
      <LoginGuard>
        <App />
      </LoginGuard>
    </QueryProvider>
  </StrictMode>,
)
