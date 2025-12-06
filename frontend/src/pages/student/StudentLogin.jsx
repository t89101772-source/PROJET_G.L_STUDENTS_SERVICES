import { useState } from 'react'
import { motion } from 'framer-motion'
import { useNavigate } from 'react-router-dom'
import { GraduationCap, User, Mail, ArrowRight, AlertCircle } from 'lucide-react'
import { useAuth } from '../../context/AuthContext'
import LoadingPage from '../../components/LoadingPage'

export default function StudentLogin() {
  const [formData, setFormData] = useState({ apogeeNumber: '', email: '' })
  const [error, setError] = useState('')
  const [loading, setLoading] = useState(false)
  const { login } = useAuth()
  const navigate = useNavigate()

  const handleSubmit = async (e) => {
    e.preventDefault()
    setError('')
    setLoading(true)

    try {
      const result = await login(
        { apogeeNumber: formData.apogeeNumber, email: formData.email },
        'student'
      )

      if (result.success) {
        navigate('/student/dashboard')
      } else {
        setError(result.error || 'Erreur de connexion')
      }
    } catch (err) {
      setError('Une erreur est survenue. Veuillez réessayer.')
    } finally {
      setLoading(false)
    }
  }

  if (loading) return <LoadingPage />

  return (
    <div className="min-h-screen bg-gradient-to-br from-success-50 via-white to-success-50 flex items-center justify-center p-4">
      <motion.div
        initial={{ opacity: 0, scale: 0.9 }}
        animate={{ opacity: 1, scale: 1 }}
        transition={{ duration: 0.5 }}
        className="w-full max-w-md"
      >
        {/* Card */}
        <div className="bg-white rounded-3xl shadow-2xl p-8">
          {/* Logo */}
          <div className="text-center mb-8">
            <motion.div
              initial={{ scale: 0 }}
              animate={{ scale: 1 }}
              transition={{ type: 'spring', delay: 0.2 }}
              className="w-20 h-20 mx-auto bg-gradient-to-br from-success-500 to-success-600 rounded-2xl flex items-center justify-center mb-4 shadow-lg"
            >
              <GraduationCap className="w-10 h-10 text-white" />
            </motion.div>
            <h1 className="text-3xl font-bold text-gray-900 mb-2">
              Espace Étudiant
            </h1>
            <p className="text-gray-600">Connectez-vous à votre compte</p>
          </div>

          {/* Error Message */}
          {error && (
            <motion.div
              initial={{ opacity: 0, y: -10 }}
              animate={{ opacity: 1, y: 0 }}
              className="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl flex items-center space-x-3"
            >
              <AlertCircle className="w-5 h-5 text-red-600" />
              <p className="text-red-600 text-sm">{error}</p>
            </motion.div>
          )}

          {/* Form */}
          <form onSubmit={handleSubmit} className="space-y-6">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">
                Numéro Apogée
              </label>
              <div className="relative">
                <User className="absolute left-4 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400" />
                <input
                  type="text"
                  value={formData.apogeeNumber}
                  onChange={(e) => setFormData({ ...formData, apogeeNumber: e.target.value })}
                  className="w-full pl-12 pr-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-success-500 focus:border-transparent transition-all"
                  placeholder="A12345"
                  required
                />
              </div>
            </div>

            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">
                Email Institutionnel
              </label>
              <div className="relative">
                <Mail className="absolute left-4 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400" />
                <input
                  type="email"
                  value={formData.email}
                  onChange={(e) => setFormData({ ...formData, email: e.target.value })}
                  className="w-full pl-12 pr-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-success-500 focus:border-transparent transition-all"
                  placeholder="prenom.nom@univ.ma"
                  required
                />
              </div>
            </div>

            <motion.button
              whileHover={{ scale: 1.02 }}
              whileTap={{ scale: 0.98 }}
              type="submit"
              disabled={loading}
              className="w-full py-3 bg-gradient-to-r from-success-500 to-success-600 text-white rounded-xl font-semibold shadow-lg hover:shadow-xl transition-all flex items-center justify-center space-x-2 disabled:opacity-50"
            >
              <span>Se connecter</span>
              <ArrowRight className="w-5 h-5" />
            </motion.button>
          </form>

          {/* Back to Home */}
          <div className="mt-6 text-center">
            <button
              onClick={() => navigate('/')}
              className="text-sm text-gray-600 hover:text-success-600 transition-colors"
            >
              ← Retour à l'accueil
            </button>
          </div>
        </div>
      </motion.div>
    </div>
  )
}

