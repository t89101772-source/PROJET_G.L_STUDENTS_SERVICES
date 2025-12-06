import React from 'react'
import { BrowserRouter as Router, Routes, Route, Navigate } from 'react-router-dom'
import { AuthProvider, useAuth } from './context/AuthContext'
import LoadingPage from './components/LoadingPage'
import HomePage from './pages/HomePage'
import StudentLogin from './pages/student/StudentLogin'
import StudentDashboard from './pages/student/StudentDashboard'
import CreateRequest from './pages/student/CreateRequest'
import CreateReclamation from './pages/student/CreateReclamation'
import MyDocuments from './pages/student/MyDocuments'
import AdminLogin from './pages/admin/AdminLogin'
import AdminDashboard from './pages/admin/AdminDashboard'
import ManageDemandes from './pages/admin/ManageDemandes'
import ManageReclamations from './pages/admin/ManageReclamations'
import History from './pages/admin/History'
import Chatbot from './components/Chatbot/Chatbot'

function PrivateRoute({ children, role }) {
  const { user, loading } = useAuth()
  
  if (loading) {
    return <LoadingPage />
  }
  
  if (!user) {
    return <Navigate to={role === 'student' ? '/student/login' : '/admin/login'} />
  }
  
  if (user.role !== role) {
    return <Navigate to={role === 'student' ? '/student/login' : '/admin/login'} />
  }
  
  return children
}

function AppRoutes() {
  return (
    <Routes>
      <Route path="/" element={<HomePage />} />
      
      {/* Routes Étudiant */}
      <Route path="/student/login" element={<StudentLogin />} />
      <Route 
        path="/student/dashboard" 
        element={
          <PrivateRoute role="student">
            <StudentDashboard />
          </PrivateRoute>
        } 
      />
      <Route 
        path="/student/request" 
        element={
          <PrivateRoute role="student">
            <CreateRequest />
          </PrivateRoute>
        } 
      />
      <Route 
        path="/student/reclamation" 
        element={
          <PrivateRoute role="student">
            <CreateReclamation />
          </PrivateRoute>
        } 
      />
      <Route 
        path="/student/documents" 
        element={
          <PrivateRoute role="student">
            <MyDocuments />
          </PrivateRoute>
        } 
      />
      
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
      <Route 
        path="/admin/demandes" 
        element={
          <PrivateRoute role="admin">
            <ManageDemandes />
          </PrivateRoute>
        } 
      />
      <Route 
        path="/admin/reclamations" 
        element={
          <PrivateRoute role="admin">
            <ManageReclamations />
          </PrivateRoute>
        } 
      />
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
      <Router>
        <div className="min-h-screen bg-gray-50">
          <AppRoutes />
          <Chatbot />
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

