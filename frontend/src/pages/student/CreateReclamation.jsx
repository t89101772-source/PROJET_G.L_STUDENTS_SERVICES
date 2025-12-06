import { useState, useEffect } from 'react'
import { motion } from 'framer-motion'
import { useNavigate } from 'react-router-dom'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { MessageSquare, CheckCircle, AlertCircle } from 'lucide-react'
import Sidebar from '../../components/Layout/Sidebar'
import { useAuth } from '../../context/AuthContext'
import { reclamationService, demandeService } from '../../services/api'

export default function CreateReclamation() {
  const { user } = useAuth()
  const navigate = useNavigate()
  const queryClient = useQueryClient()
  const [formData, setFormData] = useState({ demande_id: '', motif: '', description: '' })
  const [success, setSuccess] = useState(false)

  const { data: demandes } = useQuery({
    queryKey: ['studentDemandes', user?.apogeeNumber],
    queryFn: () => demandeService.getByStudent(user?.apogeeNumber),
    enabled: !!user?.apogeeNumber,
  })

  const mutation = useMutation({
    mutationFn: (data) => reclamationService.create(data),
    onSuccess: () => {
      setSuccess(true)
      queryClient.invalidateQueries(['studentReclamations'])
      setTimeout(() => {
        navigate('/student/dashboard')
      }, 2000)
    },
  })

  const handleSubmit = (e) => {
    e.preventDefault()
    mutation.mutate({
      demande_id: parseInt(formData.demande_id),
      motif: formData.motif,
      description: formData.description,
    })
  }

  return (
    <div className="flex min-h-screen bg-gray-50">
      <Sidebar user={user} />
      
      <main className="flex-1 ml-64 p-8">
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          className="max-w-2xl mx-auto"
        >
          <div className="bg-white rounded-2xl shadow-lg p-8">
            <div className="mb-8">
              <h1 className="text-3xl font-bold text-gray-900 mb-2">Nouvelle Réclamation</h1>
              <p className="text-gray-600">Créez une réclamation liée à une de vos demandes</p>
            </div>

            {success ? (
              <motion.div
                initial={{ scale: 0.8, opacity: 0 }}
                animate={{ scale: 1, opacity: 1 }}
                className="text-center py-12"
              >
                <CheckCircle className="w-20 h-20 text-success-500 mx-auto mb-4" />
                <h2 className="text-2xl font-bold text-gray-900 mb-2">Réclamation créée avec succès !</h2>
                <p className="text-gray-600">Redirection en cours...</p>
              </motion.div>
            ) : (
              <form onSubmit={handleSubmit} className="space-y-6">
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-2">
                    Demande concernée
                  </label>
                  <select
                    value={formData.demande_id}
                    onChange={(e) => setFormData({ ...formData, demande_id: e.target.value })}
                    className="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-success-500 focus:border-transparent transition-all"
                    required
                  >
                    <option value="">Sélectionnez une demande</option>
                    {demandes?.map((demande) => (
                      <option key={demande.id} value={demande.id}>
                        {demande.document_type} - {demande.status}
                      </option>
                    ))}
                  </select>
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-2">
                    Motif
                  </label>
                  <input
                    type="text"
                    value={formData.motif}
                    onChange={(e) => setFormData({ ...formData, motif: e.target.value })}
                    className="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-success-500 focus:border-transparent transition-all"
                    placeholder="Ex: Retard de traitement"
                    required
                  />
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-2">
                    Description
                  </label>
                  <textarea
                    value={formData.description}
                    onChange={(e) => setFormData({ ...formData, description: e.target.value })}
                    rows={5}
                    className="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-success-500 focus:border-transparent transition-all resize-none"
                    placeholder="Décrivez votre réclamation en détail..."
                    required
                  />
                </div>

                {mutation.isError && (
                  <motion.div
                    initial={{ opacity: 0, y: -10 }}
                    animate={{ opacity: 1, y: 0 }}
                    className="p-4 bg-red-50 border border-red-200 rounded-xl flex items-center space-x-3"
                  >
                    <AlertCircle className="w-5 h-5 text-red-600" />
                    <p className="text-red-600 text-sm">
                      {mutation.error?.response?.data?.message || 'Erreur lors de la création'}
                    </p>
                  </motion.div>
                )}

                <div className="flex space-x-4">
                  <motion.button
                    whileHover={{ scale: 1.02 }}
                    whileTap={{ scale: 0.98 }}
                    type="button"
                    onClick={() => navigate('/student/dashboard')}
                    className="flex-1 px-6 py-3 border border-gray-300 text-gray-700 rounded-xl font-semibold hover:bg-gray-50 transition-all"
                  >
                    Annuler
                  </motion.button>
                  <motion.button
                    whileHover={{ scale: 1.02 }}
                    whileTap={{ scale: 0.98 }}
                    type="submit"
                    disabled={mutation.isPending}
                    className="flex-1 px-6 py-3 bg-gradient-to-r from-success-500 to-success-600 text-white rounded-xl font-semibold shadow-lg hover:shadow-xl transition-all disabled:opacity-50"
                  >
                    {mutation.isPending ? 'Création...' : 'Créer la réclamation'}
                  </motion.button>
                </div>
              </form>
            )}
          </div>
        </motion.div>
      </main>
    </div>
  )
}

