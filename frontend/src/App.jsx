import React, { useEffect, useRef, useState } from 'react'
import { BrowserRouter as Router, Routes, Route, Navigate, useLocation } from 'react-router-dom'
import { AuthProvider, useAuth } from './context/AuthContext'
import LoadingPage from './components/LoadingPage'
import HomePage from './pages/HomePage'
import AboutPage from './pages/AboutPage'
import AdminLogin from './pages/admin/AdminLogin'
import AdminDashboard from './pages/admin/AdminDashboard'
import History from './pages/admin/History'
import { AnimatePresence } from 'framer-motion'

function RouteLoadingOverlay() {
  const location = useLocation()
  const first = useRef(true)
  const [show, setShow] = useState(true)

  useEffect(() => {
    setShow(true)
    const timeout = window.setTimeout(() => {
      setShow(false)
      first.current = false
    }, first.current ? 900 : 550)

    return () => window.clearTimeout(timeout)
  }, [location.pathname])

  return (
    <AnimatePresence mode="wait">
      {show && <LoadingPage mode="route" key={location.pathname} />}
    </AnimatePresence>
  )
}

function PrivateRoute({ children, role }) {
  const { user, loading } = useAuth()
  
  if (loading) {
    return <LoadingPage />
  }
  
  if (!user) {
    return <Navigate to="/admin/login" />
  }
  
  if (user.role !== role) {
    return <Navigate to="/admin/login" />
  }
  
  return children
}

function AppRoutes() {
  return (
    <Routes>
      <Route path="/" element={<HomePage />} />
      <Route path="/about" element={<AboutPage />} />
      
      {/* Routes Admin */}
      <Route path="/admin/login" element={<AdminLogin />} />
      <Route 
        path="/admin/dashboard" 
        element={
          <PrivateRoute role="admin">
            <AdminDashboard />
          </PrivateRoute>
        } 
      />
      <Route path="/admin/demandes" element={<Navigate to="/admin/dashboard" replace />} />
      <Route path="/admin/reclamations" element={<Navigate to="/admin/dashboard" replace />} />
      <Route 
        path="/admin/history" 
        element={
          <PrivateRoute role="admin">
            <History />
          </PrivateRoute>
        } 
      />
      
      <Route path="*" element={<Navigate to="/" />} />
    </Routes>
  )
}

function App() {
  return (
    <AuthProvider>
      <Router future={{ v7_startTransition: true, v7_relativeSplatPath: true }}>
        <div className="min-h-screen bg-gray-50">
          <RouteLoadingOverlay />
          <AppRoutes />
        </div>
      </Router>
    </AuthProvider>
  )
}

// Error Boundary pour capturer les erreurs
class ErrorBoundary extends React.Component {
  constructor(props) {
    super(props)
    this.state = { hasError: false, error: null }
  }

  static getDerivedStateFromError(error) {
    return { hasError: true, error }
  }

  componentDidCatch(error, errorInfo) {
    console.error('Error caught by boundary:', error, errorInfo)
  }

  render() {
    if (this.state.hasError) {
      return (
        <div style={{ padding: '50px', textAlign: 'center' }}>
          <h1 style={{ color: 'red' }}>Une erreur est survenue</h1>
          <p>{this.state.error?.message}</p>
          <button onClick={() => window.location.reload()}>Recharger la page</button>
        </div>
      )
    }
    return this.props.children
  }
}

// Wrapper avec Error Boundary
function AppWithErrorBoundary() {
  return (
    <ErrorBoundary>
      <App />
    </ErrorBoundary>
  )
}

export default AppWithErrorBoundary

