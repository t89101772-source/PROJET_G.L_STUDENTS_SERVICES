import axios from 'axios'
import { getStoredUser } from './storage'

const normalizeBaseUrl = (url) => url.replace(/\/+$/, '')
const API_URL = normalizeBaseUrl(import.meta.env.VITE_API_URL || 'http://localhost:8000/api')
const API_ORIGIN = API_URL.endsWith('/api') ? API_URL.slice(0, -4) : API_URL

const api = axios.create({
  baseURL: API_URL,
  headers: {
    'Content-Type': 'application/json',
  },
})

// Request interceptor for token and dev-only logging
api.interceptors.request.use((config) => {
  const user = getStoredUser()
  if (user?.token) {
    config.headers.Authorization = `Bearer ${user.token}`
  }
  if (import.meta.env.DEV && config.url && config.url.includes('send-email-document')) {
    console.log('REQUEST API - send-email-document:', {
      url: config.url,
      method: config.method,
      data: config.data,
    })
  }
  return config
})

// Response interceptor for API errors
api.interceptors.response.use(
  (response) => {
    // Treat API error payloads as failures
    if (response.data && response.data.error && !response.data.success) {
      return Promise.reject({
        response: {
          data: response.data,
          status: response.status
        }
      })
    }
    return response
  },
  (error) => {
    // Bubble up HTTP errors
    return Promise.reject(error)
  }
)

export const authService = {
  login: async (credentials) => {
    const endpoint = '/auth'
    const data = { login: credentials.login, password: credentials.password }
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
  
  sendEmailWithDocument: async (demandeId) => {
    const response = await api.post('/send-email-document', { demande_id: demandeId })
    return response.data
  },
  
  downloadDocument: async (demandeId, apogeeNumber = null) => {
    const url = apogeeNumber 
      ? `/download-document?demande_id=${demandeId}&apogee_number=${apogeeNumber}`
      : `/download-document?demande_id=${demandeId}`
    // Utiliser l'URL complete avec le port du backend
    const fullUrl = `${normalizeBaseUrl(API_ORIGIN)}${url}`
    window.open(fullUrl, '_blank')
  },
  
  getByNumero: async (numeroDemande) => {
    const response = await api.get(`/demandes/suivi/${numeroDemande}`)
    return response.data
  },
  
  validateStudent: async (email, apogeeNumber, cin) => {
    const response = await api.post('/validate-student', { email, apogee_number: apogeeNumber, cin })
    return response.data
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

  reject: async (id, motif) => {
    const response = await api.patch(`/reclamations/${id}/reject`, { motif })
    return response.data
  },

  reopen: async (id) => {
    const response = await api.patch(`/reclamations/${id}/reopen`)
    return response.data
  },

  resendDocument: async (id) => {
    const response = await api.patch(`/reclamations/${id}/resend-document`)
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


export const niveauService = {
  getAll: async () => {
    const response = await api.get('/niveaux')
    return response.data
  },
}

export const anneeService = {
  getAll: async () => {
    const response = await api.get('/annees')
    return response.data
  },
}

export default api

