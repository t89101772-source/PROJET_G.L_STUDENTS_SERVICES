import { motion } from 'framer-motion'
import { useQuery } from '@tanstack/react-query'
import { useNavigate } from 'react-router-dom'
import { FileText, Clock, CheckCircle, MessageSquare, TrendingUp } from 'lucide-react'
import Sidebar from '../../components/Layout/Sidebar'
import StatCard from '../../components/Layout/StatCard'
import { useAuth } from '../../context/AuthContext'
import { statsService } from '../../services/api'
import LoadingPage from '../../components/LoadingPage'

export default function AdminDashboard() {
  const { user } = useAuth()
  const navigate = useNavigate()

  const { data: stats, isLoading } = useQuery({
    queryKey: ['adminStats'],
    queryFn: () => statsService.getAdminStats(),
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
          {/* Header */}
          <div className="mb-8">
            <h1 className="text-4xl font-bold text-gray-900 mb-2">
              Tableau de Bord Administrateur
            </h1>
            <p className="text-gray-600">Vue d'ensemble de l'activité</p>
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

          {/* Quick Actions */}
          <motion.div
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ delay: 0.5 }}
            className="bg-white rounded-2xl shadow-lg p-6"
          >
            <h2 className="text-2xl font-bold text-gray-900 mb-4">Actions Rapides</h2>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <motion.button
                whileHover={{ scale: 1.02 }}
                whileTap={{ scale: 0.98 }}
                onClick={() => navigate('/admin/demandes')}
                className="p-6 bg-gradient-to-r from-primary-500 to-primary-600 text-white rounded-xl text-left hover:shadow-lg transition-all cursor-pointer"
              >
                <FileText className="w-8 h-8 mb-3" />
                <h3 className="font-semibold text-lg mb-1">Gérer les Demandes</h3>
                <p className="text-primary-100 text-sm">Traiter les demandes en attente</p>
              </motion.button>
              
              <motion.button
                whileHover={{ scale: 1.02 }}
                whileTap={{ scale: 0.98 }}
                onClick={() => navigate('/admin/reclamations')}
                className="p-6 bg-gradient-to-r from-success-500 to-success-600 text-white rounded-xl text-left hover:shadow-lg transition-all cursor-pointer"
              >
                <MessageSquare className="w-8 h-8 mb-3" />
                <h3 className="font-semibold text-lg mb-1">Gérer les Réclamations</h3>
                <p className="text-success-100 text-sm">Répondre aux réclamations</p>
              </motion.button>
            </div>
          </motion.div>
        </motion.div>
      </main>
    </div>
  )
}

