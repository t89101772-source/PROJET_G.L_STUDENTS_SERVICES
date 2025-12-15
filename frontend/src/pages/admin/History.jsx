import { useState, useMemo } from 'react'
import { motion } from 'framer-motion'
import { useQuery } from '@tanstack/react-query'
import { CheckCircle, XCircle, Clock, Search, Filter, MessageSquare, FileText } from 'lucide-react'
import { useAuth } from '../../context/AuthContext'
import { demandeService, reclamationService } from '../../services/api'
import LoadingPage from '../../components/LoadingPage'
import { History as HistoryIcon, LogOut } from 'lucide-react'
import { useNavigate } from 'react-router-dom'

export default function History() {
  const { user } = useAuth()
  const { logout } = useAuth()
  const navigate = useNavigate()
  const [searchTerm, setSearchTerm] = useState('')
  const [filterStatus, setFilterStatus] = useState('Tous')
  const [filterType, setFilterType] = useState('Tous')
  const [filterDate, setFilterDate] = useState('')
  const [filterRecordType, setFilterRecordType] = useState('Tous') // 'Tous', 'demande', 'reclamation'

  const { data: demandes, isLoading: isLoadingDemandes } = useQuery({
    queryKey: ['demandes'],
    queryFn: () => demandeService.getAll(),
  })

  const { data: reclamations, isLoading: isLoadingReclamations } = useQuery({
    queryKey: ['reclamations'],
    queryFn: () => reclamationService.getAll(),
  })

  // Combiner demandes et réclamations en un historique unifié
  const allHistory = useMemo(() => {
    const all = []
    
    // Ajouter les demandes
    if (demandes) {
      demandes.forEach(d => {
        all.push({
          id: d.id,
          type: 'demande',
          document_type: d.document_type,
          status: d.status,
          date: d.date_demande,
          nom: d.nom || '',
          prenom: d.prenom || '',
          justification: d.justification_refus || null
        })
      })
    }
    
    // Ajouter les réclamations
    if (reclamations) {
      reclamations.forEach(r => {
        all.push({
          id: r.id,
          type: 'reclamation',
          document_type: r.document_type,
          status: r.status,
          date: r.date_reclamation,
          nom: r.nom || '',
          prenom: r.prenom || '',
          justification: r.reponse_admin || r.reponse || null
        })
      })
    }
    
    // Trier par date (plus récent en premier)
    return all.sort((a, b) => new Date(b.date) - new Date(a.date))
  }, [demandes, reclamations])

  // Filtrer l'historique combiné
  const filteredHistory = useMemo(() => {
    if (!allHistory.length) return []

    return allHistory.filter((item) => {
      // Filtre par statut
      if (filterStatus !== 'Tous' && item.status !== filterStatus) {
        return false
      }

      // Filtre par type
      if (filterType !== 'Tous' && item.document_type !== filterType) {
        return false
      }

      // Filtre par date
      if (filterDate) {
        const itemDate = new Date(item.date).toISOString().split('T')[0]
        if (itemDate !== filterDate) {
          return false
        }
      }

      // Filtre par recherche (nom, prénom, ID, type)
      if (searchTerm) {
        const searchLower = searchTerm.toLowerCase()
        const matchesName = `${item.nom} ${item.prenom}`.toLowerCase().includes(searchLower)
        const matchesId = item.id.toString().includes(searchTerm)
        const matchesType = item.document_type?.toLowerCase().includes(searchLower)
        const matchesItemType = (item.type === 'demande' ? 'demande' : 'reclamation').includes(searchLower)
        
        if (!matchesName && !matchesId && !matchesType && !matchesItemType) {
          return false
        }
      }

      return true
    })
  }, [allHistory, filterStatus, filterType, filterDate, searchTerm])

  // Types de documents uniques (demandes + réclamations)
  const documentTypes = useMemo(() => {
    const types = new Set()
    if (demandes) {
      demandes.forEach(d => {
        if (d.document_type) types.add(d.document_type)
      })
    }
    if (reclamations) {
      reclamations.forEach(r => {
        if (r.document_type) types.add(r.document_type)
      })
    }
    return ['Tous', ...Array.from(types)]
  }, [demandes, reclamations])

  // Statuts uniques (demandes + réclamations)
  const allStatuses = useMemo(() => {
    const statuses = new Set()
    if (demandes) {
      demandes.forEach(d => {
        if (d.status) statuses.add(d.status)
      })
    }
    if (reclamations) {
      reclamations.forEach(r => {
        if (r.status) statuses.add(r.status)
      })
    }

    // On enlève les statuts qui ne sont plus utilisés dans la logique actuelle
    // (par exemple "Fermée" côté réclamations, puisque tu gardes seulement Résolue / Rejetée)
    const filtered = Array.from(statuses).filter((s) => s !== 'Fermée')

    return ['Tous', ...filtered]
  }, [demandes, reclamations])

  const isLoading = isLoadingDemandes || isLoadingReclamations

  const getStatusBadge = (status) => {
    const badges = {
      'En attente': { color: 'bg-orange-100 text-orange-700', icon: Clock },
      'Acceptée': { color: 'bg-green-100 text-green-700', icon: CheckCircle },
      'Traitée': { color: 'bg-green-100 text-green-700', icon: CheckCircle },
      'Refusée': { color: 'bg-red-100 text-red-700', icon: XCircle },
      'Rejetée': { color: 'bg-red-100 text-red-700', icon: XCircle },
      'En cours': { color: 'bg-blue-100 text-blue-700', icon: Clock },
      'Résolue': { color: 'bg-green-100 text-green-700', icon: CheckCircle },
      'Fermée': { color: 'bg-gray-100 text-gray-700', icon: XCircle },
    }
    const badge = badges[status] || { color: 'bg-gray-100 text-gray-700', icon: Clock }
    const Icon = badge.icon
    return (
      <span className={`inline-flex items-center space-x-1 px-3 py-1 rounded-full text-xs font-medium ${badge.color}`}>
        <Icon className="w-3 h-3" />
        <span>{status}</span>
      </span>
    )
  }

  if (isLoading) return <LoadingPage />

  return (
    <div className="min-h-screen bg-gray-50 relative overflow-hidden">
      {/* Animation de bulles en arrière-plan */}
      <div className="fixed inset-0 overflow-hidden pointer-events-none z-0">
        {[...Array(15)].map((_, i) => (
          <motion.div
            key={i}
            className="absolute rounded-full opacity-20"
            style={{
              width: Math.random() * 200 + 50,
              height: Math.random() * 200 + 50,
              left: `${Math.random() * 100}%`,
              top: `${Math.random() * 100}%`,
              background: `linear-gradient(135deg, 
                rgba(${Math.random() > 0.5 ? '37,99,235' : '99,102,241'}, ${0.3 + Math.random() * 0.3}),
                rgba(${Math.random() > 0.5 ? '212,175,55' : '99,102,241'}, ${0.2 + Math.random() * 0.2})
              )`,
            }}
            animate={{
              y: [0, Math.random() * 100 - 50, 0],
              x: [0, Math.random() * 100 - 50, 0],
              scale: [1, 1.2 + Math.random() * 0.3, 1],
            }}
            transition={{
              duration: 15 + Math.random() * 10,
              repeat: Infinity,
              ease: "easeInOut",
              delay: Math.random() * 5,
            }}
          />
        ))}
      </div>

      <nav className="bg-white border-b border-gray-200 shadow-sm sticky top-0 z-50 relative">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex items-center justify-between h-16">
            <div className="flex items-center gap-4">
              <div className="flex items-center justify-center w-12 h-12 bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                <img src="/logo_novatech_mark.svg" alt="NovaTech" className="w-10 h-10" />
              </div>
              <div>
                <h1 className="text-xl font-bold text-gray-900">UnivDocs</h1>
                <p className="text-xs text-gray-600">Historique - NovaTech</p>
              </div>
            </div>

            <div className="flex items-center gap-3">
              <button
                onClick={() => navigate('/admin/dashboard')}
                className="flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-lg transition-colors"
              >
                <HistoryIcon className="w-4 h-4" />
                Dashboard
              </button>
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

      <main className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 relative z-10">
        <motion.div initial={{ opacity: 0, y: 12 }} animate={{ opacity: 1, y: 0 }}>
          <div className="mb-8">
            <h1 className="text-4xl font-bold text-gray-900 mb-2">Historique Complet</h1>
            <p className="text-gray-600">Consultez l'historique complet des demandes et réclamations</p>
          </div>

          {/* Layout en parallèle : Filtres à gauche, Table à droite */}
          <div className="grid grid-cols-1 lg:grid-cols-4 gap-6">
            {/* Filtres - Colonne gauche */}
            <div className="lg:col-span-1">
              <div className="bg-white/95 backdrop-blur-sm rounded-2xl shadow-lg p-6 sticky top-24">
            <div className="flex items-center space-x-2 mb-4">
              <Filter className="w-5 h-5 text-gray-600" />
              <h2 className="text-lg font-semibold text-gray-900">Filtres de recherche</h2>
            </div>
            
            {/* Recherche textuelle */}
            <div className="mb-6">
              <label className="block text-sm font-medium text-gray-700 mb-2">
                Recherche
              </label>
              <div className="relative">
                <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400" />
                <input
                  type="text"
                  value={searchTerm}
                  onChange={(e) => setSearchTerm(e.target.value)}
                  placeholder="Nom, ID, type..."
                  className="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
              </div>
            </div>

            {/* Filtre par type d'enregistrement (Demande/Réclamation) */}
            <div className="mb-6">
              <label className="block text-sm font-medium text-gray-700 mb-3">
                Type d'enregistrement
              </label>
              <div className="flex flex-wrap gap-2">
                {['Tous', 'demande', 'reclamation'].map((type) => {
                  const isActive = filterRecordType === type
                  const label = type === 'Tous' ? 'Tous' : type === 'demande' ? 'Demandes' : 'Réclamations'
                  const IconComponent = type === 'demande' ? FileText : type === 'reclamation' ? MessageSquare : null
                  return (
                    <button
                      key={type}
                      onClick={() => setFilterRecordType(type)}
                      className={`px-4 py-2 rounded-xl text-sm font-semibold border transition-all inline-flex items-center gap-2 ${
                        isActive
                          ? 'bg-blue-600 text-white border-blue-600 shadow-md'
                          : 'bg-gray-50 text-gray-700 border-gray-200 hover:bg-gray-100'
                      }`}
                    >
                      {IconComponent && <IconComponent className="w-4 h-4" />}
                      {label}
                    </button>
                  )
                })}
              </div>
            </div>

            {/* Filtre par statut (boutons) */}
            <div className="mb-6">
              <label className="block text-sm font-medium text-gray-700 mb-3">
                Statut
              </label>
              <div className="flex flex-wrap gap-2">
                {allStatuses.map((status) => {
                  const isActive = filterStatus === status
                  return (
                    <button
                      key={status}
                      onClick={() => setFilterStatus(status)}
                      className={`px-4 py-2 rounded-xl text-sm font-semibold border transition-all ${
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

            {/* Filtre par type de document (boutons) */}
            <div className="mb-6">
              <label className="block text-sm font-medium text-gray-700 mb-3">
                Type de document
              </label>
              <div className="flex flex-wrap gap-2">
                {documentTypes.map((type) => {
                  const isActive = filterType === type
                  return (
                    <button
                      key={type}
                      onClick={() => setFilterType(type)}
                      className={`px-4 py-2 rounded-xl text-sm font-semibold border transition-all ${
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

            {/* Filtre par date */}
            <div className="mb-4">
              <label className="block text-sm font-medium text-gray-700 mb-2">
                Date
              </label>
              <input
                type="date"
                value={filterDate}
                onChange={(e) => setFilterDate(e.target.value)}
                className="w-full max-w-xs px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              />
            </div>

            {/* Bouton réinitialiser */}
            <div className="mt-4 flex justify-end">
              <button
                onClick={() => {
                  setSearchTerm('')
                  setFilterStatus('Tous')
                  setFilterType('Tous')
                  setFilterDate('')
                  setFilterRecordType('Tous')
                }}
                className="px-4 py-2 text-sm text-gray-600 hover:text-gray-900 underline"
              >
                Réinitialiser les filtres
              </button>
            </div>
              </div>
            </div>

            {/* Table - Colonne droite */}
            <div className="lg:col-span-3">
              {/* Résultats */}
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
                      <td colSpan={7} className="px-6 py-12 text-center text-gray-500">
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
                      </motion.tr>
                    ))
                  )}
                </tbody>
              </table>
            </div>
              </div>
            </div>
          </div>
        </motion.div>
      </main>
    </div>
  )
}

