import { motion } from 'framer-motion'
import { useNavigate } from 'react-router-dom'
import { GraduationCap, User, Shield, ArrowRight, FileText, MessageSquare } from 'lucide-react'

export default function HomePage() {
  const navigate = useNavigate()

  return (
    <div className="min-h-screen bg-gradient-to-br from-primary-600 via-primary-700 to-primary-800 relative overflow-hidden">
      {/* Animated Background */}
      <div className="absolute inset-0">
        <div className="absolute top-0 left-0 w-96 h-96 bg-primary-400 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-float"></div>
        <div className="absolute top-0 right-0 w-96 h-96 bg-purple-400 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-float" style={{ animationDelay: '2s' }}></div>
        <div className="absolute bottom-0 left-1/2 w-96 h-96 bg-pink-400 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-float" style={{ animationDelay: '4s' }}></div>
      </div>

      <div className="relative z-10 flex items-center justify-center min-h-screen px-4">
        <div className="max-w-4xl w-full text-center">
          {/* Logo */}
          <motion.div
            initial={{ scale: 0 }}
            animate={{ scale: 1 }}
            transition={{ type: 'spring', duration: 0.8 }}
            className="mb-8"
          >
            <div className="w-32 h-32 mx-auto bg-white rounded-3xl shadow-2xl flex items-center justify-center">
              <GraduationCap className="w-16 h-16 text-primary-600" />
            </div>
          </motion.div>

          {/* Title */}
          <motion.h1
            initial={{ opacity: 0, y: 30 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ delay: 0.2 }}
            className="text-6xl md:text-7xl font-bold text-white mb-6"
          >
            Bienvenue
          </motion.h1>

          <motion.p
            initial={{ opacity: 0, y: 30 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ delay: 0.4 }}
            className="text-xl md:text-2xl text-primary-100 mb-12 max-w-2xl mx-auto"
          >
            Système de gestion des demandes de documents et réclamations pour les étudiants
          </motion.p>

          {/* Buttons */}
          <motion.div
            initial={{ opacity: 0, y: 30 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ delay: 0.6 }}
            className="flex flex-col sm:flex-row gap-6 justify-center items-center"
          >
            <motion.button
              whileHover={{ scale: 1.05, y: -5 }}
              whileTap={{ scale: 0.95 }}
              onClick={() => navigate('/student/login')}
              className="group relative px-8 py-4 bg-white text-primary-700 rounded-2xl font-semibold text-lg shadow-2xl overflow-hidden w-full sm:w-auto min-w-[280px]"
            >
              <span className="relative z-10 flex items-center justify-center space-x-3">
                <User className="w-6 h-6" />
                <span>Espace Étudiant</span>
                <ArrowRight className="w-5 h-5 group-hover:translate-x-1 transition-transform" />
              </span>
              <motion.div
                className="absolute inset-0 bg-gradient-to-r from-success-500 to-success-600"
                initial={{ x: '-100%' }}
                whileHover={{ x: 0 }}
                transition={{ duration: 0.3 }}
              />
            </motion.button>

            <motion.button
              whileHover={{ scale: 1.05, y: -5 }}
              whileTap={{ scale: 0.95 }}
              onClick={() => navigate('/admin/login')}
              className="group relative px-8 py-4 bg-white/10 backdrop-blur-lg text-white border-2 border-white/30 rounded-2xl font-semibold text-lg shadow-2xl overflow-hidden w-full sm:w-auto min-w-[280px] hover:bg-white/20"
            >
              <span className="relative z-10 flex items-center justify-center space-x-3">
                <Shield className="w-6 h-6" />
                <span>Espace Administrateur</span>
                <ArrowRight className="w-5 h-5 group-hover:translate-x-1 transition-transform" />
              </span>
            </motion.button>
          </motion.div>

          {/* Features */}
          <motion.div
            initial={{ opacity: 0, y: 30 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ delay: 0.8 }}
            className="mt-16 grid grid-cols-1 md:grid-cols-3 gap-6"
          >
            {[
              { icon: FileText, title: 'Gestion de Demandes', desc: 'Créez et suivez vos demandes de documents' },
              { icon: MessageSquare, title: 'Réclamations', desc: 'Exprimez vos préoccupations facilement' },
              { icon: Shield, title: 'Sécurisé', desc: 'Vos données sont protégées' },
            ].map((feature, index) => {
              const Icon = feature.icon
              return (
                <motion.div
                  key={index}
                  initial={{ opacity: 0, y: 20 }}
                  animate={{ opacity: 1, y: 0 }}
                  transition={{ delay: 0.8 + index * 0.1 }}
                  whileHover={{ y: -5 }}
                  className="bg-white/10 backdrop-blur-lg rounded-2xl p-6 border border-white/20"
                >
                  <Icon className="w-8 h-8 text-white mb-3" />
                  <h3 className="text-white font-semibold mb-2">{feature.title}</h3>
                  <p className="text-primary-100 text-sm">{feature.desc}</p>
                </motion.div>
              )
            })}
          </motion.div>
        </div>
      </div>
    </div>
  )
}

