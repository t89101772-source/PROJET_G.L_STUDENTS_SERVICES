import { useMemo, useState, useRef } from 'react'
import { AnimatePresence, motion } from 'framer-motion'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { useNavigate } from 'react-router-dom'
import { createPortal } from 'react-dom'
import { FileText, Clock, MessageSquare, TrendingUp, History as HistoryIcon, LogOut, CheckCircle, XCircle, RotateCcw, Ban, Send, Mail, Eye, GraduationCap, Search, Filter } from 'lucide-react'
import StatCard from '../../components/Layout/StatCard'
import { useAuth } from '../../context/AuthContext'
import { demandeService, reclamationService, statsService } from '../../services/api'
import LoadingPage from '../../components/LoadingPage'

export default function AdminDashboard() {
  const { user } = useAuth()
  const navigate = useNavigate()
  const queryClient = useQueryClient()
  const { logout } = useAuth()

  const [historyOpen, setHistoryOpen] = useState(false)
  const historyButtonRef = useRef(null)
  const [historyPosition, setHistoryPosition] = useState({ top: 0, right: 0 })

  // Références pour navigation entre réclamations et demandes
  const demandesSectionRef = useRef(null)
  const demandeRowRefs = useRef({})

  // Demandes modals
  const [selectedDemande, setSelectedDemande] = useState(null)
  const [justification, setJustification] = useState('')
  const [selectedDemandeType, setSelectedDemandeType] = useState('Tous')
  const [sendingDemandeEmailId, setSendingDemandeEmailId] = useState(null)
  const [generatingDemandeId, setGeneratingDemandeId] = useState(null)
  const [viewDemande, setViewDemande] = useState(null)
  const [demandeDocument, setDemandeDocument] = useState(null) // Document de demande à consulter

  // Reclamations modals
  const [selectedReclamation, setSelectedReclamation] = useState(null)
  const [reponse, setReponse] = useState('')
  const [selectedReject, setSelectedReject] = useState(null)
  const [rejectMotif, setRejectMotif] = useState('')
  const [viewReclamation, setViewReclamation] = useState(null)
  const [selectedReclamationType, setSelectedReclamationType] = useState('Tous')
  const [reclamationDocument, setReclamationDocument] = useState(null) // Document à consulter
  const [highlightedDemandeId, setHighlightedDemandeId] = useState(null)
  const [activePage, setActivePage] = useState('home') // 'home', 'demandes', 'reclamations', 'historique'
  
  // États pour les filtres de l'historique
  const [historySearchTerm, setHistorySearchTerm] = useState('')
  const [historyFilterStatus, setHistoryFilterStatus] = useState('Tous')
  const [historyFilterType, setHistoryFilterType] = useState('Tous')
  const [historyFilterDate, setHistoryFilterDate] = useState('')
  const [historyFilterRecordType, setHistoryFilterRecordType] = useState('Tous')
  const [historyFiltersOpen, setHistoryFiltersOpen] = useState(false)

  const { data: stats, isLoading } = useQuery({
    queryKey: ['adminStats'],
    queryFn: () => statsService.getAdminStats(),
  })

  const { data: demandes } = useQuery({
    queryKey: ['demandes'],
    queryFn: () => demandeService.getAll(),
  })

  const { data: reclamations } = useQuery({
    queryKey: ['reclamations'],
    queryFn: () => reclamationService.getAll(),
  })

  // Filtrer pour ne montrer que les demandes "En attente" dans le tableau de gestion
  const sortedDemandes = useMemo(() => {
    if (!demandes) return []
    return [...demandes]
      .filter(d => d.status === 'En attente') // Seulement les demandes en attente
      .sort((a, b) => new Date(b.date_demande) - new Date(a.date_demande))
  }, [demandes])

  // Historique combiné : demandes + réclamations, trié par date (plus récent en premier) - 5 derniers pour dropdown
  const history5 = useMemo(() => {
    const all = []
    
    // Ajouter les demandes avec leur type
    if (demandes) {
      demandes.forEach(d => {
        all.push({
          id: d.id,
          type: 'demande',
          document_type: d.document_type,
          status: d.status,
          date: d.date_demande,
          numero: d.numero_demande || d.numero_attestation,
          nom: d.nom || '',
          prenom: d.prenom || ''
        })
      })
    }
    
    // Ajouter les réclamations avec leur type
    if (reclamations) {
      reclamations.forEach(r => {
        all.push({
          id: r.id,
          type: 'reclamation',
          document_type: r.document_type,
          status: r.status,
          date: r.date_reclamation,
          numero: r.numero_attestation_reclamee || r.numero_demande_reclamee,
          nom: r.nom || '',
          prenom: r.prenom || ''
        })
      })
    }
    
    // Trier par date (plus récent en premier) et prendre les 5 premiers
    return all
      .sort((a, b) => new Date(b.date) - new Date(a.date))
      .slice(0, 5)
  }, [demandes, reclamations])

  // Historique complet pour la page Historique
  const historyAll = useMemo(() => {
    const all = []
    
    // Ajouter les demandes avec leur type
    if (demandes) {
      demandes.forEach(d => {
        all.push({
          id: d.id,
          type: 'demande',
          document_type: d.document_type,
          status: d.status,
          date: d.date_demande,
          numero: d.numero_demande || d.numero_attestation,
          nom: d.nom || '',
          prenom: d.prenom || '',
          justification: d.justification || ''
        })
      })
    }
    
    // Ajouter les réclamations avec leur type
    if (reclamations) {
      reclamations.forEach(r => {
        all.push({
          id: r.id,
          type: 'reclamation',
          document_type: r.document_type,
          status: r.status,
          date: r.date_reclamation,
          numero: r.numero_attestation_reclamee || r.numero_demande_reclamee,
          nom: r.nom || '',
          prenom: r.prenom || '',
          reponse: r.reponse || ''
        })
      })
    }
    
    // Trier par date (plus récent en premier) - TOUS les éléments
    return all.sort((a, b) => new Date(b.date) - new Date(a.date))
  }, [demandes, reclamations])

  // Statuts disponibles pour l'historique
  const historyStatuses = useMemo(() => {
    const statusSet = new Set()
    historyAll.forEach(item => {
      if (item.status) statusSet.add(item.status)
    })
    return ['Tous', ...Array.from(statusSet).sort()]
  }, [historyAll])

  // Types de documents disponibles pour l'historique
  const historyDocumentTypes = useMemo(() => {
    const typeSet = new Set()
    historyAll.forEach(item => {
      if (item.document_type) typeSet.add(item.document_type)
    })
    return ['Tous', ...Array.from(typeSet).sort()]
  }, [historyAll])

  // Historique filtré
  const filteredHistory = useMemo(() => {
    let filtered = [...historyAll]
    
    // Filtrer par type d'enregistrement
    if (historyFilterRecordType !== 'Tous') {
      filtered = filtered.filter(item => item.type === historyFilterRecordType)
    }
    
    // Filtrer par statut
    if (historyFilterStatus !== 'Tous') {
      filtered = filtered.filter(item => item.status === historyFilterStatus)
    }
    
    // Filtrer par type de document
    if (historyFilterType !== 'Tous') {
      filtered = filtered.filter(item => item.document_type === historyFilterType)
    }
    
    // Filtrer par date
    if (historyFilterDate) {
      const filterDate = new Date(historyFilterDate)
      filtered = filtered.filter(item => {
        const itemDate = new Date(item.date)
        return itemDate.toDateString() === filterDate.toDateString()
      })
    }
    
    // Filtrer par recherche
    if (historySearchTerm) {
      const term = historySearchTerm.toLowerCase()
      filtered = filtered.filter(item => 
        (item.nom && item.nom.toLowerCase().includes(term)) ||
        (item.prenom && item.prenom.toLowerCase().includes(term)) ||
        (item.numero && item.numero.toLowerCase().includes(term)) ||
        (item.document_type && item.document_type.toLowerCase().includes(term))
      )
    }
    
    return filtered
  }, [historyAll, historyFilterRecordType, historyFilterStatus, historyFilterType, historyFilterDate, historySearchTerm])

  const demandeTypeCounts = useMemo(() => {
    const counts = {}
    for (const d of sortedDemandes) {
      const t = d.document_type || 'Autre'
      counts[t] = (counts[t] || 0) + 1
    }
    return counts
  }, [sortedDemandes])

  const demandeTypes = useMemo(() => {
    const main = ['Attestation de scolarité', 'Attestation de réussite', 'Relevé de notes', 'Convention de stage']
    const set = new Set(sortedDemandes.map((d) => d.document_type).filter(Boolean))
    const extras = [...set].filter((t) => !main.includes(t) && t !== 'Réclamation')
    return ['Tous', ...main, ...extras]
  }, [sortedDemandes])

  const filteredDemandes = useMemo(() => {
    if (selectedDemandeType === 'Tous') return sortedDemandes
    return sortedDemandes.filter((d) => (d.document_type || 'Autre') === selectedDemandeType)
  }, [sortedDemandes, selectedDemandeType])

  // Filtrer pour ne montrer que les réclamations "En attente" dans le tableau de gestion
  const sortedReclamations = useMemo(() => {
    if (!reclamations) return []
    return [...reclamations]
      .filter(r => r.status === 'En attente') // Seulement les réclamations en attente
      .sort((a, b) => new Date(b.date_reclamation) - new Date(a.date_reclamation))
  }, [reclamations])

  const reclamationTypeCounts = useMemo(() => {
    const counts = {}
    for (const r of sortedReclamations) {
      const t = r.document_type || 'Autre'
      counts[t] = (counts[t] || 0) + 1
    }
    return counts
  }, [sortedReclamations])

  const reclamationTypes = useMemo(() => {
    const main = ['Attestation de scolarité', 'Attestation de réussite', 'Relevé de notes', 'Convention de stage']
    const set = new Set(sortedReclamations.map((r) => r.document_type).filter(Boolean))
    const extras = [...set].filter((t) => !main.includes(t))
    return ['Tous', ...main, ...extras]
  }, [sortedReclamations])

  const filteredReclamations = useMemo(() => {
    if (selectedReclamationType === 'Tous') return sortedReclamations
    return sortedReclamations.filter((r) => (r.document_type || 'Autre') === selectedReclamationType)
  }, [sortedReclamations, selectedReclamationType])

  const actionBtnBase =
    'flex flex-col items-center justify-center gap-0.5 h-10 w-[92px] rounded-xl text-[10px] font-semibold transition-colors'
  const actionIconClass = 'w-3.5 h-3.5'

  const [actionMessage, setActionMessage] = useState(null)

  const updateDemandeMutation = useMutation({
    mutationFn: ({ id, status, justification }) => demandeService.updateStatus(id, status, justification),
    onSuccess: (data, variables) => {
      queryClient.invalidateQueries(['demandes'])
      queryClient.invalidateQueries(['adminStats'])
      setSelectedDemande(null)
      setJustification('')
      
      // Afficher un message de succès selon l'action
      if (variables.status === 'Acceptée') {
        setActionMessage({ type: 'success', text: 'Demande acceptée. Le document sera généré et envoyé par email automatiquement.' })
        setTimeout(() => setActionMessage(null), 5000)
      } else if (variables.status === 'Refusée') {
        setActionMessage({ type: 'success', text: 'Demande refusée. L\'étudiant a été notifié par email.' })
        setTimeout(() => setActionMessage(null), 5000)
      }
    },
    onError: (error) => {
      console.error('Error updating demande:', error)
      setActionMessage({ type: 'error', text: 'Erreur: ' + (error.response?.data?.error || error.response?.data?.message || error.message) })
      setTimeout(() => setActionMessage(null), 5000)
    },
  })

  const sendDemandeEmailMutation = useMutation({
    mutationFn: (demandeId) => {
      console.log('🔄 Tentative d\'envoi d\'email pour demande ID:', demandeId)
      if (!demandeId) {
        console.error('❌ Erreur: demandeId est vide ou undefined')
        throw new Error('ID de demande manquant')
      }
      return demandeService.sendEmailWithDocument(demandeId)
    },
    onMutate: (demandeId) => {
      setSendingDemandeEmailId(demandeId)
    },
    onSuccess: (data) => {
      console.log('✅ Réponse API reçue:', data)
      // Vérifier que l'email a réellement été envoyé
      if (data && data.success === true) {
        queryClient.invalidateQueries(['demandes'])
        alert('Email envoyé avec succès.')
      } else {
        // Si success n'est pas true, c'est une erreur
        const errorMsg = data?.message || data?.error || 'L\'email n\'a pas pu être envoyé'
        console.error('❌ Erreur dans la réponse:', data)
        alert('Erreur: ' + errorMsg)
      }
    },
    onError: (error) => {
      console.error('❌ Erreur lors de l\'envoi d\'email:', error)
      console.error('❌ Détails de l\'erreur:', {
        response: error.response?.data,
        status: error.response?.status,
        message: error.message
      })
      const errorMsg = error.response?.data?.error || error.response?.data?.message || error.message || 'Erreur inconnue lors de l\'envoi de l\'email'
      alert('Erreur email: ' + errorMsg)
    },
    onSettled: () => {
      setSendingDemandeEmailId(null)
    },
  })

  const handleConsultDemande = async (demande) => {
    try {
      // Si pas encore généré, générer d'abord (sinon /download-document renvoie "Document non généré")
      if (!demande?.document_path) {
        setGeneratingDemandeId(demande.id)
        await demandeService.generateDocument(demande.id)
        await queryClient.invalidateQueries(['demandes'])
        // Récupérer la demande mise à jour
        const updatedDemandes = await demandeService.getAll()
        const updatedDemande = updatedDemandes.find(d => d.id === demande.id)
        setDemandeDocument(updatedDemande || demande)
      } else {
        setDemandeDocument(demande)
      }
    } catch (error) {
      console.error('Error consulting demande:', error)
      const apiMsg = error?.response?.data?.message || error?.response?.data?.error
      alert('Impossible de consulter le document: ' + (apiMsg || error.message))
    } finally {
      setGeneratingDemandeId(null)
    }
  }

  // Fonction pour ouvrir le document d'une réclamation
  const handleViewReclamationDocument = async (reclamation) => {
    try {
      // Trouver la demande associée à cette réclamation
      const numeroReclame = reclamation.numero_attestation_reclamee || reclamation.numero_demande_reclamee
      if (!numeroReclame) {
        alert('Impossible de trouver le document associé à cette réclamation')
        return
      }
      
      // Chercher la demande correspondante
      const allDemandes = await demandeService.getAll()
      const demandeAssociee = allDemandes.find(d => 
        d.numero_attestation === numeroReclame || 
        d.numero_demande === numeroReclame
      )
      
      if (!demandeAssociee) {
        alert('Document non trouvé pour cette réclamation')
        return
      }
      
      // Si pas encore généré, générer d'abord
      if (!demandeAssociee.document_path) {
        setGeneratingDemandeId(demandeAssociee.id)
        await demandeService.generateDocument(demandeAssociee.id)
        await queryClient.invalidateQueries(['demandes'])
        const updatedDemandes = await demandeService.getAll()
        const updatedDemande = updatedDemandes.find(d => d.id === demandeAssociee.id)
        setReclamationDocument({
          reclamation: reclamation,
          demande: updatedDemande || demandeAssociee
        })
      } else {
        setReclamationDocument({
          reclamation: reclamation,
          demande: demandeAssociee
        })
      }
    } catch (error) {
      console.error('Error viewing reclamation document:', error)
      const apiMsg = error?.response?.data?.message || error?.response?.data?.error
      alert('Impossible de consulter le document: ' + (apiMsg || error.message))
    } finally {
      setGeneratingDemandeId(null)
    }
  }

  // Fonction pour ouvrir le document depuis l'historique
  const handleViewHistoryDocument = async (item) => {
    if (item.type === 'demande') {
      // Pour une demande, utiliser handleConsultDemande
      const demande = demandes?.find(d => d.id === item.id)
      if (demande) {
        await handleConsultDemande(demande)
      }
    } else if (item.type === 'reclamation') {
      // Pour une réclamation, utiliser handleViewReclamationDocument
      const reclamation = reclamations?.find(r => r.id === item.id)
      if (reclamation) {
        await handleViewReclamationDocument(reclamation)
      }
    }
  }

  const respondReclamationMutation = useMutation({
    mutationFn: ({ id, reponse }) => reclamationService.respond(id, reponse),
    onSuccess: () => {
      queryClient.invalidateQueries(['reclamations'])
      queryClient.invalidateQueries(['adminStats'])
      setSelectedReclamation(null)
      setReponse('')
    },
    onError: (error) => {
      console.error('Error responding reclamation:', error)
      alert('Erreur: ' + (error.response?.data?.error || error.response?.data?.message || error.message))
    },
  })

  const rejectReclamationMutation = useMutation({
    mutationFn: ({ id, motif }) => reclamationService.reject(id, motif),
    onSuccess: () => {
      queryClient.invalidateQueries(['reclamations'])
      queryClient.invalidateQueries(['adminStats'])
      setSelectedReject(null)
      setRejectMotif('')
    },
    onError: (error) => {
      console.error('Error rejecting reclamation:', error)
      alert('Erreur: ' + (error.response?.data?.error || error.response?.data?.message || error.message))
    },
  })

  const reopenReclamationMutation = useMutation({
    mutationFn: (id) => reclamationService.reopen(id),
    onSuccess: () => {
      queryClient.invalidateQueries(['reclamations'])
      queryClient.invalidateQueries(['adminStats'])
    },
    onError: (error) => {
      console.error('Error reopening reclamation:', error)
      alert('Erreur: ' + (error.response?.data?.error || error.response?.data?.message || error.message))
    },
  })

  const resendReclamationMutation = useMutation({
    mutationFn: (id) => reclamationService.resendDocument(id),
    onSuccess: () => {
      alert('Document renvoyé par email.')
    },
    onError: (error) => {
      console.error('Error resending document:', error)
      alert('Erreur: ' + (error.response?.data?.error || error.response?.data?.message || error.message))
    },
  })

  const closeReclamationMutation = useMutation({
    mutationFn: (id) => reclamationService.close(id),
    onSuccess: () => {
      queryClient.invalidateQueries(['reclamations'])
      queryClient.invalidateQueries(['adminStats'])
    },
    onError: (error) => {
      console.error('Error closing reclamation:', error)
      alert('Erreur: ' + (error.response?.data?.error || error.response?.data?.message || error.message))
    },
  })

  // Indicateur global de chargement pour les actions sur les réclamations
  const reclamationActionLoading =
    respondReclamationMutation.isPending ||
    rejectReclamationMutation.isPending ||
    reopenReclamationMutation.isPending ||
    resendReclamationMutation.isPending ||
    closeReclamationMutation.isPending

  // Fonction pour obtenir le badge de statut
  const getStatusBadge = (status) => {
    const badges = {
      'En attente': { color: 'bg-yellow-100 text-yellow-800', label: 'En attente' },
      'Acceptée': { color: 'bg-green-100 text-green-800', label: 'Acceptée' },
      'Traitée': { color: 'bg-blue-100 text-blue-800', label: 'Traitée' },
      'Refusée': { color: 'bg-red-100 text-red-800', label: 'Refusée' },
      'Rejetée': { color: 'bg-red-100 text-red-800', label: 'Rejetée' },
      'En cours': { color: 'bg-blue-100 text-blue-800', label: 'En cours' },
      'Résolue': { color: 'bg-green-100 text-green-800', label: 'Résolue' },
      'Fermée': { color: 'bg-gray-100 text-gray-800', label: 'Fermée' },
      'Ouverte': { color: 'bg-yellow-100 text-yellow-800', label: 'Ouverte' }
    }
    const badge = badges[status] || { color: 'bg-gray-100 text-gray-800', label: status || 'Inconnu' }
    return (
      <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${badge.color}`}>
        {badge.label}
      </span>
    )
  }

  if (isLoading) return <LoadingPage />

  return (
    <div className="min-h-screen flex flex-col bg-gradient-to-br from-slate-50 via-indigo-50/60 to-slate-50">
      {/* Message de feedback pour les actions */}
      {actionMessage && (
        <div className={`fixed top-20 right-4 z-50 px-6 py-4 rounded-lg shadow-lg flex items-center gap-3 ${
          actionMessage.type === 'success' ? 'bg-green-500 text-white' : 'bg-red-500 text-white'
        }`}>
          <span>{actionMessage.text}</span>
          <button onClick={() => setActionMessage(null)} className="text-white hover:text-gray-200">×</button>
        </div>
      )}
      {/* Background pro (texture subtile) */}
      <div
        className="pointer-events-none fixed inset-0 opacity-[0.05]"
        style={{
          backgroundImage:
            'radial-gradient(circle at 18% 18%, rgba(37,99,235,1) 0, rgba(37,99,235,0) 45%), radial-gradient(circle at 82% 28%, rgba(99,102,241,1) 0, rgba(99,102,241,0) 42%), radial-gradient(circle at 50% 92%, rgba(212,175,55,1) 0, rgba(212,175,55,0) 48%)',
        }}
      />
      {/* Top nav style HomePage */}
      <nav className="bg-white border-b border-gray-200 shadow-sm sticky top-0 z-50 relative">
        {/* Animation nav bar (néon) */}
        <div
          className="absolute inset-x-0 bottom-0 h-[3px] opacity-90"
          style={{
            background:
              'linear-gradient(90deg, rgba(37,99,235,0), rgba(37,99,235,1), rgba(99,102,241,1), rgba(212,175,55,1), rgba(37,99,235,0))',
            backgroundSize: '220% 100%',
            animation: 'adminNeonBar 3.6s linear infinite',
          }}
        />
        <style>{`
          @keyframes adminNeonBar {
            0% { background-position: 0% 50%; }
            100% { background-position: 220% 50%; }
          }
        `}</style>
        <div className="max-w-screen-2xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex items-center justify-between h-16">
            <div className="flex items-center gap-4">
        <motion.div
                animate={{
                  boxShadow: [
                    '0 0 0 rgba(37,99,235,0)',
                    '0 0 18px rgba(37,99,235,0.35)',
                    '0 0 0 rgba(37,99,235,0)',
                  ],
                }}
                transition={{ duration: 2.4, repeat: Infinity, ease: 'easeInOut' }}
                className="flex items-center justify-center w-12 h-12 bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden"
              >
                <img src="/logo_novatech_mark.svg" alt="NovaTech" className="w-10 h-10" />
              </motion.div>
              <div>
                <h1 className="text-xl font-bold text-gray-900">UnivDocs</h1>
                <p className="text-xs text-gray-600">NovaTech - Université Cité des Sciences</p>
              </div>
              </div>

            <div className="flex items-center gap-3 relative">
              {/* Navigation par onglets */}
              <div className="flex items-center gap-1 bg-gray-100 rounded-lg p-1">
                <button
                  onClick={() => setActivePage('home')}
                  className={`px-4 py-2 text-sm font-medium rounded-md transition-colors ${
                    activePage === 'home'
                      ? 'bg-white text-blue-700 shadow-sm'
                      : 'text-gray-600 hover:text-gray-900'
                  }`}
                >
                  Accueil
                </button>
                <button
                  onClick={() => setActivePage('demandes')}
                  className={`px-4 py-2 text-sm font-medium rounded-md transition-colors ${
                    activePage === 'demandes'
                      ? 'bg-white text-blue-700 shadow-sm'
                      : 'text-gray-600 hover:text-gray-900'
                  }`}
                >
                  Demandes
                </button>
                <button
                  onClick={() => setActivePage('reclamations')}
                  className={`px-4 py-2 text-sm font-medium rounded-md transition-colors ${
                    activePage === 'reclamations'
                      ? 'bg-white text-blue-700 shadow-sm'
                      : 'text-gray-600 hover:text-gray-900'
                  }`}
                >
                  Réclamations
                </button>
                <button
                  onClick={() => setActivePage('historique')}
                  className={`px-4 py-2 text-sm font-medium rounded-md transition-colors ${
                    activePage === 'historique'
                      ? 'bg-white text-blue-700 shadow-sm'
                      : 'text-gray-600 hover:text-gray-900'
                  }`}
                >
                  Historique
                </button>
              </div>

              <button
                onClick={() => { 
                  logout()
                  // Utiliser window.location pour forcer une navigation complète et éviter la redirection de PrivateRoute
                  window.location.href = '/'
                }}
                className="flex items-center gap-2 px-4 py-2 text-sm font-medium text-red-700 bg-red-50 hover:bg-red-100 rounded-lg transition-colors border border-red-200"
              >
                <LogOut className="w-4 h-4" />
                Déconnexion
              </button>
              </div>
            </div>
          </div>
      </nav>

      {/* History dropdown portal - Outside nav to avoid overflow issues */}
      {typeof window !== 'undefined' && createPortal(
        <AnimatePresence>
          {historyOpen && (
            <motion.div
              initial={{ opacity: 0, y: 8, scale: 0.98 }}
              animate={{ opacity: 1, y: 0, scale: 1 }}
              exit={{ opacity: 0, y: 8, scale: 0.98 }}
              transition={{ duration: 0.16 }}
              style={{
                position: 'fixed',
                top: `${historyPosition.top}px`,
                right: `${historyPosition.right}px`,
                zIndex: 9999
              }}
              className="w-[420px] max-w-[92vw] bg-white border border-gray-200 rounded-xl shadow-2xl overflow-hidden"
              onMouseEnter={() => setHistoryOpen(true)}
              onMouseLeave={() => setHistoryOpen(false)}
            >
              <div className="px-4 py-3 bg-gray-50 border-b border-gray-200 flex items-center justify-between">
                <p className="text-sm font-semibold text-gray-900">5 derniers enregistrements</p>
                <button
                  onClick={() => navigate('/admin/history')}
                  className="text-sm font-semibold text-blue-700 hover:text-blue-900"
                >
                  Voir tout
                </button>
              </div>
              <div className="p-3 space-y-2">
                {history5.length === 0 ? (
                  <div className="p-3 text-sm text-gray-600">Aucun historique.</div>
                ) : (
                  history5.map((item) => (
                    <div key={`h-${item.type}-${item.id}`} className="p-3 rounded-lg border border-gray-200 bg-white hover:bg-gray-50">
                      <div className="flex items-center justify-between gap-2">
                        <div className="flex items-center gap-2">
                          <span className={`text-xs font-semibold px-2 py-0.5 rounded ${
                            item.type === 'demande' 
                              ? 'bg-blue-100 text-blue-700' 
                              : 'bg-purple-100 text-purple-700'
                          }`}>
                            {item.type === 'demande' ? 'Demande' : 'Réclamation'}
                          </span>
                          <div className="text-sm font-semibold text-gray-900">#{item.id} — {item.document_type || 'N/A'}</div>
                        </div>
                        <div className="text-xs text-gray-600">{item.status}</div>
                      </div>
                      <div className="text-xs text-gray-500 mt-1">
                        {item.date ? new Date(item.date).toLocaleString('fr-FR') : ''}
                      </div>
                    </div>
                  ))
                )}
              </div>
            </motion.div>
          )}
        </AnimatePresence>,
        document.body
      )}

      <main className="flex-1 max-w-screen-2xl mx-auto w-full px-4 sm:px-6 lg:px-8 py-8">
        <motion.div initial={{ opacity: 0, y: 12 }} animate={{ opacity: 1, y: 0 }}>
          {/* Page Home - Statistiques */}
          {activePage === 'home' && (
            <div>
          {/* Stats Cards - Design Professionnel Clair */}
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <StatCard
              title="Total Demandes"
              value={stats?.total_demandes || 0}
              icon={FileText}
              color="blue"
              delay={0.1}
            />
            <StatCard
              title="En Attente"
              value={stats?.demandes_en_attente || 0}
              icon={Clock}
              color="orange"
              delay={0.2}
            />
            <StatCard
              title="Total Réclamations"
              value={stats?.total_reclamations || 0}
              icon={MessageSquare}
              color="primary"
              delay={0.3}
            />
            <StatCard
              title="Réclamations Ouvertes"
              value={stats?.reclamations_ouvertes || 0}
              icon={TrendingUp}
              color="red"
              delay={0.4}
            />
          </div>

          {/* Deux sections en parallèle (Demandes / Réclamations) */}
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {/* Demandes en attente - Aperçu */}
                <motion.div
                  whileHover={{ scale: 1.02 }}
                  transition={{ type: 'spring', stiffness: 320, damping: 24 }}
                  className="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden transition-all duration-200 hover:border-blue-500/70 hover:shadow-[0_0_0_1px_rgba(37,99,235,0.55),0_0_26px_rgba(37,99,235,0.35),0_0_64px_rgba(99,102,241,0.18)]"
                >
                  <div className="px-6 py-4 bg-gradient-to-r from-blue-600 to-blue-700 text-white">
                    <div className="flex items-center justify-between">
                      <div className="flex items-center gap-2">
                        <FileText className="w-5 h-5" />
                        <h3 className="text-lg font-bold">Demandes en attente</h3>
                      </div>
                      <div className="text-lg font-semibold">{filteredDemandes.length}</div>
                    </div>
                  </div>
                  <div className="p-6">
                    <p className="text-sm text-gray-600 mb-4">
                      {filteredDemandes.length > 0 
                        ? `${filteredDemandes.length} demande(s) nécessitent votre attention`
                        : 'Aucune demande en attente'}
                    </p>
                    <button
                      onClick={() => setActivePage('demandes')}
                      className="w-full px-4 py-2 bg-blue-600 text-white rounded-lg font-semibold hover:bg-blue-700 transition-colors"
                    >
                      Voir toutes les demandes
                    </button>
                  </div>
                </motion.div>

                {/* Réclamations en attente - Aperçu */}
                <motion.div
                  whileHover={{ scale: 1.02 }}
                  transition={{ type: 'spring', stiffness: 320, damping: 24 }}
                  className="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden transition-all duration-200 hover:border-indigo-500/70 hover:shadow-[0_0_0_1px_rgba(99,102,241,0.55),0_0_26px_rgba(99,102,241,0.35),0_0_64px_rgba(212,175,55,0.14)]"
                >
                  <div className="px-6 py-4 bg-gradient-to-r from-indigo-600 to-indigo-700 text-white">
                    <div className="flex items-center justify-between">
                      <div className="flex items-center gap-2">
                        <MessageSquare className="w-5 h-5" />
                        <h3 className="text-lg font-bold">Réclamations en attente</h3>
                      </div>
                      <div className="text-lg font-semibold">{filteredReclamations.length}</div>
                    </div>
                  </div>
                  <div className="p-6">
                    <p className="text-sm text-gray-600 mb-4">
                      {filteredReclamations.length > 0 
                        ? `${filteredReclamations.length} réclamation(s) nécessitent votre attention`
                        : 'Aucune réclamation en attente'}
                    </p>
                    <button
                      onClick={() => setActivePage('reclamations')}
                      className="w-full px-4 py-2 bg-indigo-600 text-white rounded-lg font-semibold hover:bg-indigo-700 transition-colors"
                    >
                      Voir toutes les réclamations
                    </button>
                  </div>
                </motion.div>
              </div>
            </div>
          )}

          {/* Page Demandes */}
          {activePage === 'demandes' && (
            <div>
              <div className="mb-6">
                <h2 className="text-2xl font-bold text-gray-900">Gestion des Demandes</h2>
                <p className="text-sm text-gray-600 mt-1">Gérez les demandes en attente</p>
              </div>
            <motion.div
              whileHover={{ scale: 1.02 }}
              transition={{ type: 'spring', stiffness: 320, damping: 24 }}
              ref={demandesSectionRef}
              className="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden transition-all duration-200 hover:border-blue-500/70 hover:shadow-[0_0_0_1px_rgba(37,99,235,0.55),0_0_26px_rgba(37,99,235,0.35),0_0_64px_rgba(99,102,241,0.18)]"
            >
              <div className="px-6 py-4 bg-gray-50 border-b border-gray-200 flex items-center justify-between">
                <div className="flex items-center gap-2">
                  <FileText className="w-5 h-5 text-blue-700" />
                    <h3 className="text-lg font-bold text-gray-900">Demandes en attente</h3>
                </div>
                <div className="text-sm font-semibold text-gray-700">{filteredDemandes.length}</div>
              </div>

              {/* Filtres par type (sans emojis) */}
              <div className="px-6 py-4 border-b border-gray-200 bg-white">
                <div className="flex flex-wrap gap-2">
                  {demandeTypes.map((t) => {
                    const count = t === 'Tous' ? sortedDemandes.length : (demandeTypeCounts[t] || 0)
                    const active = selectedDemandeType === t
                    return (
                      <button
                        key={t}
                        onClick={() => setSelectedDemandeType(t)}
                        className={`px-3 py-2 rounded-xl text-xs font-semibold border transition-colors ${
                          active
                            ? 'bg-blue-600 text-white border-blue-600'
                            : 'bg-gray-50 text-gray-700 border-gray-200 hover:bg-gray-100'
                        }`}
                      >
                        {t} <span className={`${active ? 'text-white/90' : 'text-gray-500'}`}>({count})</span>
                      </button>
                    )
                  })}
                </div>
              </div>
              <div className="overflow-x-auto max-h-[520px] overflow-y-auto">
                <table className="w-full">
                  <motion.thead 
                    initial={{ opacity: 0, y: -10 }}
                    animate={{ opacity: 1, y: 0 }}
                    transition={{ duration: 0.4, ease: "easeOut" }}
                    className="sticky top-0 z-10"
                  >
                    <tr className="text-xs">
                      <motion.th 
                        className="px-6 py-3 text-left font-semibold text-white"
                        style={{
                          background: 'linear-gradient(135deg, #3b82f6 0%, #2563eb 50%, #1d4ed8 100%)',
                          backgroundSize: '200% 200%',
                        }}
                        animate={{
                          backgroundPosition: ['0% 50%', '100% 50%', '0% 50%'],
                        }}
                        transition={{
                          duration: 3,
                          repeat: Infinity,
                          ease: "linear"
                        }}
                      >
                        ID
                      </motion.th>
                      <motion.th 
                        className="px-6 py-3 text-left font-semibold text-white"
                        style={{
                          background: 'linear-gradient(135deg, #3b82f6 0%, #2563eb 50%, #1d4ed8 100%)',
                          backgroundSize: '200% 200%',
                        }}
                        animate={{
                          backgroundPosition: ['0% 50%', '100% 50%', '0% 50%'],
                        }}
                        transition={{
                          duration: 3,
                          repeat: Infinity,
                          ease: "linear"
                        }}
                      >
                        Type
                      </motion.th>
                      <motion.th 
                        className="px-6 py-3 text-left font-semibold text-white"
                        style={{
                          background: 'linear-gradient(135deg, #3b82f6 0%, #2563eb 50%, #1d4ed8 100%)',
                          backgroundSize: '200% 200%',
                        }}
                        animate={{
                          backgroundPosition: ['0% 50%', '100% 50%', '0% 50%'],
                        }}
                        transition={{
                          duration: 3,
                          repeat: Infinity,
                          ease: "linear"
                        }}
                      >
                        Statut
                      </motion.th>
                      <motion.th 
                        className="px-6 py-3 text-left font-semibold text-white min-w-[310px]"
                        style={{
                          background: 'linear-gradient(135deg, #3b82f6 0%, #2563eb 50%, #1d4ed8 100%)',
                          backgroundSize: '200% 200%',
                        }}
                        animate={{
                          backgroundPosition: ['0% 50%', '100% 50%', '0% 50%'],
                        }}
                        transition={{
                          duration: 3,
                          repeat: Infinity,
                          ease: "linear"
                        }}
                      >
                        Actions
                      </motion.th>
                    </tr>
                  </motion.thead>
                  <tbody className="divide-y divide-gray-100 text-sm">
                    {filteredDemandes.map((d) => (
                      <tr
                        key={d.id}
                        data-demande-id={d.id}
                        ref={(el) => {
                          if (el) {
                            demandeRowRefs.current[d.id] = el
                          }
                        }}
                        className={`hover:bg-gray-50 align-top transition-colors ${
                          highlightedDemandeId === d.id ? 'bg-indigo-50 ring-2 ring-indigo-300' : ''
                        }`}
                      >
                        <td className="px-6 py-3 font-semibold text-gray-900">#{d.id}</td>
                        <td className="px-6 py-3 text-gray-700">{d.document_type}</td>
                        <td className="px-6 py-3 text-gray-700">{d.status}</td>
                        <td className="px-6 py-3">
                          {/* Logique dynamique (comme avant) */}
                          {d.status === 'En attente' && (
                            <div className="flex flex-wrap items-start gap-1 min-w-[310px]">
                              <button
                                onClick={() => setViewDemande(d)}
                                className={`${actionBtnBase} bg-gray-100 text-gray-800 hover:bg-gray-200`}
                              >
                                <Eye className={actionIconClass} />
                                Détails
                              </button>
                              <button
                                  onClick={() => updateDemandeMutation.mutate({ id: d.id, status: 'Acceptée', justification: null })}
                                  disabled={updateDemandeMutation.isPending}
                                  className={`${actionBtnBase} bg-blue-600 text-white hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed`}
                                >
                                  <CheckCircle className={actionIconClass} />
                                  {updateDemandeMutation.isPending ? '...' : 'Accepter'}
                              </button>
                              <button
                                onClick={() => { setSelectedDemande(d); setJustification('') }}
                                className={`${actionBtnBase} bg-red-600 text-white hover:bg-red-700`}
                              >
                                <XCircle className={actionIconClass} />
                                Refuser
                              </button>
                            </div>
                          )}
                          {d.status === 'Acceptée' && (
                            <div className="flex flex-wrap items-start gap-1 min-w-[200px]">
                              <button
                                onClick={() => setViewDemande(d)}
                                className={`${actionBtnBase} bg-gray-100 text-gray-800 hover:bg-gray-200`}
                              >
                                <Eye className={actionIconClass} />
                                Détails
                              </button>
                              <button
                                onClick={() => handleConsultDemande(d)}
                                className={`${actionBtnBase} bg-indigo-600 text-white hover:bg-indigo-700`}
                              >
                                <FileText className={actionIconClass} />
                                Consulter
                              </button>
                            </div>
                          )}
                        </td>
                      </tr>
                    ))}
                    {sortedDemandes.length === 0 && (
                      <tr>
                          <td colSpan={4} className="px-6 py-10 text-center text-gray-500">Aucune demande en attente</td>
                      </tr>
                    )}
                  </tbody>
                </table>
              </div>
            </motion.div>
            </div>
          )}

          {/* Page Réclamations */}
          {activePage === 'reclamations' && (
            <div>
              <div className="mb-6">
                <h2 className="text-2xl font-bold text-gray-900">Gestion des Réclamations</h2>
                <p className="text-sm text-gray-600 mt-1">Gérez les réclamations en attente</p>
              </div>
          <motion.div
              whileHover={{ scale: 1.02 }}
              transition={{ type: 'spring', stiffness: 320, damping: 24 }}
              className="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden transition-all duration-200 hover:border-indigo-500/70 hover:shadow-[0_0_0_1px_rgba(99,102,241,0.55),0_0_26px_rgba(99,102,241,0.35),0_0_64px_rgba(212,175,55,0.14)]"
            >
              <div className="px-6 py-4 bg-gray-50 border-b border-gray-200 flex items-center justify-between">
                <div className="flex items-center gap-2">
                  <MessageSquare className="w-5 h-5 text-indigo-700" />
                    <h3 className="text-lg font-bold text-gray-900">Réclamations en attente</h3>
                </div>
                <div className="text-sm font-semibold text-gray-700">{filteredReclamations.length}</div>
              </div>

              {/* Filtres par type (document réclamé) */}
              <div className="px-6 py-4 border-b border-gray-200 bg-white">
                <div className="flex flex-wrap gap-2">
                  {reclamationTypes.map((t) => {
                    const count = t === 'Tous' ? sortedReclamations.length : (reclamationTypeCounts[t] || 0)
                    const active = selectedReclamationType === t
                    return (
                      <button
                        key={t}
                        onClick={() => setSelectedReclamationType(t)}
                        className={`px-3 py-2 rounded-xl text-xs font-semibold border transition-colors ${
                          active
                            ? 'bg-indigo-600 text-white border-indigo-600'
                            : 'bg-gray-50 text-gray-700 border-gray-200 hover:bg-gray-100'
                        }`}
                      >
                        {t} <span className={`${active ? 'text-white/90' : 'text-gray-500'}`}>({count})</span>
                      </button>
                    )
                  })}
                </div>
              </div>
              <div className="overflow-x-auto max-h-[520px] overflow-y-auto">
                <table className="w-full">
                  <motion.thead 
                    initial={{ opacity: 0, y: -10 }}
            animate={{ opacity: 1, y: 0 }}
                    transition={{ duration: 0.4, ease: "easeOut" }}
                    className="sticky top-0 z-10"
                  >
                    <tr className="text-xs">
                      <motion.th 
                        className="px-6 py-3 text-left font-semibold text-white"
                        style={{
                          background: 'linear-gradient(135deg, #6366f1 0%, #4f46e5 50%, #4338ca 100%)',
                          backgroundSize: '200% 200%',
                        }}
                        animate={{
                          backgroundPosition: ['0% 50%', '100% 50%', '0% 50%'],
                        }}
                        transition={{
                          duration: 3,
                          repeat: Infinity,
                          ease: "linear"
                        }}
                      >
                        ID
                      </motion.th>
                      <motion.th 
                        className="px-6 py-3 text-left font-semibold text-white"
                        style={{
                          background: 'linear-gradient(135deg, #6366f1 0%, #4f46e5 50%, #4338ca 100%)',
                          backgroundSize: '200% 200%',
                        }}
                        animate={{
                          backgroundPosition: ['0% 50%', '100% 50%', '0% 50%'],
                        }}
                        transition={{
                          duration: 3,
                          repeat: Infinity,
                          ease: "linear"
                        }}
                      >
                        Étudiant
                      </motion.th>
                      <motion.th 
                        className="px-6 py-3 text-left font-semibold text-white"
                        style={{
                          background: 'linear-gradient(135deg, #6366f1 0%, #4f46e5 50%, #4338ca 100%)',
                          backgroundSize: '200% 200%',
                        }}
                        animate={{
                          backgroundPosition: ['0% 50%', '100% 50%', '0% 50%'],
                        }}
                        transition={{
                          duration: 3,
                          repeat: Infinity,
                          ease: "linear"
                        }}
                      >
                        Doc
                      </motion.th>
                      <motion.th 
                        className="px-6 py-3 text-left font-semibold text-white"
                        style={{
                          background: 'linear-gradient(135deg, #6366f1 0%, #4f46e5 50%, #4338ca 100%)',
                          backgroundSize: '200% 200%',
                        }}
                        animate={{
                          backgroundPosition: ['0% 50%', '100% 50%', '0% 50%'],
                        }}
                        transition={{
                          duration: 3,
                          repeat: Infinity,
                          ease: "linear"
                        }}
                      >
                        Statut
                      </motion.th>
                      <motion.th 
                        className="px-6 py-3 text-left font-semibold text-white min-w-[360px]"
                        style={{
                          background: 'linear-gradient(135deg, #6366f1 0%, #4f46e5 50%, #4338ca 100%)',
                          backgroundSize: '200% 200%',
                        }}
                        animate={{
                          backgroundPosition: ['0% 50%', '100% 50%', '0% 50%'],
                        }}
                        transition={{
                          duration: 3,
                          repeat: Infinity,
                          ease: "linear"
                        }}
                      >
                        Actions
                      </motion.th>
                    </tr>
                  </motion.thead>
                  <tbody className="divide-y divide-gray-100 text-sm">
                    {filteredReclamations.map((r) => (
                      <tr key={r.id} className="hover:bg-gray-50 align-top">
                        <td className="px-6 py-3 font-semibold text-gray-900">#{r.id}</td>
                        <td className="px-6 py-3 text-gray-700">{r.nom} {r.prenom}</td>
                        <td className="px-6 py-3 text-gray-700">
                          <div className="font-medium">{r.document_type}</div>
                          <div className="text-xs text-gray-500">
                            {r.numero_attestation_reclamee || r.numero_demande_reclamee || ''}
                          </div>
                        </td>
                          <td className="px-6 py-3 text-gray-700">{r.status}</td>
                        <td className="px-6 py-3">
                          {/* Actions adaptatives */}
                          {(r.status === 'En attente' || r.status === 'En cours') && (
                            <div className="flex flex-wrap items-start gap-1 min-w-[450px]">
                              <button
                                onClick={() => setViewReclamation(r)}
                                disabled={reclamationActionLoading}
                                className={`${actionBtnBase} bg-gray-100 text-gray-800 hover:bg-gray-200 disabled:opacity-50 disabled:cursor-not-allowed`}
                              >
                                <Eye className={actionIconClass} />
                                Voir
                              </button>
                              <button
                                onClick={() => handleViewReclamationDocument(r)}
                                disabled={reclamationActionLoading || generatingDemandeId}
                                className={`${actionBtnBase} bg-blue-600 text-white hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed`}
                              >
                                <FileText className={actionIconClass} />
                                Document
                              </button>
                              <button
                                onClick={() => { setSelectedReclamation(r); setReponse('') }}
                                disabled={reclamationActionLoading}
                                className={`${actionBtnBase} bg-indigo-600 text-white hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed`}
                              >
                                <Send className={actionIconClass} />
                                Répondre
                              </button>
                              <button
                                onClick={() => { setSelectedReject(r); setRejectMotif('') }}
                                disabled={reclamationActionLoading}
                                className={`${actionBtnBase} bg-red-600 text-white hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed`}
                              >
                                <Ban className={actionIconClass} />
                                Rejeter
                              </button>
                              </div>
                            )}
                          </td>
                        </tr>
                      ))}
                      {sortedReclamations.length === 0 && (
                        <tr>
                          <td colSpan={5} className="px-6 py-10 text-center text-gray-500">Aucune réclamation en attente</td>
                        </tr>
                      )}
                    </tbody>
                  </table>
                </div>
              </motion.div>
            </div>
          )}

          {/* Page Historique - Avec filtres */}
          {activePage === 'historique' && (
            <div>
              <div className="mb-6">
                <h2 className="text-2xl font-bold text-gray-900">Historique Complet</h2>
                <p className="text-sm text-gray-600 mt-1">Consultez l'historique complet des demandes et réclamations</p>
              </div>

              {/* Filtre de recherche en haut */}
              <div className="mb-6">
                <div className="bg-white/95 backdrop-blur-sm rounded-2xl shadow-lg p-4">
                  <div className="relative">
                    <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400" />
                    <input
                      type="text"
                      value={historySearchTerm}
                      onChange={(e) => setHistorySearchTerm(e.target.value)}
                      placeholder="Rechercher par nom, ID, type de document..."
                      className="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                    />
                  </div>
                </div>
              </div>

              {/* Bouton pour ouvrir/fermer les filtres */}
              <div className="mb-4 flex items-center justify-between">
                <button
                  onClick={() => setHistoryFiltersOpen(!historyFiltersOpen)}
                  className="flex items-center gap-2 px-4 py-2 bg-white/95 backdrop-blur-sm rounded-lg shadow-sm border border-gray-200 hover:bg-gray-50 transition-colors"
                >
                  <Filter className="w-4 h-4 text-gray-600" />
                  <span className="text-sm font-medium text-gray-700">
                    {historyFiltersOpen ? 'Masquer les filtres' : 'Afficher les filtres'}
                  </span>
                </button>
              </div>

              {/* Filtres horizontaux */}
              {historyFiltersOpen && (
                <div className="mb-6 bg-white/95 backdrop-blur-sm rounded-2xl shadow-lg p-4">
                  <div className="flex flex-wrap items-center gap-4">
                  {/* Type d'enregistrement */}
                  <div className="flex items-center gap-2">
                    <span className="text-sm font-medium text-gray-700">Type:</span>
                    <div className="flex gap-2">
                      {['Tous', 'demande', 'reclamation'].map((type) => {
                        const isActive = historyFilterRecordType === type
                        const label = type === 'Tous' ? 'Tous' : type === 'demande' ? 'Demandes' : 'Réclamations'
                        const IconComponent = type === 'demande' ? FileText : type === 'reclamation' ? MessageSquare : null
                        return (
                          <button
                            key={type}
                            onClick={() => setHistoryFilterRecordType(type)}
                            className={`px-3 py-1.5 rounded-lg text-xs font-semibold border transition-all inline-flex items-center gap-1.5 ${
                              isActive
                                ? 'bg-blue-600 text-white border-blue-600 shadow-md'
                                : 'bg-gray-50 text-gray-700 border-gray-200 hover:bg-gray-100'
                            }`}
                          >
                            {IconComponent && <IconComponent className="w-3 h-3" />}
                            {label}
                          </button>
                        )
                      })}
                    </div>
                  </div>

                  {/* Statut */}
                  <div className="flex items-center gap-2">
                    <span className="text-sm font-medium text-gray-700">Statut:</span>
                    <div className="flex flex-wrap gap-2">
                      {historyStatuses.slice(0, 6).map((status) => {
                        const isActive = historyFilterStatus === status
                        return (
                          <button
                            key={status}
                            onClick={() => setHistoryFilterStatus(status)}
                            className={`px-3 py-1.5 rounded-lg text-xs font-semibold border transition-all ${
                              isActive
                                ? 'bg-indigo-600 text-white border-indigo-600 shadow-md'
                                : 'bg-gray-50 text-gray-700 border-gray-200 hover:bg-gray-100'
                            }`}
                          >
                            {status}
                          </button>
                        )
                      })}
                    </div>
                  </div>

                  {/* Type de document */}
                  <div className="flex items-center gap-2">
                    <span className="text-sm font-medium text-gray-700">Document:</span>
                    <div className="flex flex-wrap gap-2">
                      {historyDocumentTypes.slice(0, 5).map((type) => {
                        const isActive = historyFilterType === type
                        return (
                          <button
                            key={type}
                            onClick={() => setHistoryFilterType(type)}
                            className={`px-3 py-1.5 rounded-lg text-xs font-semibold border transition-all ${
                              isActive
                                ? 'bg-purple-600 text-white border-purple-600 shadow-md'
                                : 'bg-gray-50 text-gray-700 border-gray-200 hover:bg-gray-100'
                            }`}
                          >
                            {type}
                          </button>
                        )
                      })}
                    </div>
                  </div>

                  {/* Date */}
                  <div className="flex items-center gap-2">
                    <span className="text-sm font-medium text-gray-700">Date:</span>
                    <input
                      type="date"
                      value={historyFilterDate}
                      onChange={(e) => setHistoryFilterDate(e.target.value)}
                      className="px-3 py-1.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-xs"
                    />
                  </div>

                  {/* Bouton réinitialiser */}
                  <button
                    onClick={() => {
                      setHistorySearchTerm('')
                      setHistoryFilterStatus('Tous')
                      setHistoryFilterType('Tous')
                      setHistoryFilterDate('')
                      setHistoryFilterRecordType('Tous')
                    }}
                    className="ml-auto px-3 py-1.5 text-xs text-gray-600 hover:text-gray-900 underline"
                  >
                    Réinitialiser
                  </button>
                  </div>
                </div>
              )}

              {/* Table */}
              <div className="bg-white/95 backdrop-blur-sm rounded-2xl shadow-lg overflow-hidden">
                    <div className="px-6 py-4 bg-gray-50 border-b border-gray-200">
                      <p className="text-sm text-gray-600">
                        <span className="font-semibold">{filteredHistory.length}</span> enregistrement(s) trouvé(s)
                      </p>
                    </div>
                    
                    <div className="overflow-x-auto">
                      <table className="w-full">
                        <thead className="bg-gray-50">
                          <tr>
                            <th className="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase">Type</th>
                            <th className="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase">ID</th>
                            <th className="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase">Étudiant</th>
                            <th className="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase">Document</th>
                            <th className="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase">Date</th>
                            <th className="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase">Statut</th>
                            <th className="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase">Justification/Réponse</th>
                      </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-200">
                          {filteredHistory.length === 0 ? (
                      <tr>
                              <td colSpan={8} className="px-6 py-12 text-center text-gray-500">
                                Aucun enregistrement trouvé avec ces critères
                              </td>
                      </tr>
                          ) : (
                            filteredHistory.map((item, index) => (
                              <motion.tr
                                key={`${item.type}-${item.id}`}
                                initial={{ opacity: 0, x: -20 }}
                                animate={{ opacity: 1, x: 0 }}
                                transition={{ delay: index * 0.02 }}
                                className="hover:bg-gray-50 transition-colors"
                              >
                                <td className="px-6 py-4">
                                  <span className={`inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-semibold ${
                                    item.type === 'demande' 
                                      ? 'bg-blue-100 text-blue-700' 
                                      : 'bg-purple-100 text-purple-700'
                                  }`}>
                                    {item.type === 'demande' ? (
                                      <FileText className="w-3 h-3" />
                                    ) : (
                                      <MessageSquare className="w-3 h-3" />
                                    )}
                                    {item.type === 'demande' ? 'Demande' : 'Réclamation'}
                                  </span>
                                </td>
                                <td className="px-6 py-4 text-sm font-medium text-gray-900">#{item.id}</td>
                                <td className="px-6 py-4 text-sm text-gray-700">
                                  {item.nom} {item.prenom}
                                </td>
                                <td className="px-6 py-4 text-sm text-gray-700">{item.document_type || 'N/A'}</td>
                                <td className="px-6 py-4 text-sm text-gray-600">
                                  {new Date(item.date).toLocaleDateString('fr-FR', {
                                    year: 'numeric',
                                    month: 'long',
                                    day: 'numeric',
                                    hour: '2-digit',
                                    minute: '2-digit'
                                  })}
                                </td>
                                <td className="px-6 py-4">{getStatusBadge(item.status)}</td>
                                <td className="px-6 py-4 text-sm text-gray-600">
                                  {item.justification ? (
                                    <span className="text-red-600 italic">{item.justification}</span>
                                  ) : (
                                    <span className="text-gray-400">-</span>
                                  )}
                                </td>
                                <td className="px-6 py-4">
                                  {/* Bouton Voir document (seulement pour les demandes avec document_path) */}
                                  {item.type === 'demande' && (
                                    <button
                                      onClick={() => handleViewHistoryDocument(item)}
                                      disabled={generatingDemandeId}
                                      className="px-3 py-1.5 bg-blue-600 text-white rounded-lg text-xs font-semibold hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-1"
                                    >
                                      <FileText className="w-3 h-3" />
                                      Voir document
                                    </button>
                                  )}
                                  {item.type === 'reclamation' && (
                                    <button
                                      onClick={() => handleViewHistoryDocument(item)}
                                      disabled={generatingDemandeId}
                                      className="px-3 py-1.5 bg-blue-600 text-white rounded-lg text-xs font-semibold hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-1"
                                    >
                                      <FileText className="w-3 h-3" />
                                      Voir document
                                    </button>
                                  )}
                                </td>
                              </motion.tr>
                            ))
                    )}
                  </tbody>
                </table>
              </div>
            </div>
          </div>
          )}

          {/* Modals (toujours visibles quelle que soit la page) */}
          {/* Modal Refus Demande */}
          {selectedDemande && (
            <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
              <motion.div initial={{ scale: 0.98, opacity: 0 }} animate={{ scale: 1, opacity: 1 }} className="bg-white rounded-2xl p-6 max-w-md w-full">
                <h3 className="text-xl font-bold text-gray-900 mb-2">Refuser la demande #{selectedDemande.id}</h3>
                <p className="text-sm text-gray-600 mb-4">Justification obligatoire (envoyée à l’étudiant).</p>
                <textarea
                  value={justification}
                  onChange={(e) => setJustification(e.target.value)}
                  placeholder="Justification..."
                  rows={4}
                  className="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-transparent mb-4"
                />
                <div className="flex gap-3">
                  <button
                    onClick={() => { setSelectedDemande(null); setJustification('') }}
                    className="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-xl font-semibold hover:bg-gray-50"
                  >
                    Annuler
                  </button>
                  <button
                    onClick={() => updateDemandeMutation.mutate({ id: selectedDemande.id, status: 'Refusée', justification })}
                    disabled={!justification.trim()}
                    className="flex-1 px-4 py-2 bg-red-600 text-white rounded-xl font-semibold hover:bg-red-700 disabled:opacity-50"
                  >
                    Refuser
                  </button>
                </div>
              </motion.div>
            </div>
          )}

          {/* Modal Réponse Réclamation */}
          {selectedReclamation && (
            <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
              <motion.div initial={{ scale: 0.98, opacity: 0 }} animate={{ scale: 1, opacity: 1 }} className="bg-white rounded-2xl p-6 max-w-md w-full">
                <h3 className="text-xl font-bold text-gray-900 mb-4">Répondre à la réclamation #{selectedReclamation.id}</h3>
                <textarea
                  value={reponse}
                  onChange={(e) => setReponse(e.target.value)}
                  placeholder="Votre réponse..."
                  rows={5}
                  className="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-transparent mb-4"
                />
                <div className="flex gap-3">
                  <button
                    onClick={() => { setSelectedReclamation(null); setReponse('') }}
                    className="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-xl font-semibold hover:bg-gray-50"
                  >
                    Annuler
                  </button>
                  <button
                    onClick={() => respondReclamationMutation.mutate({ id: selectedReclamation.id, reponse })}
                    disabled={!reponse.trim() || reclamationActionLoading}
                    className="flex-1 px-4 py-2 bg-indigo-600 text-white rounded-xl font-semibold hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                    Envoyer
                  </button>
                </div>
              </motion.div>
            </div>
          )}

          {/* Modal Rejet Réclamation */}
          {selectedReject && (
            <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
              <motion.div initial={{ scale: 0.98, opacity: 0 }} animate={{ scale: 1, opacity: 1 }} className="bg-white rounded-2xl p-6 max-w-md w-full">
                <h3 className="text-xl font-bold text-gray-900 mb-2">Rejeter la réclamation #{selectedReject.id}</h3>
                <p className="text-sm text-gray-600 mb-4">Motif obligatoire (envoyé par email).</p>
                <textarea
                  value={rejectMotif}
                  onChange={(e) => setRejectMotif(e.target.value)}
                  placeholder="Motif de rejet..."
                  rows={4}
                  className="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-transparent mb-4"
                />
                <div className="flex gap-3">
                  <button
                    onClick={() => { setSelectedReject(null); setRejectMotif('') }}
                    className="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-xl font-semibold hover:bg-gray-50"
                  >
                    Annuler
                  </button>
                  <button
                    onClick={() => rejectReclamationMutation.mutate({ id: selectedReject.id, motif: rejectMotif })}
                    disabled={!rejectMotif.trim() || reclamationActionLoading}
                    className="flex-1 px-4 py-2 bg-red-600 text-white rounded-xl font-semibold hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed"
                  >
                    Rejeter
                  </button>
                </div>
              </motion.div>
            </div>
          )}

          {/* Modal Consultation Réclamation */}
          {viewReclamation && (
            <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
              <motion.div
                initial={{ scale: 0.98, opacity: 0 }}
                animate={{ scale: 1, opacity: 1 }}
                className="bg-white rounded-2xl p-6 max-w-2xl w-full"
              >
                <div className="flex items-start justify-between gap-3">
                  <div>
                    <h3 className="text-xl font-bold text-gray-900">Réclamation #{viewReclamation.id}</h3>
                    <p className="text-sm text-gray-600 mt-1">
                      <span className="font-semibold">{viewReclamation.nom} {viewReclamation.prenom}</span> — {viewReclamation.email}
                    </p>
                    <p className="text-sm text-gray-600 mt-1">
                      <span className="font-semibold">Document:</span> {viewReclamation.document_type}{' '}
                      {viewReclamation.numero_attestation_reclamee ? `(${viewReclamation.numero_attestation_reclamee})` : ''}
                    </p>
                    <p className="text-sm text-gray-600 mt-1">
                      <span className="font-semibold">Statut:</span> {viewReclamation.status}
                    </p>
                    <p className="text-sm text-gray-600 mt-1">
                      <span className="font-semibold">Motif:</span> {viewReclamation.motif || '—'}
                    </p>
                    {/* Mini-historique */}
                    <div className="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs text-gray-600">
                      <div>
                        <span className="font-semibold">Date de création:</span>{' '}
                        {viewReclamation.date_reclamation
                          ? new Date(viewReclamation.date_reclamation).toLocaleString('fr-FR')
                          : '—'}
                  </div>
                  <div>
                        <span className="font-semibold">Dernière réponse:</span>{' '}
                        {viewReclamation.date_reponse
                          ? new Date(viewReclamation.date_reponse).toLocaleString('fr-FR')
                          : '—'}
                      </div>
                    </div>
            </div>
                  <button
                    onClick={() => setViewReclamation(null)}
                    className="px-3 py-2 rounded-lg bg-gray-100 hover:bg-gray-200 text-sm font-semibold text-gray-800"
                  >
                    Fermer
                  </button>
                </div>

                <div className="mt-5">
                  <p className="text-sm font-semibold text-gray-900 mb-2">Description</p>
                  <div className="rounded-xl border border-gray-200 bg-gray-50 p-4 text-sm text-gray-800 whitespace-pre-line">
                    {viewReclamation.description}
                  </div>
                </div>

                {viewReclamation.reponse_admin && (
                  <div className="mt-4">
                    <p className="text-sm font-semibold text-gray-900 mb-2">Réponse admin</p>
                    <div className="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-900 whitespace-pre-line">
                      {viewReclamation.reponse_admin}
                  </div>
                </div>
                )}
              </motion.div>
                  </div>
          )}

          {/* Modal Consultation Document Demande */}
          {demandeDocument && (
            <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
              <motion.div
                initial={{ scale: 0.98, opacity: 0 }}
                animate={{ scale: 1, opacity: 1 }}
                className="bg-white rounded-2xl p-6 max-w-5xl w-full h-[90vh] flex flex-col"
              >
                <div className="flex items-start justify-between gap-3 mb-4">
                  <div>
                    <h3 className="text-xl font-bold text-gray-900">
                      Document - Demande #{demandeDocument.id}
                    </h3>
                    <p className="text-sm text-gray-600 mt-1">
                      <span className="font-semibold">{demandeDocument.document_type}</span>
                      {demandeDocument.numero_attestation && 
                        ` (${demandeDocument.numero_attestation})`}
                    </p>
                  </div>
                </div>

                {/* PDF Viewer */}
                <div className="flex-1 border border-gray-200 rounded-xl overflow-hidden bg-gray-100">
                  <iframe
                    src={`${import.meta.env.VITE_API_URL || 'http://localhost:8000'}/api/download-document?demande_id=${demandeDocument.id}`}
                    className="w-full h-full"
                    title="Document PDF"
                  />
                </div>

                {/* Actions */}
                <div className="flex items-center justify-end gap-3 mt-4 pt-4 border-t border-gray-200">
                  <button
                    onClick={() => {
                      window.print()
                    }}
                    className="px-4 py-2 bg-gray-600 text-white rounded-xl font-semibold hover:bg-gray-700 flex items-center gap-2"
                  >
                    <FileText className="w-4 h-4" />
                    Imprimer
                  </button>
                  <button
                    onClick={() => {
                      console.log('🔵 Bouton "Envoyer email" cliqué pour demande ID:', demandeDocument.id)
                      if (!demandeDocument || !demandeDocument.id) {
                        console.error('❌ Erreur: demandeDocument ou ID manquant', demandeDocument)
                        alert('Erreur: Impossible de trouver l\'ID de la demande')
                        return
                      }
                      sendDemandeEmailMutation.mutate(demandeDocument.id, {
                        onSuccess: () => {
                          // Fermer la modal seulement après succès
                          setDemandeDocument(null)
                        },
                        onError: () => {
                          // Ne pas fermer la modal en cas d'erreur pour voir le message
                        }
                      })
                    }}
                    disabled={sendDemandeEmailMutation.isPending || demandeDocument.email_sent == 1}
                    className="px-4 py-2 bg-blue-600 text-white rounded-xl font-semibold hover:bg-blue-700 flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
                  >
                    <Mail className="w-4 h-4" />
                    {sendDemandeEmailMutation.isPending ? 'Envoi...' : 'Envoyer email'}
                  </button>
                  <button
                    onClick={() => setDemandeDocument(null)}
                    className="px-4 py-2 bg-gray-200 text-gray-800 rounded-xl font-semibold hover:bg-gray-300"
                  >
                    Annuler
                  </button>
                </div>
              </motion.div>
                  </div>
          )}

          {/* Modal Consultation Document Réclamation */}
          {reclamationDocument && (
            <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
              <motion.div
                initial={{ scale: 0.98, opacity: 0 }}
                animate={{ scale: 1, opacity: 1 }}
                className="bg-white rounded-2xl p-6 max-w-5xl w-full h-[90vh] flex flex-col"
              >
                <div className="flex items-start justify-between gap-3 mb-4">
                  <div>
                    <h3 className="text-xl font-bold text-gray-900">
                      Document - Réclamation #{reclamationDocument.reclamation.id}
                    </h3>
                    <p className="text-sm text-gray-600 mt-1">
                      <span className="font-semibold">{reclamationDocument.reclamation.document_type}</span>
                      {reclamationDocument.reclamation.numero_attestation_reclamee && 
                        ` (${reclamationDocument.reclamation.numero_attestation_reclamee})`}
                    </p>
                  </div>
                </div>

                {/* PDF Viewer */}
                <div className="flex-1 border border-gray-200 rounded-xl overflow-hidden bg-gray-100">
                  <iframe
                    src={`${import.meta.env.VITE_API_URL || 'http://localhost:8000'}/api/download-document?demande_id=${reclamationDocument.demande.id}`}
                    className="w-full h-full"
                    title="Document PDF"
                  />
                </div>

                {/* Actions */}
                <div className="flex items-center justify-end gap-3 mt-4 pt-4 border-t border-gray-200">
                  <button
                    onClick={() => {
                      window.print()
                    }}
                    className="px-4 py-2 bg-gray-600 text-white rounded-xl font-semibold hover:bg-gray-700 flex items-center gap-2"
                  >
                    <FileText className="w-4 h-4" />
                    Imprimer
                  </button>
                  <button
                    onClick={() => {
                      resendReclamationMutation.mutate(reclamationDocument.reclamation.id)
                      setReclamationDocument(null)
                    }}
                    className="px-4 py-2 bg-indigo-600 text-white rounded-xl font-semibold hover:bg-indigo-700 flex items-center gap-2"
                  >
                    <Mail className="w-4 h-4" />
                    Renvoyer
                  </button>
                  <button
                    onClick={() => setReclamationDocument(null)}
                    className="px-4 py-2 bg-gray-200 text-gray-800 rounded-xl font-semibold hover:bg-gray-300"
                  >
                    Annuler
                  </button>
                </div>
              </motion.div>
            </div>
          )}

          {/* Modal Détails Demande (lecture seule) */}
          {viewDemande && (
            <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
              <motion.div
                initial={{ scale: 0.98, opacity: 0 }}
                animate={{ scale: 1, opacity: 1 }}
                className="bg-white rounded-2xl p-6 max-w-2xl w-full"
              >
                <div className="flex items-start justify-between gap-3">
                  <div>
                    <h3 className="text-xl font-bold text-gray-900">Demande #{viewDemande.id}</h3>
                    <p className="text-sm text-gray-600 mt-1">
                      <span className="font-semibold">{viewDemande.nom} {viewDemande.prenom}</span> — {viewDemande.apogee_number}
                    </p>
                    <p className="text-sm text-gray-600 mt-1">
                      <span className="font-semibold">Type:</span> {viewDemande.document_type}
                    </p>
                    <p className="text-sm text-gray-600 mt-1">
                      <span className="font-semibold">Statut:</span> {viewDemande.status}
                    </p>
                    {viewDemande.numero_demande && (
                      <p className="text-sm text-gray-600 mt-1">
                        <span className="font-semibold">Numéro demande:</span> {viewDemande.numero_demande}
                      </p>
                    )}
                    {viewDemande.numero_attestation && (
                      <p className="text-sm text-gray-600 mt-1">
                        <span className="font-semibold">Numéro attestation:</span> {viewDemande.numero_attestation}
                      </p>
                    )}
                    <p className="text-sm text-gray-600 mt-1">
                      <span className="font-semibold">Date:</span>{' '}
                      {viewDemande.date_demande ? new Date(viewDemande.date_demande).toLocaleString('fr-FR') : '—'}
                    </p>
                  </div>
                  <button
                    onClick={() => setViewDemande(null)}
                    className="px-3 py-2 rounded-lg bg-gray-100 hover:bg-gray-200 text-sm font-semibold text-gray-800"
                  >
                    Fermer
                  </button>
                </div>

                <div className="mt-5">
                  <p className="text-sm font-semibold text-gray-900 mb-2">Informations supplémentaires</p>
                  <div className="rounded-xl border border-gray-200 bg-gray-50 p-4 text-sm text-gray-800 whitespace-pre-wrap">
                    {(() => {
                      try {
                        const ai = viewDemande.additional_info
                        if (!ai) return '—'
                        const obj = typeof ai === 'string' ? JSON.parse(ai) : ai
                        return JSON.stringify(obj, null, 2)
                      } catch (e) {
                        return String(viewDemande.additional_info || '—')
                      }
                    })()}
                  </div>
            </div>
          </motion.div>
            </div>
          )}
        </motion.div>
      </main>

      {/* Footer - Section séparée en bas */}
      <footer className="mt-10 w-full border-t border-gray-200 bg-white">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
          <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div>
              <div className="flex items-center gap-3">
                <div className="w-10 h-10 bg-gradient-to-br from-blue-600 to-blue-800 rounded-lg flex items-center justify-center shadow-sm">
                  <GraduationCap className="w-5 h-5 text-white" />
                </div>
                <div>
                  <p className="font-bold text-gray-900">UnivDocs</p>
                  <p className="text-xs text-gray-600">NovaTech</p>
                </div>
              </div>
              <p className="mt-3 text-sm text-gray-600 max-w-sm">
                Plateforme de gestion documentaire académique: gestion des demandes, réclamations et communication avec les étudiants.
              </p>
            </div>

            <div>
              <p className="text-sm font-semibold text-gray-900">Navigation</p>
              <div className="mt-3 space-y-2">
                <button onClick={() => window.scrollTo({ top: 0, behavior: 'smooth' })} className="block text-sm text-gray-600 hover:text-gray-900 transition-colors">
                  Haut de page
                </button>
                <button onClick={() => navigate('/admin/history')} className="block text-sm text-gray-600 hover:text-gray-900 transition-colors">
                  Historique
                </button>
              </div>
            </div>

            <div>
              <p className="text-sm font-semibold text-gray-900">Établissement</p>
              <p className="mt-3 text-sm text-gray-600">École Supérieure d'Ingénierie NovaTech</p>
              <p className="mt-1 text-sm text-gray-600">Université Cité des Sciences</p>
            </div>
          </div>

          <div className="mt-8 pt-6 border-t border-gray-200 flex flex-col sm:flex-row gap-2 sm:items-center sm:justify-between">
            <p className="text-xs text-gray-500">
              © {new Date().getFullYear()} UnivDocs. Tous droits réservés.
            </p>
            <p className="text-xs text-gray-500">
              Conçu pour une expérience fluide et moderne.
            </p>
          </div>
        </div>
      </footer>
    </div>
  )
}

