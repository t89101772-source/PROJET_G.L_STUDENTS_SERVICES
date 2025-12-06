import { useState, useMemo } from 'react'
import { motion } from 'framer-motion'
import { useQuery } from '@tanstack/react-query'
import { CheckCircle, XCircle, Clock, Search, Filter } from 'lucide-react'
import Sidebar from '../../components/Layout/Sidebar'
import { useAuth } from '../../context/AuthContext'
import { demandeService } from '../../services/api'
import LoadingPage from '../../components/LoadingPage'

export default function History() {
  const { user } = useAuth()
  const [searchTerm, setSearchTerm] = useState('')
  const [filterStatus, setFilterStatus] = useState('Tous')
  const [filterType, setFilterType] = useState('Tous')
  const [filterDate, setFilterDate] = useState('')

  const { data: demandes, isLoading } = useQuery({
    queryKey: ['demandes'],
    queryFn: () => demandeService.getAll(),
  })

  // Filtrer les demandes
  const filteredDemandes = useMemo(() => {
    if (!demandes) return []

    return demandes.filter((demande) => {
      // Filtre par statut
      if (filterStatus !== 'Tous' && demande.status !== filterStatus) {
        return false
      }

      // Filtre par type
      if (filterType !== 'Tous' && demande.document_type !== filterType) {
        return false
      }

      // Filtre par date
      if (filterDate) {
        const demandeDate = new Date(demande.date_demande).toISOString().split('T')[0]
        if (demandeDate !== filterDate) {
          return false
        }
      }

      // Filtre par recherche (nom, prénom, ID)
      if (searchTerm) {
        const searchLower = searchTerm.toLowerCase()
        const matchesName = `${demande.nom} ${demande.prenom}`.toLowerCase().includes(searchLower)
        const matchesId = demande.id.toString().includes(searchTerm)
        const matchesType = demande.document_type?.toLowerCase().includes(searchLower)
        
        if (!matchesName && !matchesId && !matchesType) {
          return false
        }
      }

      return true
    })
  }, [demandes, filterStatus, filterType, filterDate, searchTerm])

  // Types de documents uniques
  const documentTypes = useMemo(() => {
    if (!demandes) return []
    const types = [...new Set(demandes.map(d => d.document_type).filter(Boolean))]
    return ['Tous', ...types]
  }, [demandes])

  const getStatusBadge = (status) => {
    const badges = {
      'En attente': { color: 'bg-orange-100 text-orange-700', icon: Clock },
      'Acceptée': { color: 'bg-success-100 text-success-700', icon: CheckCircle },
      'Refusée': { color: 'bg-red-100 text-red-700', icon: XCircle },
    }
    const badge = badges[status] || badges['En attente']
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
    <div className="flex min-h-screen bg-gray-50">
      <Sidebar user={user} />
      
      <main className="flex-1 ml-64 p-8">
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          className="max-w-7xl mx-auto"
        >
          <div className="mb-8">
            <h1 className="text-4xl font-bold text-gray-900 mb-2">Historique des Demandes</h1>
            <p className="text-gray-600">Consultez l'historique complet des demandes</p>
          </div>

          {/* Filtres */}
          <div className="bg-white rounded-2xl shadow-lg p-6 mb-6">
            <div className="flex items-center space-x-2 mb-4">
              <Filter className="w-5 h-5 text-gray-600" />
              <h2 className="text-lg font-semibold text-gray-900">Filtres de recherche</h2>
            </div>
            
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
              {/* Recherche textuelle */}
              <div>
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
                    className="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                  />
                </div>
              </div>

              {/* Filtre par statut */}
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Statut
                </label>
                <select
                  value={filterStatus}
                  onChange={(e) => setFilterStatus(e.target.value)}
                  className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                >
                  <option value="Tous">Tous</option>
                  <option value="En attente">En attente</option>
                  <option value="Acceptée">Acceptée</option>
                  <option value="Refusée">Refusée</option>
                </select>
              </div>

              {/* Filtre par type */}
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Type de document
                </label>
                <select
                  value={filterType}
                  onChange={(e) => setFilterType(e.target.value)}
                  className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                >
                  {documentTypes.map((type) => (
                    <option key={type} value={type}>{type}</option>
                  ))}
                </select>
              </div>

              {/* Filtre par date */}
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Date
                </label>
                <input
                  type="date"
                  value={filterDate}
                  onChange={(e) => setFilterDate(e.target.value)}
                  className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                />
              </div>
            </div>

            {/* Bouton réinitialiser */}
            <div className="mt-4 flex justify-end">
              <button
                onClick={() => {
                  setSearchTerm('')
                  setFilterStatus('Tous')
                  setFilterType('Tous')
                  setFilterDate('')
                }}
                className="px-4 py-2 text-sm text-gray-600 hover:text-gray-900 underline"
              >
                Réinitialiser les filtres
              </button>
            </div>
          </div>

          {/* Résultats */}
          <div className="bg-white rounded-2xl shadow-lg overflow-hidden">
            <div className="px-6 py-4 bg-gray-50 border-b border-gray-200">
              <p className="text-sm text-gray-600">
                <span className="font-semibold">{filteredDemandes.length}</span> demande(s) trouvée(s)
              </p>
            </div>
            
            <div className="overflow-x-auto">
              <table className="w-full">
                <thead className="bg-gray-50">
                  <tr>
                    <th className="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase">ID</th>
                    <th className="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase">Étudiant</th>
                    <th className="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase">Type</th>
                    <th className="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase">Date</th>
                    <th className="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase">Statut</th>
                    <th className="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase">Justification</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-gray-200">
                  {filteredDemandes.length === 0 ? (
                    <tr>
                      <td colSpan={6} className="px-6 py-12 text-center text-gray-500">
                        Aucune demande trouvée avec ces critères
                      </td>
                    </tr>
                  ) : (
                    filteredDemandes.map((demande, index) => (
                      <motion.tr
                        key={demande.id}
                        initial={{ opacity: 0, x: -20 }}
                        animate={{ opacity: 1, x: 0 }}
                        transition={{ delay: index * 0.02 }}
                        className="hover:bg-gray-50 transition-colors"
                      >
                        <td className="px-6 py-4 text-sm font-medium text-gray-900">#{demande.id}</td>
                        <td className="px-6 py-4 text-sm text-gray-700">
                          {demande.nom} {demande.prenom}
                        </td>
                        <td className="px-6 py-4 text-sm text-gray-700">{demande.document_type}</td>
                        <td className="px-6 py-4 text-sm text-gray-600">
                          {new Date(demande.date_demande).toLocaleDateString('fr-FR', {
                            year: 'numeric',
                            month: 'long',
                            day: 'numeric',
                            hour: '2-digit',
                            minute: '2-digit'
                          })}
                        </td>
                        <td className="px-6 py-4">{getStatusBadge(demande.status)}</td>
                        <td className="px-6 py-4 text-sm text-gray-600">
                          {demande.justification_refus ? (
                            <span className="text-red-600 italic">{demande.justification_refus}</span>
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
        </motion.div>
      </main>
    </div>
  )
}

