import { motion } from 'framer-motion'
import { useNavigate } from 'react-router-dom'
import { GraduationCap, ShieldCheck, Sparkles, ArrowLeft } from 'lucide-react'

export default function AboutPage() {
  const navigate = useNavigate()

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Top bar */}
      <div className="bg-white border-b border-gray-200 sticky top-0 z-50">
        <div className="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
          <button
            onClick={() => navigate('/')}
            className="inline-flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-100 transition-colors"
          >
            <ArrowLeft className="w-4 h-4" />
            Retour
          </button>

          <div className="flex items-center gap-2 text-sm font-semibold text-gray-900">
            <GraduationCap className="w-5 h-5 text-blue-700" />
            About Us
          </div>

          <button
            onClick={() => navigate('/admin/login')}
            className="inline-flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 transition-colors"
          >
            <ShieldCheck className="w-4 h-4" />
            Admin
          </button>
        </div>
      </div>

      {/* Content */}
      <main className="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <motion.div
          initial={{ opacity: 0, y: 14 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ duration: 0.5 }}
          className="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden"
        >
          <div className="p-8 md:p-10 bg-gradient-to-br from-blue-50 via-white to-indigo-50">
            <motion.h1
              initial={{ opacity: 0, y: 10 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ duration: 0.5, delay: 0.05 }}
              className="text-3xl md:text-4xl font-bold text-gray-900"
            >
              About UnivDocs
            </motion.h1>
            <p className="mt-3 text-gray-600 max-w-2xl">
              UnivDocs est une plateforme simple et rapide pour centraliser les demandes de documents académiques,
              suivre leur statut, et améliorer la communication entre étudiants et administration.
            </p>
          </div>

          <div className="p-8 md:p-10 grid grid-cols-1 md:grid-cols-3 gap-6">
            <div className="rounded-xl border border-gray-200 p-6 bg-white">
              <div className="w-11 h-11 rounded-lg bg-blue-50 flex items-center justify-center">
                <Sparkles className="w-5 h-5 text-blue-700" />
              </div>
              <h3 className="mt-4 font-semibold text-gray-900">Notre mission</h3>
              <p className="mt-2 text-sm text-gray-600">
                Réduire les frictions administratives et offrir une expérience fluide, moderne et accessible.
              </p>
            </div>

            <div className="rounded-xl border border-gray-200 p-6 bg-white">
              <div className="w-11 h-11 rounded-lg bg-green-50 flex items-center justify-center">
                <ShieldCheck className="w-5 h-5 text-green-700" />
              </div>
              <h3 className="mt-4 font-semibold text-gray-900">Fiabilité</h3>
              <p className="mt-2 text-sm text-gray-600">
                Suivi clair des demandes, statuts explicites, et un processus plus transparent pour tout le monde.
              </p>
            </div>

            <div className="rounded-xl border border-gray-200 p-6 bg-white">
              <div className="w-11 h-11 rounded-lg bg-indigo-50 flex items-center justify-center">
                <GraduationCap className="w-5 h-5 text-indigo-700" />
              </div>
              <h3 className="mt-4 font-semibold text-gray-900">Orientation étudiants</h3>
              <p className="mt-2 text-sm text-gray-600">
                Un parcours guidé, des formulaires simples, et des informations utiles à chaque étape.
              </p>
            </div>
          </div>

          <div className="px-8 md:px-10 pb-10">
            <div className="rounded-xl bg-gray-50 border border-gray-200 p-6">
              <h3 className="font-semibold text-gray-900">Besoin d’aide ?</h3>
              <p className="mt-2 text-sm text-gray-600">
                Utilisez la page d’accueil pour créer une demande ou suivre un dossier. Si vous êtes administrateur,
                connectez-vous via “Admin”.
              </p>
              <div className="mt-4 flex flex-wrap gap-3">
                <button
                  onClick={() => navigate('/')}
                  className="px-4 py-2 rounded-lg bg-blue-700 text-white text-sm font-medium hover:bg-blue-800 transition-colors"
                >
                  Aller à l’accueil
                </button>
              </div>
            </div>
          </div>
        </motion.div>
      </main>
    </div>
  )
}


