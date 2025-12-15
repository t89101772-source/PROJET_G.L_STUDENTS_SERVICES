import { createContext, useContext, useState, useEffect } from 'react'
import { authService } from '../services/api'

const AuthContext = createContext()

export function AuthProvider({ children }) {
  const [user, setUser] = useState(null)
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    checkAuth()
  }, [])

  const checkAuth = async () => {
    try {
      const storedUser = localStorage.getItem('user')
      if (storedUser) {
        const userData = JSON.parse(storedUser)
        setUser(userData)
      }
    } catch (error) {
      console.error('Error checking auth:', error)
    } finally {
      setLoading(false)
    }
  }

  const login = async (credentials) => {
    try {
      const response = await authService.login(credentials)
      if (response.user) {
        const userData = {
          ...response.user,
          role: 'admin', // Seul rôle disponible maintenant
          token: response.token
        }
        setUser(userData)
        localStorage.setItem('user', JSON.stringify(userData))
        return { success: true }
      } else {
        return { 
          success: false, 
          error: response.message || response.error || 'Erreur de connexion' 
        }
      }
    } catch (error) {
      console.error('Login error:', error)
      // Gérer les erreurs CORS et réseau
      if (error.code === 'ERR_NETWORK' || error.message.includes('CORS')) {
        return { 
          success: false, 
          error: 'Erreur de connexion au serveur. Vérifiez que le serveur backend est démarré sur le port 8000.' 
        }
      }
      return { 
        success: false, 
        error: error.response?.data?.message || error.response?.data?.error || error.message || 'Erreur de connexion' 
      }
    }
  }

  const logout = () => {
    setUser(null)
    localStorage.removeItem('user')
  }

  return (
    <AuthContext.Provider value={{ user, login, logout, loading }}>
      {children}
    </AuthContext.Provider>
  )
}

export function useAuth() {
  const context = useContext(AuthContext)
  if (!context) {
    throw new Error('useAuth must be used within AuthProvider')
  }
  return context
}

