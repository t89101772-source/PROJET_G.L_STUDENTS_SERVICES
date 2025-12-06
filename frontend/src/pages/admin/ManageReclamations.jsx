import { useState } from 'react'
import { motion } from 'framer-motion'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { MessageSquare, CheckCircle, XCircle } from 'lucide-react'
import Sidebar from '../../components/Layout/Sidebar'
import { useAuth } from '../../context/AuthContext'
import { reclamationService } from '../../services/api'
import LoadingPage from '../../components/LoadingPage'

export default function ManageReclamations() {
  const { user } = useAuth()
  const queryClient = useQueryClient()
  const [selectedReclamation, setSelectedReclamation] = useState(null)
  const [reponse, setReponse] = useState('')

  const { data: reclamations, isLoading } = useQuery({
    queryKey: ['reclamations'],
    queryFn: () => reclamationService.getAll(),
  })

  const respondMutation = useMutation({
    mutationFn: ({ id, reponse }) => reclamationService.respond(id, reponse),
    onSuccess: () => {
      queryClient.invalidateQueries(['reclamations'])
      queryClient.invalidateQueries(['adminStats'])
      setSelectedReclamation(null)
      setReponse('')
    },
    onError: (error) => {
      console.error('Error responding to reclamation:', error)
      alert('Erreur lors de la réponse: ' + (error.response?.data?.error || error.message))
    },
  })

  const closeMutation = useMutation({
    mutationFn: (id) => reclamationService.close(id),
    onSuccess: () => {
      queryClient.invalidateQueries(['reclamations'])
      queryClient.invalidateQueries(['adminStats'])
    },
    onError: (error) => {
      console.error('Error closing reclamation:', error)
      alert('Erreur lors de la fermeture: ' + (error.response?.data?.error || error.message))
    },
  })

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
            <h1 className="text-4xl font-bold text-gray-900 mb-2">Gestion des Réclamations</h1>
            <p className="text-gray-600">Répondez aux réclamations des étudiants</p>
          </div>

          <div className="space-y-4">
            {reclamations?.map((reclamation, index) => (
              <motion.div
                key={reclamation.id}
                initial={{ opacity: 0, y: 20 }}
                animate={{ opacity: 1, y: 0 }}
                transition={{ delay: index * 0.05 }}
                className="bg-white rounded-2xl shadow-lg p-6"
              >
                <div className="flex items-start justify-between mb-4">
                  <div>
                    <div className="flex items-center space-x-3 mb-2">
                      <h3 className="text-lg font-bold text-gray-900">Réclamation #{reclamation.id}</h3>
                      <span className={`px-3 py-1 rounded-full text-xs font-medium ${
                        reclamation.status === 'Fermée' 
                          ? 'bg-gray-100 text-gray-700' 
                          : reclamation.status === 'En cours'
                          ? 'bg-blue-100 text-blue-700'
                          : 'bg-orange-100 text-orange-700'
                      }`}>
                        {reclamation.status}
                      </span>
                    </div>
                    <p className="text-sm text-gray-600">
                      <strong>Étudiant:</strong> {reclamation.nom} {reclamation.prenom}
                    </p>
                    <p className="text-sm text-gray-600">
                      <strong>Document:</strong> {reclamation.document_type}
                    </p>
                    <p className="text-sm text-gray-600">
                      <strong>Motif:</strong> {reclamation.motif}
                    </p>
                  </div>
                </div>

                <div className="bg-gray-50 rounded-xl p-4 mb-4">
                  <p className="text-gray-700">{reclamation.description}</p>
                </div>

                {reclamation.reponse_admin && (
                  <div className="bg-primary-50 rounded-xl p-4 mb-4">
                    <p className="text-sm font-semibold text-primary-900 mb-1">Réponse Admin:</p>
                    <p className="text-primary-700">{reclamation.reponse_admin}</p>
                  </div>
                )}

                {reclamation.status !== 'Fermée' && (
                  <div className="flex space-x-3">
                    <motion.button
                      whileHover={{ scale: 1.02 }}
                      whileTap={{ scale: 0.98 }}
                      onClick={() => setSelectedReclamation(reclamation)}
                      className="px-4 py-2 bg-primary-500 text-white rounded-xl text-sm font-semibold hover:bg-primary-600"
                    >
                      Répondre
                    </motion.button>
                    <motion.button
                      whileHover={{ scale: 1.02 }}
                      whileTap={{ scale: 0.98 }}
                      onClick={() => closeMutation.mutate(reclamation.id)}
                      disabled={closeMutation.isPending || respondMutation.isPending}
                      className="px-4 py-2 bg-gray-500 text-white rounded-xl text-sm font-semibold hover:bg-gray-600 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                      {closeMutation.isPending ? '...' : 'Fermer'}
                    </motion.button>
                  </div>
                )}
              </motion.div>
            ))}
          </div>

          {/* Modal Réponse */}
          {selectedReclamation && (
            <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
              <motion.div
                initial={{ scale: 0.9, opacity: 0 }}
                animate={{ scale: 1, opacity: 1 }}
                className="bg-white rounded-2xl p-6 max-w-md w-full"
              >
                <h3 className="text-xl font-bold text-gray-900 mb-4">Répondre à la réclamation</h3>
                <textarea
                  value={reponse}
                  onChange={(e) => setReponse(e.target.value)}
                  placeholder="Votre réponse..."
                  rows={5}
                  className="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-transparent mb-4"
                />
                <div className="flex space-x-4">
                  <button
                    onClick={() => setSelectedReclamation(null)}
                    className="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-xl font-semibold hover:bg-gray-50"
                  >
                    Annuler
                  </button>
                  <button
                    onClick={() => respondMutation.mutate({ id: selectedReclamation.id, reponse })}
                    disabled={respondMutation.isPending || !reponse.trim()}
                    className="flex-1 px-4 py-2 bg-primary-500 text-white rounded-xl font-semibold hover:bg-primary-600 disabled:opacity-50 disabled:cursor-not-allowed"
                  >
                    {respondMutation.isPending ? 'Envoi...' : 'Envoyer'}
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

