import { useEffect, useState } from 'react'
import { motion } from 'framer-motion'
import { useQuery } from '@tanstack/react-query'
import { FileText, Clock, CheckCircle, XCircle, MessageSquare } from 'lucide-react'
import Sidebar from '../../components/Layout/Sidebar'
import StatCard from '../../components/Layout/StatCard'
import { useAuth } from '../../context/AuthContext'
import { demandeService, reclamationService, statsService } from '../../services/api'
import LoadingPage from '../../components/LoadingPage'

export default function StudentDashboard() {
  const { user } = useAuth()
  const [loading, setLoading] = useState(true)

  const { data: stats, isLoading: statsLoading } = useQuery({
    queryKey: ['studentStats', user?.apogeeNumber],
    queryFn: () => statsService.getStudentStats(user?.apogeeNumber),
    enabled: !!user?.apogeeNumber,
  })

  const { data: demandes } = useQuery({
    queryKey: ['studentDemandes', user?.apogeeNumber],
    queryFn: () => demandeService.getByStudent(user?.apogeeNumber),
    enabled: !!user?.apogeeNumber,
  })

  const { data: reclamations } = useQuery({
    queryKey: ['studentReclamations', user?.apogeeNumber],
    queryFn: () => reclamationService.getByStudent(user?.apogeeNumber),
    enabled: !!user?.apogeeNumber,
  })

  useEffect(() => {
    if (stats) setLoading(false)
  }, [stats])

  if (loading || statsLoading) return <LoadingPage />

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

  return (
    <div className="flex min-h-screen bg-gray-50">
      <Sidebar user={user} />
      
      <main className="flex-1 ml-64 p-8">
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          className="max-w-7xl mx-auto"
        >
          {/* Header */}
          <div className="mb-8">
            <h1 className="text-4xl font-bold text-gray-900 mb-2">
              Bienvenue, {user?.prenom} {user?.nom}
            </h1>
            <p className="text-gray-600">Numéro Apogée : {user?.apogeeNumber}</p>
          </div>

          {/* Stats Cards */}
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
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
              title="Acceptées"
              value={stats?.demandes_acceptees || 0}
              icon={CheckCircle}
              color="success"
              delay={0.3}
            />
            <StatCard
              title="Réclamations"
              value={stats?.total_reclamations || 0}
              icon={MessageSquare}
              color="primary"
              delay={0.4}
            />
          </div>

          {/* Demandes Récentes */}
          <motion.div
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ delay: 0.5 }}
            className="bg-white rounded-2xl shadow-lg p-6 mb-6"
          >
            <h2 className="text-2xl font-bold text-gray-900 mb-6">Mes Demandes Récentes</h2>
            <div className="overflow-x-auto">
              <table className="w-full">
                <thead>
                  <tr className="border-b border-gray-200">
                    <th className="text-left py-3 px-4 text-sm font-semibold text-gray-700">Type</th>
                    <th className="text-left py-3 px-4 text-sm font-semibold text-gray-700">Date</th>
                    <th className="text-left py-3 px-4 text-sm font-semibold text-gray-700">Statut</th>
                  </tr>
                </thead>
                <tbody>
                  {demandes?.slice(0, 5).map((demande, index) => (
                    <motion.tr
                      key={demande.id}
                      initial={{ opacity: 0, x: -20 }}
                      animate={{ opacity: 1, x: 0 }}
                      transition={{ delay: 0.6 + index * 0.1 }}
                      className="border-b border-gray-100 hover:bg-gray-50 transition-colors"
                    >
                      <td className="py-4 px-4">{demande.document_type}</td>
                      <td className="py-4 px-4 text-gray-600">
                        {new Date(demande.date_demande).toLocaleDateString('fr-FR')}
                      </td>
                      <td className="py-4 px-4">{getStatusBadge(demande.status)}</td>
                    </motion.tr>
                  ))}
                  {(!demandes || demandes.length === 0) && (
                    <tr>
                      <td colSpan="3" className="py-8 text-center text-gray-500">
                        Aucune demande pour le moment
                      </td>
                    </tr>
                  )}
                </tbody>
              </table>
            </div>
          </motion.div>

          {/* Réclamations Récentes */}
          <motion.div
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ delay: 0.7 }}
            className="bg-white rounded-2xl shadow-lg p-6"
          >
            <h2 className="text-2xl font-bold text-gray-900 mb-6">Mes Réclamations Récentes</h2>
            <div className="space-y-4">
              {reclamations?.slice(0, 3).map((reclamation, index) => (
                <motion.div
                  key={reclamation.id}
                  initial={{ opacity: 0, x: -20 }}
                  animate={{ opacity: 1, x: 0 }}
                  transition={{ delay: 0.8 + index * 0.1 }}
                  className="p-4 bg-gray-50 rounded-xl border border-gray-200"
                >
                  <div className="flex items-center justify-between mb-2">
                    <span className="font-semibold text-gray-900">{reclamation.motif}</span>
                    <span className={`px-3 py-1 rounded-full text-xs font-medium ${
                      reclamation.status === 'Fermée' 
                        ? 'bg-gray-100 text-gray-700' 
                        : 'bg-blue-100 text-blue-700'
                    }`}>
                      {reclamation.status}
                    </span>
                  </div>
                  <p className="text-sm text-gray-600">{reclamation.description}</p>
                </motion.div>
              ))}
              {(!reclamations || reclamations.length === 0) && (
                <p className="text-center text-gray-500 py-8">Aucune réclamation pour le moment</p>
              )}
            </div>
          </motion.div>
        </motion.div>
      </main>
    </div>
  )
}

