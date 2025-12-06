import { useState } from 'react'
import { motion } from 'framer-motion'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { CheckCircle, XCircle, Clock, AlertCircle, FileDown, FileText } from 'lucide-react'
import Sidebar from '../../components/Layout/Sidebar'
import { useAuth } from '../../context/AuthContext'
import { demandeService } from '../../services/api'
import LoadingPage from '../../components/LoadingPage'

export default function ManageDemandes() {
  const { user } = useAuth()
  const queryClient = useQueryClient()
  const [selectedDemande, setSelectedDemande] = useState(null)
  const [justification, setJustification] = useState('')
  const [selectedType, setSelectedType] = useState('Tous') // Filtre par type de document
  const [generatingDocId, setGeneratingDocId] = useState(null) // ID de la demande en cours de génération

  const { data: demandes, isLoading } = useQuery({
    queryKey: ['demandes'],
    queryFn: () => demandeService.getAll(),
  })

  // Grouper les demandes par type
  const demandesByType = demandes?.reduce((acc, demande) => {
    const type = demande.document_type || 'Autre'
    if (!acc[type]) {
      acc[type] = []
    }
    acc[type].push(demande)
    return acc
  }, {}) || {}

  // Filtrer selon le type sélectionné
  const filteredDemandes = selectedType === 'Tous' 
    ? demandes 
    : demandes?.filter(d => d.document_type === selectedType)

  // Types de documents disponibles
  const documentTypes = ['Tous', ...Object.keys(demandesByType)]

  const updateMutation = useMutation({
    mutationFn: ({ id, status, justification }) => 
      demandeService.updateStatus(id, status, justification),
    onSuccess: () => {
      queryClient.invalidateQueries(['demandes'])
      queryClient.invalidateQueries(['adminStats'])
      setSelectedDemande(null)
      setJustification('')
    },
    onError: (error) => {
      console.error('Error updating demande:', error)
      alert('Erreur lors de la mise à jour: ' + (error.response?.data?.error || error.message))
    },
  })

  const generateDocumentMutation = useMutation({
    mutationFn: (demandeId) => demandeService.generateDocument(demandeId),
    onSuccess: (data, demandeId) => {
      queryClient.invalidateQueries(['demandes'])
      setGeneratingDocId(null) // Réinitialiser l'état de génération
      alert('Document généré avec succès !')
      // Optionnel : télécharger automatiquement
      if (data.download_url) {
        window.open(data.download_url, '_blank')
      }
    },
    onError: (error, demandeId) => {
      console.error('Error generating document:', error)
      setGeneratingDocId(null) // Réinitialiser l'état même en cas d'erreur
      alert('Erreur lors de la génération: ' + (error.response?.data?.error || error.message))
    },
  })

  const handleGenerateDocument = (demandeId) => {
    setGeneratingDocId(demandeId) // Marquer cette demande comme en cours de génération
    generateDocumentMutation.mutate(demandeId)
  }

  const handleAction = (demande, action) => {
    if (action === 'refuse') {
      setSelectedDemande(demande)
    } else {
      updateMutation.mutate({ id: demande.id, status: action === 'accept' ? 'Acceptée' : 'Refusée' })
    }
  }

  const handleRefuse = () => {
    if (!justification.trim()) {
      alert('Veuillez fournir une justification')
      return
    }
    updateMutation.mutate({
      id: selectedDemande.id,
      status: 'Refusée',
      justification: justification,
    })
  }

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
            <h1 className="text-4xl font-bold text-gray-900 mb-2">Gestion des Demandes</h1>
            <p className="text-gray-600">Traitez les demandes des étudiants</p>
          </div>

          {/* Filtre par type de document */}
          <div className="mb-6 bg-white rounded-xl shadow-md p-4">
            <div className="flex items-center space-x-4">
              <label className="text-sm font-medium text-gray-700">Filtrer par type :</label>
              <select
                value={selectedType}
                onChange={(e) => setSelectedType(e.target.value)}
                className="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
              >
                {documentTypes.map((type) => (
                  <option key={type} value={type}>
                    {type} {type !== 'Tous' && `(${demandesByType[type]?.length || 0})`}
                  </option>
                ))}
              </select>
              <div className="flex-1"></div>
              <div className="text-sm text-gray-600">
                Total: <span className="font-semibold">{filteredDemandes?.length || 0}</span> demande(s)
              </div>
            </div>
          </div>

          <div className="bg-white rounded-2xl shadow-lg overflow-hidden">
            <div className="overflow-x-auto">
              <table className="w-full">
                <thead className="bg-gray-50">
                  <tr>
                    <th className="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase">ID</th>
                    <th className="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase">Étudiant</th>
                    <th className="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase">Type</th>
                    <th className="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase">Date</th>
                    <th className="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase">Statut</th>
                    <th className="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase">Actions</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-gray-200">
                  {filteredDemandes?.map((demande, index) => (
                    <motion.tr
                      key={demande.id}
                      initial={{ opacity: 0, x: -20 }}
                      animate={{ opacity: 1, x: 0 }}
                      transition={{ delay: index * 0.05 }}
                      className="hover:bg-gray-50 transition-colors"
                    >
                      <td className="px-6 py-4 text-sm font-medium text-gray-900">#{demande.id}</td>
                      <td className="px-6 py-4 text-sm text-gray-700">
                        {demande.nom} {demande.prenom}
                      </td>
                      <td className="px-6 py-4 text-sm text-gray-700">{demande.document_type}</td>
                      <td className="px-6 py-4 text-sm text-gray-600">
                        {new Date(demande.date_demande).toLocaleDateString('fr-FR')}
                      </td>
                      <td className="px-6 py-4">{getStatusBadge(demande.status)}</td>
                      <td className="px-6 py-4">
                        <div className="flex space-x-2 flex-wrap">
                          {demande.status === 'En attente' && (
                            <>
                              <motion.button
                                whileHover={{ scale: 1.05 }}
                                whileTap={{ scale: 0.95 }}
                                onClick={() => handleAction(demande, 'accept')}
                                disabled={updateMutation.isPending}
                                className="px-3 py-1 bg-success-500 text-white rounded-lg text-xs font-medium hover:bg-success-600 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                              >
                                {updateMutation.isPending ? '...' : 'Valider'}
                              </motion.button>
                              <motion.button
                                whileHover={{ scale: 1.05 }}
                                whileTap={{ scale: 0.95 }}
                                onClick={() => handleAction(demande, 'refuse')}
                                disabled={updateMutation.isPending}
                                className="px-3 py-1 bg-red-500 text-white rounded-lg text-xs font-medium hover:bg-red-600 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                              >
                                Refuser
                              </motion.button>
                            </>
                          )}
                          {demande.status === 'Acceptée' && (
                            <>
                              {!demande.document_path ? (
                                <motion.button
                                  whileHover={{ scale: 1.05 }}
                                  whileTap={{ scale: 0.95 }}
                                  onClick={() => handleGenerateDocument(demande.id)}
                                  disabled={generatingDocId === demande.id || (generatingDocId !== null && generatingDocId !== demande.id)}
                                  className="px-3 py-1 bg-primary-500 text-white rounded-lg text-xs font-medium hover:bg-primary-600 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center space-x-1"
                                  title="Générer le document PDF"
                                >
                                  <FileText className="w-3 h-3" />
                                  <span>{generatingDocId === demande.id ? 'Génération...' : 'Générer PDF'}</span>
                                </motion.button>
                              ) : (
                                <motion.button
                                  whileHover={{ scale: 1.05 }}
                                  whileTap={{ scale: 0.95 }}
                                  onClick={() => {
                                    demandeService.downloadDocument(demande.id)
                                  }}
                                  className="px-3 py-1 bg-blue-500 text-white rounded-lg text-xs font-medium hover:bg-blue-600 transition-colors flex items-center space-x-1"
                                  title="Télécharger le document"
                                >
                                  <FileDown className="w-3 h-3" />
                                  <span>Télécharger</span>
                                </motion.button>
                              )}
                            </>
                          )}
                          {demande.status === 'Refusée' && (
                            <span className="text-xs text-gray-500 italic">
                              ✗ Refusée
                            </span>
                          )}
                        </div>
                      </td>
                    </motion.tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>

          {/* Modal Refus */}
          {selectedDemande && (
            <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
              <motion.div
                initial={{ scale: 0.9, opacity: 0 }}
                animate={{ scale: 1, opacity: 1 }}
                className="bg-white rounded-2xl p-6 max-w-md w-full"
              >
                <h3 className="text-xl font-bold text-gray-900 mb-4">Refuser la demande</h3>
                <p className="text-gray-600 mb-4">
                  Demande #{selectedDemande.id} - {selectedDemande.document_type}
                </p>
                <textarea
                  value={justification}
                  onChange={(e) => setJustification(e.target.value)}
                  placeholder="Justification du refus..."
                  rows={4}
                  className="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-transparent mb-4"
                />
                <div className="flex space-x-4">
                  <button
                    onClick={() => setSelectedDemande(null)}
                    className="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-xl font-semibold hover:bg-gray-50"
                  >
                    Annuler
                  </button>
                  <button
                    onClick={handleRefuse}
                    disabled={updateMutation.isPending}
                    className="flex-1 px-4 py-2 bg-red-500 text-white rounded-xl font-semibold hover:bg-red-600 disabled:opacity-50 disabled:cursor-not-allowed"
                  >
                    {updateMutation.isPending ? 'Traitement...' : 'Confirmer'}
                  </button>
                </div>
              </motion.div>
            </div>
          )}
        </motion.div>
      </main>
    </div>
  )
}

