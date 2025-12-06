import { motion } from 'framer-motion'
import { useNavigate, useLocation } from 'react-router-dom'
import { 
  LayoutDashboard, 
  FileText, 
  MessageSquare, 
  LogOut,
  User,
  GraduationCap,
  History
} from 'lucide-react'
import { useAuth } from '../../context/AuthContext'

const menuItems = {
  student: [
    { icon: LayoutDashboard, label: 'Tableau de Bord', path: '/student/dashboard' },
    { icon: FileText, label: 'Nouvelle Demande', path: '/student/request' },
    { icon: MessageSquare, label: 'Nouvelle Réclamation', path: '/student/reclamation' },
    { icon: FileText, label: 'Mes Documents', path: '/student/documents' },
  ],
  admin: [
    { icon: LayoutDashboard, label: 'Tableau de Bord', path: '/admin/dashboard' },
    { icon: FileText, label: 'Gestion Demandes', path: '/admin/demandes' },
    { icon: MessageSquare, label: 'Gestion Réclamations', path: '/admin/reclamations' },
    { icon: History, label: 'Historique', path: '/admin/history' },
  ],
}

export default function Sidebar({ user }) {
  const navigate = useNavigate()
  const location = useLocation()
  const { logout } = useAuth()
  const role = user?.role || 'student'
  const items = menuItems[role] || []

  const handleLogout = () => {
    logout()
    navigate(role === 'student' ? '/student/login' : '/admin/login')
  }

  return (
    <motion.aside
      initial={{ x: -300 }}
      animate={{ x: 0 }}
      transition={{ type: 'spring', damping: 25 }}
      className="fixed left-0 top-0 h-full w-64 bg-white shadow-xl z-40"
    >
      <div className="flex flex-col h-full">
        {/* Header */}
        <div className="p-6 border-b border-gray-200">
          <div className="flex items-center space-x-3">
            <div className="w-12 h-12 rounded-xl gradient-primary flex items-center justify-center">
              <GraduationCap className="w-6 h-6 text-white" />
            </div>
            <div>
              <h2 className="font-bold text-gray-900">
                {role === 'student' ? 'Espace Étudiant' : 'Admin Panel'}
              </h2>
              <p className="text-sm text-gray-500">
                {user?.prenom || user?.login || 'Utilisateur'}
              </p>
            </div>
          </div>
        </div>

        {/* Menu */}
        <nav className="flex-1 p-4 space-y-2">
          {items.map((item, index) => {
            const Icon = item.icon
            const isActive = location.pathname === item.path
            
            return (
              <motion.button
                key={item.path}
                initial={{ opacity: 0, x: -20 }}
                animate={{ opacity: 1, x: 0 }}
                transition={{ delay: index * 0.1 }}
                onClick={() => navigate(item.path)}
                className={`w-full flex items-center space-x-3 px-4 py-3 rounded-xl transition-all ${
                  isActive
                    ? 'bg-primary-50 text-primary-700 shadow-sm'
                    : 'text-gray-700 hover:bg-gray-50'
                }`}
              >
                <Icon className="w-5 h-5" />
                <span className="font-medium">{item.label}</span>
              </motion.button>
            )
          })}
        </nav>

        {/* Logout */}
        <div className="p-4 border-t border-gray-200">
          <motion.button
            whileHover={{ scale: 1.02 }}
            whileTap={{ scale: 0.98 }}
            onClick={handleLogout}
            className="w-full flex items-center space-x-3 px-4 py-3 rounded-xl text-red-600 hover:bg-red-50 transition-colors"
          >
            <LogOut className="w-5 h-5" />
            <span className="font-medium">Déconnexion</span>
          </motion.button>
        </div>
      </div>
    </motion.aside>
  )
}

