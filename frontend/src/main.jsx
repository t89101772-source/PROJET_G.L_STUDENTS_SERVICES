import React from 'react'
import ReactDOM from 'react-dom/client'
import App from './App.jsx'
import './index.css'
import { QueryClient, QueryClientProvider } from '@tanstack/react-query'

const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      refetchOnWindowFocus: false,
      retry: 1,
    },
  },
})

const rootElement = document.getElementById('root')

if (!rootElement) {
  console.error('Root element not found!')
} else {
  try {
    ReactDOM.createRoot(rootElement).render(
      <React.StrictMode>
        <QueryClientProvider client={queryClient}>
          <App />
        </QueryClientProvider>
      </React.StrictMode>,
    )
  } catch (error) {
    console.error('Error rendering app:', error)
    rootElement.innerHTML = `
      <div style="padding: 50px; text-align: center;">
        <h1 style="color: red;">Erreur de chargement</h1>
        <p>${error.message}</p>
        <button onclick="window.location.reload()">Recharger</button>
      </div>
    `
  }
}

