import axios from 'axios'

const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api'

const api = axios.create({
  baseURL: API_URL,
  headers: {
    'Content-Type': 'application/json',
  },
})

// Intercepteur pour ajouter le token
api.interceptors.request.use((config) => {
  const user = JSON.parse(localStorage.getItem('user') || '{}')
  if (user.token) {
    config.headers.Authorization = `Bearer ${user.token}`
  }
  return config
})

export const authService = {
  login: async (credentials, role) => {
    const endpoint = '/auth'
    const data = role === 'student' 
      ? { apogeeNumber: credentials.apogeeNumber, email: credentials.email }
      : { login: credentials.login, password: credentials.password }
    const response = await api.post(endpoint, data)
    return response.data
  },
}

export const demandeService = {
  getAll: async () => {
    const response = await api.get('/demandes')
    return response.data
  },
  
  getByStudent: async (apogeeNumber) => {
    const response = await api.get(`/demandes/student/${apogeeNumber}`)
    return response.data
  },
  
  create: async (data) => {
    const response = await api.post('/demandes', data)
    return response.data
  },
  
  updateStatus: async (id, status, justification = null) => {
    const response = await api.patch(`/demandes/${id}/status`, { status, justification })
    return response.data
  },
  
  generateDocument: async (demandeId) => {
    const response = await api.post('/generate-document', { demande_id: demandeId })
    return response.data
  },
  
  downloadDocument: async (demandeId, apogeeNumber = null) => {
    const url = apogeeNumber 
      ? `/download-document?demande_id=${demandeId}&apogee_number=${apogeeNumber}`
      : `/download-document?demande_id=${demandeId}`
    // Utiliser l'URL complète avec le port du backend
    const backendUrl = import.meta.env.VITE_API_URL || 'http://localhost:8000/api'
    const fullUrl = backendUrl.replace('/api', '') + url
    window.open(fullUrl, '_blank')
  },
}

export const reclamationService = {
  getAll: async () => {
    const response = await api.get('/reclamations')
    return response.data
  },
  
  getByStudent: async (apogeeNumber) => {
    const response = await api.get(`/reclamations/student/${apogeeNumber}`)
    return response.data
  },
  
  create: async (data) => {
    const response = await api.post('/reclamations', data)
    return response.data
  },
  
  respond: async (id, reponse) => {
    const response = await api.patch(`/reclamations/${id}/respond`, { reponse })
    return response.data
  },
  
  close: async (id) => {
    const response = await api.patch(`/reclamations/${id}/close`)
    return response.data
  },
}

export const statsService = {
  getStudentStats: async (apogeeNumber) => {
    const response = await api.get(`/stats/student/${apogeeNumber}`)
    return response.data
  },
  
  getAdminStats: async () => {
    const response = await api.get('/stats/admin')
    return response.data
  },
}

export const chatbotService = {
  sendMessage: async (message) => {
    const response = await api.post('/chatbot', { message })
    return response.data
  },
  
  getSuggestions: async () => {
    const user = JSON.parse(localStorage.getItem('user') || '{}')
    const userType = user.role || 'guest'
    const response = await api.get(`/chatbot?user_type=${userType}`)
    return response.data
  },
}

export default api

